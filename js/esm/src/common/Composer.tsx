// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Lightweight markdown composer: toolbar, auto-growing textarea, Ctrl/⌘+Enter to send,
 * Escape to cancel, and an unsent draft kept in the browser.
 *
 * @module     mod_hiveforum/common/Composer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {useCallback, useEffect, useId, useLayoutEffect, useRef, useState, type KeyboardEvent, type ReactNode} from 'react';
import Icon, {type IconName} from '@moodle/lms/mod_hiveforum/common/Icon';
import {useStrings} from '@moodle/lms/mod_hiveforum/common/i18n';
import {useMentions, type MentionSource} from '@moodle/lms/mod_hiveforum/common/Mentions';
import UserAvatar from '@moodle/lms/mod_hiveforum/common/UserAvatar';
import type {User} from '@moodle/lms/mod_hiveforum/common/types';

/** Per-viewer draft storage; browser storage may be unavailable, so every access is guarded. */
const drafts = {
    get(key: string): string {
        try {
            return window.localStorage.getItem(`mod_hiveforum/draft/${key}`) ?? '';
        } catch {
            return '';
        }
    },
    set(key: string, value: string): void {
        try {
            if (value.trim()) {
                window.localStorage.setItem(`mod_hiveforum/draft/${key}`, value);
            } else {
                window.localStorage.removeItem(`mod_hiveforum/draft/${key}`);
            }
        } catch {
            // Ignore: drafts are a convenience only.
        }
    },
};

interface Format {
    icon: IconName;
    label: string;
    before: string;
    after?: string;
    linePrefix?: boolean;
    placeholder?: string;
}

interface ComposerProps {
    /** Key under which the unsent draft is kept (omit to disable drafts). */
    draftKey?: string;
    initialValue?: string;
    placeholder?: string;
    submitLabel: string;
    onSubmit: (message: string) => Promise<unknown>;
    onCancel?: () => void;
    autoFocus?: boolean;
    user?: User | null;
    compact?: boolean;
    /** Extra controls rendered left of the submit button. */
    extra?: ReactNode;
    /** Accessible label of the textarea. */
    label: string;
    /** Enables @mentions (people who can see this discussion). */
    mentions?: MentionSource;
}

