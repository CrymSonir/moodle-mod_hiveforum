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
 * @mention autocomplete for the composer.
 *
 * Typing "@" followed by a few letters lists the people who can see the discussion
 * (the server applies capabilities, enrolment and groups). Choosing one inserts the token
 * "@[Name](user:id)", which the server turns into a profile link and a notification.
 *
 * @module     mod_hiveforum/common/Mentions
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {useCallback, useEffect, useId, useRef, useState, type KeyboardEvent, type RefObject} from 'react';
import {useStrings} from '@moodle/lms/mod_hiveforum/common/i18n';
import {searchUsers} from '@moodle/lms/mod_hiveforum/common/repository';
import UserAvatar from '@moodle/lms/mod_hiveforum/common/UserAvatar';
import type {User} from '@moodle/lms/mod_hiveforum/common/types';

/** Where mentions are searched: the forum and the discussion (0 for a new one). */
export interface MentionSource {
    cmid: number;
    discussionid: number;
}

const TRIGGER = /(^|\s)@([\p{L}\p{N}_.'-]{0,30})$/u;
const DEBOUNCE_MS = 150;

/** Token stored in the message for a mention. */
export const mentionToken = (user: User): string =>
    `@[${user.fullname.replace(/[[\]()\r\n]/g, '')}](user:${user.id}) `;

interface Options {
    source?: MentionSource;
    textareaRef: RefObject<HTMLTextAreaElement | null>;
    value: string;
    setValue: (value: string) => void;
}

export function useMentions({source, textareaRef, value, setValue}: Options) {
    const listId = useId();
    const [query, setQuery] = useState<string | null>(null);
    const [start, setStart] = useState(0);
    const [results, setResults] = useState<User[]>([]);
    const [active, setActive] = useState(0);
    const requestRef = useRef(0);

    /** Look for "@query" right before the caret. */
    const detect = useCallback(() => {
        const el = textareaRef.current;
        if (!source || !el || el.selectionStart !== el.selectionEnd) {
            setQuery(null);
            return;
        }
        const caret = el.selectionStart;
        const match = el.value.slice(0, caret).match(TRIGGER);
        if (match) {
            setQuery(match[2]);
            setStart(caret - match[2].length - 1);
        } else {
            setQuery(null);
        }
    }, [source, textareaRef]);

    useEffect(detect, [value, detect]);

    useEffect(() => {
        if (query === null || !source) {
            setResults([]);
            return undefined;
        }
        const current = ++requestRef.current;
        const timer = window.setTimeout(() => {
            searchUsers(source.cmid, source.discussionid, query)
                .then((result) => {
                    if (current === requestRef.current) {
                        setResults(result.users);
                        setActive(0);
                    }
                })
                .catch(() => setResults([]));
        }, DEBOUNCE_MS);
        return () => window.clearTimeout(timer);
    }, [query, source]);

    const close = useCallback(() => {
        setQuery(null);
        setResults([]);
    }, []);

    const choose = useCallback((user: User) => {
        const el = textareaRef.current;
        const caret = el ? el.selectionStart : value.length;
        const token = mentionToken(user);
        const next = value.slice(0, start) + token + value.slice(caret);
        setValue(next);
        close();
        window.requestAnimationFrame(() => {
            el?.focus();
            el?.setSelectionRange(start + token.length, start + token.length);
        });
    }, [textareaRef, value, start, setValue, close]);

    /** Insert "@" at the caret to start a mention (toolbar button). */
    const startMention = useCallback(() => {
        const el = textareaRef.current;
        if (!el) {
            return;
        }
        const {selectionStart: s, selectionEnd: e} = el;
        const needsSpace = s > 0 && !/\s/.test(value[s - 1]);
        const insert = (needsSpace ? ' ' : '') + '@';
        setValue(value.slice(0, s) + insert + value.slice(e));
        window.requestAnimationFrame(() => {
            el.focus();
            el.setSelectionRange(s + insert.length, s + insert.length);
            detect();
        });
    }, [textareaRef, value, setValue, detect]);

    /** Keyboard navigation in the list; returns true when the key was handled. */
    const onKeyDown = useCallback((e: KeyboardEvent<HTMLTextAreaElement>): boolean => {
        if (query === null || !results.length) {
            return false;
        }
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            const delta = e.key === 'ArrowDown' ? 1 : -1;
            setActive((i) => (i + delta + results.length) % results.length);
            return true;
        }
        if (e.key === 'Enter' || e.key === 'Tab') {
            e.preventDefault();
            choose(results[active]);
            return true;
        }
        if (e.key === 'Escape') {
            e.preventDefault();
            close();
            return true;
        }
        return false;
    }, [query, results, active, choose, close]);

    const open = query !== null && results.length > 0;

    return {
        open,
        listId,
        activeId: open ? `${listId}-${results[active]?.id}` : undefined,
        detect,
        onKeyDown,
        startMention,
        enabled: Boolean(source),
        list: open ? (
            <MentionList id={listId} users={results} active={active} onChoose={choose} onHover={setActive} />
        ) : null,
    };
}

function MentionList({id, users, active, onChoose, onHover}: {
    id: string;
    users: User[];
    active: number;
    onChoose: (user: User) => void;
    onHover: (index: number) => void;
}) {
    const t = useStrings();
    return (
        <ul className="hf-mentions" id={id} role="listbox" aria-label={t('mentionsomeone')}>
            {users.map((user, index) => (
                <li
                    key={user.id}
                    id={`${id}-${user.id}`}
                    role="option"
                    aria-selected={index === active}
                    className={`hf-mentions__item ${index === active ? 'is-active' : ''}`}
                    onMouseDown={(e) => {
                        e.preventDefault();
                        onChoose(user);
                    }}
                    onMouseEnter={() => onHover(index)}
                >
                    <UserAvatar user={user} size="md" decorative />
                    <span className="hf-mentions__name">{user.fullname}</span>
                    {user.isstaff && <span className="hf-badge hf-badge--staff">{t('staff')}</span>}
                </li>
            ))}
        </ul>
    );
}
