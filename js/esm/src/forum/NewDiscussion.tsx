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
 * Inline "new discussion" panel.
 *
 * @module     mod_hiveforum/forum/NewDiscussion
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {useEffect, useId, useRef, useState} from 'react';
import Composer from '@moodle/lms/mod_hiveforum/common/Composer';
import Icon from '@moodle/lms/mod_hiveforum/common/Icon';
import {useStrings} from '@moodle/lms/mod_hiveforum/common/i18n';
import {exception} from '@moodle/lms/mod_hiveforum/common/notify';
import {createDiscussion} from '@moodle/lms/mod_hiveforum/common/repository';
import TagInput from '@moodle/lms/mod_hiveforum/common/TagInput';
import type {GroupTarget, User} from '@moodle/lms/mod_hiveforum/common/types';

interface NewDiscussionProps {
    cmid: number;
    allowtags: boolean;
    suggestions: string[];
    fulleditorurl: string;
    user: User | null;
    /** Types the user may start. */
    posttypes: Array<'discussion' | 'question'>;
    /** Groups the discussion can be addressed to (empty when the forum has no groups). */
    targets: GroupTarget[];
    /** Active group of the list, used as default target. */
    activegroup: number;
    onClose: () => void;
}

export default function NewDiscussion({
    cmid, allowtags, suggestions, fulleditorurl, user, posttypes, targets, activegroup, onClose,
}: NewDiscussionProps) {
    const t = useStrings();
    const titleId = useId();
    const titleRef = useRef<HTMLInputElement>(null);
    const [name, setName] = useState('');
    const [isquestion, setIsquestion] = useState(posttypes.length === 1 && posttypes[0] === 'question');
    const [groupid, setGroupid] = useState(() =>
        targets.some((g) => g.id === activegroup) ? activegroup : (targets[0]?.id ?? 0));
    const [tags, setTags] = useState<string[]>([]);
    const [titleError, setTitleError] = useState(false);

    useEffect(() => titleRef.current?.focus(), []);

    const submit = async(message: string) => {
        if (!name.trim()) {
            setTitleError(true);
            titleRef.current?.focus();
            throw new Error('Title required');
        }
        try {
            const result = await createDiscussion({cmid, name: name.trim(), message, isquestion, tags, groupid});
            window.location.href = result.url;
        } catch (error) {
            exception(error);
            throw error;
        }
    };

    return (
        <section className="hf-newdiscussion" aria-labelledby={`${titleId}-heading`}>
            <div className="hf-newdiscussion__head">
                <h2 id={`${titleId}-heading`} className="hf-newdiscussion__heading">
                    <Icon name="sparkles" size={18} /> {t('newdiscussion')}
                </h2>
                <button type="button" className="hf-iconbtn" onClick={onClose} aria-label={t('close')}>
                    <Icon name="x" />
                </button>
            </div>

            {posttypes.length > 1 && (
            <div className="hf-segmented" role="radiogroup" aria-label={t('discussiontype')}>
                <button type="button" role="radio" aria-checked={!isquestion}
                    className={!isquestion ? 'is-active' : ''} onClick={() => setIsquestion(false)}>
                    <Icon name="chat" size={16} /> {t('typediscussion')}
                </button>
                <button type="button" role="radio" aria-checked={isquestion}
                    className={isquestion ? 'is-active' : ''} onClick={() => setIsquestion(true)}>
                    <Icon name="question" size={16} /> {t('typequestion')}
                </button>
            </div>
            )}
            <p className="hf-newdiscussion__help">{isquestion ? t('typequestion_help') : t('typediscussion_help')}</p>

            <label htmlFor={titleId} className="visually-hidden">{t('discussiontitle')}</label>
            <input
                id={titleId}
                ref={titleRef}
                className={`hf-newdiscussion__title ${titleError ? 'is-invalid' : ''}`}
                value={name}
                maxLength={255}
                placeholder={isquestion ? t('questiontitleplaceholder') : t('discussiontitleplaceholder')}
                aria-invalid={titleError}
                onChange={(e) => {
                    setName(e.target.value);
                    setTitleError(false);
                }}
            />
            {titleError && <p className="hf-field-error">{t('errornameempty')}</p>}

            {targets.length > 0 && (
                <label className="hf-target">
                    <Icon name="users2" size={15} />
                    <span>{t('postto')}</span>
                    <select value={groupid} onChange={(e) => setGroupid(Number(e.target.value))} disabled={targets.length === 1}>
                        {targets.map((g) => <option key={g.id} value={g.id}>{g.name}</option>)}
                    </select>
                </label>
            )}

            {allowtags && <TagInput tags={tags} onChange={setTags} suggestions={suggestions} />}

            <Composer
                draftKey={`new/${cmid}`}
                label={t('message')}
                placeholder={t('messageplaceholder')}
                submitLabel={t('publishdiscussion')}
                onSubmit={submit}
                onCancel={onClose}
                user={user}
                mentions={{cmid, discussionid: 0}}
                extra={
                    <a className="hf-fulleditor" href={fulleditorurl}>
                        <Icon name="expand" size={14} /> {t('fulleditor')}
                    </a>
                }
            />
        </section>
    );
}