export default function Composer({
    draftKey, initialValue = '', placeholder, submitLabel, onSubmit, onCancel, autoFocus = false, user,
    compact = false, extra, label, mentions,
}: ComposerProps) {
    const t = useStrings();
    const id = useId();
    const textareaRef = useRef<HTMLTextAreaElement>(null);
    const [value, setValue] = useState(() => initialValue || (draftKey ? drafts.get(draftKey) : ''));
    const [busy, setBusy] = useState(false);
    const [focused, setFocused] = useState(autoFocus);
    const mention = useMentions({source: mentions, textareaRef, value, setValue});

    const formats: Format[] = [
        {icon: 'bold', label: t('format_bold'), before: '**', after: '**', placeholder: t('format_bold')},
        {icon: 'italic', label: t('format_italic'), before: '_', after: '_', placeholder: t('format_italic')},
        {icon: 'link', label: t('format_link'), before: '[', after: '](https://)', placeholder: t('format_linktext')},
        {icon: 'code', label: t('format_code'), before: '`', after: '`', placeholder: 'code'},
        {icon: 'quote', label: t('format_quote'), before: '> ', linePrefix: true},
        {icon: 'list', label: t('format_list'), before: '- ', linePrefix: true},
    ];

    // Grow with the content.
    useLayoutEffect(() => {
        const el = textareaRef.current;
        if (!el) {
            return;
        }
        el.style.height = 'auto';
        el.style.height = `${Math.min(el.scrollHeight, 480)}px`;
    }, [value]);

    useEffect(() => {
        if (autoFocus) {
            const el = textareaRef.current;
            el?.focus();
            el?.setSelectionRange(el.value.length, el.value.length);
        }
    }, [autoFocus]);

    useEffect(() => {
        if (draftKey) {
            drafts.set(draftKey, value);
        }
    }, [draftKey, value]);

    const applyFormat = useCallback((format: Format) => {
        const el = textareaRef.current;
        if (!el) {
            return;
        }
        const {selectionStart: start, selectionEnd: end} = el;
        const selected = value.slice(start, end);
        let next: string;
        let cursorStart: number;
        let cursorEnd: number;
        if (format.linePrefix) {
            const lineStart = value.lastIndexOf('\n', start - 1) + 1;
            const block = value.slice(lineStart, end);
            const prefixed = block.split('\n').map((line) => format.before + line).join('\n');
            next = value.slice(0, lineStart) + prefixed + value.slice(end);
            cursorStart = lineStart;
            cursorEnd = lineStart + prefixed.length;
        } else {
            const inner = selected || format.placeholder || '';
            next = value.slice(0, start) + format.before + inner + (format.after ?? '') + value.slice(end);
            cursorStart = start + format.before.length;
            cursorEnd = cursorStart + inner.length;
        }
        setValue(next);
        window.requestAnimationFrame(() => {
            el.focus();
            el.setSelectionRange(cursorStart, cursorEnd);
        });
    }, [value]);

    const submit = useCallback(async() => {
        if (!value.trim() || busy) {
            return;
        }
        setBusy(true);
        try {
            await onSubmit(value);
            setValue('');
            if (draftKey) {
                drafts.set(draftKey, '');
            }
        } catch {
            // The caller reports the error; keep the text so nothing is lost.
        } finally {
            setBusy(false);
        }
    }, [value, busy, onSubmit, draftKey]);

    const onKeyDown = (e: KeyboardEvent<HTMLTextAreaElement>) => {
        if (mention.onKeyDown(e)) {
            return;
        }
        if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
            e.preventDefault();
            submit();
        } else if (e.key === 'Escape' && onCancel) {
            e.preventDefault();
            onCancel();
        } else if ((e.ctrlKey || e.metaKey) && (e.key === 'b' || e.key === 'i')) {
            e.preventDefault();
            applyFormat(formats[e.key === 'b' ? 0 : 1]);
        }
    };

    const expanded = focused || value.length > 0 || !compact;

    return (
        <div className={`hf-composer ${expanded ? 'is-expanded' : ''} ${compact ? 'hf-composer--compact' : ''}`}>
            {user && <div className="hf-composer__avatar"><UserAvatar user={user} size="md" decorative /></div>}
            <div className="hf-composer__box">
                <label htmlFor={id} className="visually-hidden">{label}</label>
                {mention.list}
                <textarea
                    id={id}
                    ref={textareaRef}
                    className="hf-composer__input"
                    value={value}
                    placeholder={placeholder}
                    rows={compact && !expanded ? 1 : 3}
                    onChange={(e) => setValue(e.target.value)}
                    onKeyDown={onKeyDown}
                    onSelect={mention.detect}
                    onFocus={() => setFocused(true)}
                    onBlur={() => setFocused(false)}
                    aria-autocomplete={mention.enabled ? 'list' : undefined}
                    aria-controls={mention.open ? mention.listId : undefined}
                    aria-expanded={mention.enabled ? mention.open : undefined}
                    aria-activedescendant={mention.activeId}
                    disabled={busy}
                />
                {expanded && (
                    <div className="hf-composer__bar">
                        <div className="hf-composer__tools" role="toolbar" aria-label={t('formatting')}>
                            {formats.map((format) => (
                                <button
                                    key={format.icon}
                                    type="button"
                                    className="hf-iconbtn hf-iconbtn--sm"
                                    title={format.label}
                                    aria-label={format.label}
                                    onMouseDown={(e) => e.preventDefault()}
                                    onClick={() => applyFormat(format)}
                                >
                                    <Icon name={format.icon} size={15} />
                                </button>
                            ))}
                            {mention.enabled && (
                                <button
                                    type="button"
                                    className="hf-iconbtn hf-iconbtn--sm"
                                    title={t('mentionsomeone')}
                                    aria-label={t('mentionsomeone')}
                                    onMouseDown={(e) => e.preventDefault()}
                                    onClick={mention.startMention}
                                >
                                    <Icon name="at" size={15} />
                                </button>
                            )}
                            <span className="hf-composer__hint">{t('markdownhint')}</span>
                        </div>
                        <div className="hf-composer__actions">
                            {extra}
                            {onCancel && (
                                <button type="button" className="btn btn-link btn-sm" onClick={onCancel} disabled={busy}>
                                    {t('cancel')}
                                </button>
                            )}
                            <button
                                type="button"
                                className="btn btn-primary btn-sm hf-btn-send"
                                onClick={submit}
                                disabled={busy || !value.trim()}
                                title={t('sendshortcut')}
                            >
                                {busy ? <span className="spinner-border spinner-border-sm" aria-hidden="true" /> : null}
                                <span>{submitLabel}</span>
                            </button>
                        </div>
                    </div>
                )}
            </div>
        </div>
    );
}
