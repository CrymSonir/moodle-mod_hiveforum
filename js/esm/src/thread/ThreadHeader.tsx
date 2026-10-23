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
 * Discussion header: badges, title (renamable), tags, follow / pin / resolve.
 *
 * @module     mod_hiveforum/thread/ThreadHeader
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {useState} from 'react';
import Icon from '@moodle/lms/mod_hiveforum/common/Icon';
import {useStrings} from '@moodle/lms/mod_hiveforum/common/i18n';
import {exception, toast} from '@moodle/lms/mod_hiveforum/common/notify';
import {setDiscussionState, updateDiscussion} from '@moodle/lms/mod_hiveforum/common/repository';
import TagInput from '@moodle/lms/mod_hiveforum/common/TagInput';
import {RelativeTime} from '@moodle/lms/mod_hiveforum/common/time';
import {AvatarStack} from '@moodle/lms/mod_hiveforum/common/UserAvatar';
import type {Discussion, User} from '@moodle/lms/mod_hiveforum/common/types';

interface ThreadHeaderProps {
    discussion: Discussion;
    forumurl: string;
    replycount: number;
    participants: User[];
    allowtags: boolean;
    onChange: (discussion: Discussion) => void;
}

export default function ThreadHeader({discussion: d, forumurl, replycount, participants, allowtags, onChange}: ThreadHeaderProps) {
    const t = useStrings();
    const [renaming, setRenaming] = useState(false);
    const [name, setName] = useState(d.rawname);
    const [tags, setTags] = useState(d.tags);
    const [busy, setBusy] = useState<string | null>(null);

    const toggle = (state: 'pinned' | 'resolved' | 'following', value: boolean, message: string) => {
        setBusy(state);
        // Optimistic update.
        onChange({...d, [state]: value});
        setDiscussionState(d.id, state, value)
            .then((result) => {
                onChange(result.discussion);
                toast(message);
            })
            .catch((error) => {
                onChange(d);
                exception(error);
            })
            .finally(() => setBusy(null));
    };

    const saveRename = () => {
        if (!name.trim()) {
            return;
        }
        setBusy('rename');
        updateDiscussion(d.id, name.trim(), tags)
            .then((result) => {
                onChange(result.discussion);
                setRenaming(false);
            })
            .catch(exception)
            .finally(() => setBusy(null));
    };

    return (
        <header className="hf-threadhead">
            <div className="hf-threadhead__badges">
                {d.pinned && <span className="hf-badge hf-badge--pinned"><Icon name="pin" size={12} />{t('pinned')}</span>}
                {d.isquestion && !d.resolved && (
                    <span className="hf-badge hf-badge--question"><Icon name="question" size={12} />{t('question')}</span>
                )}
                {d.isquestion && d.resolved && (
                    <span className="hf-badge hf-badge--resolved"><Icon name="check" size={12} />{t('resolved')}</span>
                )}
            </div>

            {renaming ? (
                <div className="hf-rename">
                    <label htmlFor="hf-rename-input" className="visually-hidden">{t('discussiontitle')}</label>
                    <input
                        id="hf-rename-input"
                        className="hf-rename__input"
                        value={name}
                        maxLength={255}
                        autoFocus
                        onChange={(e) => setName(e.target.value)}
                        onKeyDown={(e) => {
                            if (e.key === 'Enter') {
                                saveRename();
                            } else if (e.key === 'Escape') {
                                setRenaming(false);
                            }
                        }}
                    />
                    {allowtags && <TagInput tags={tags} onChange={setTags} />}
                    <div className="hf-rename__actions">
                        <button type="button" className="btn btn-link btn-sm" onClick={() => setRenaming(false)}>{t('cancel')}</button>
                        <button type="button" className="btn btn-primary btn-sm" onClick={saveRename}
                            disabled={busy === 'rename' || !name.trim()}>{t('savechanges')}</button>
                    </div>
                </div>
            ) : (
                <h2 className="hf-threadhead__title">
                    <span dangerouslySetInnerHTML={{__html: d.name}} />
                    {d.canrename && (
                        <button type="button" className="hf-iconbtn hf-iconbtn--sm" onClick={() => {
                            setName(d.rawname);
                            setTags(d.tags);
                            setRenaming(true);
                        }} title={t('renamediscussion')} aria-label={t('renamediscussion')}>
                            <Icon name="edit" size={15} />
                        </button>
                    )}
                </h2>
            )}

            {!renaming && d.tags.length > 0 && (
                <div className="hf-threadhead__tags">
                    {d.tags.map((tag) => (
                        <a key={tag} className="hf-tag" href={`${forumurl}&tag=${encodeURIComponent(tag)}`}>#{tag}</a>
                    ))}
                </div>
            )}

            <div className="hf-threadhead__bar">
                <div className="hf-threadhead__meta">
                    <AvatarStack users={participants} max={5} />
                    <span>{t('startedby', d.author.fullname)} <RelativeTime timestamp={d.timecreated} /></span>
                    <span className="hf-threadhead__count">
                        <Icon name="chat" size={14} /> {t.plural(replycount, 'onereply', 'repliescount', 'noreplies')}
                    </span>
                </div>
                <div className="hf-threadhead__actions">
                    {d.canfollow && (
                        <button type="button" className={`btn btn-sm ${d.following ? 'btn-secondary hf-btn-following' : 'btn-outline-secondary'}`}
                            aria-pressed={d.following} disabled={busy === 'following'}
                            onClick={() => toggle('following', !d.following, d.following ? t('unfollowed') : t('followedtoast'))}>
                            <Icon name={d.following ? 'bellcheck' : 'bell'} size={15} />
                            {d.following ? t('following_btn') : t('follow')}
                        </button>
                    )}
                    {d.canpin && (
                        <button type="button" className={`btn btn-sm ${d.pinned ? 'btn-secondary' : 'btn-outline-secondary'}`}
                            aria-pressed={d.pinned} disabled={busy === 'pinned'}
                            onClick={() => toggle('pinned', !d.pinned, d.pinned ? t('unpinned') : t('pinnedtoast'))}>
                            <Icon name="pin" size={15} /> {d.pinned ? t('unpindiscussion') : t('pindiscussion')}
                        </button>
                    )}
                    {d.isquestion && d.canresolve && (
                        <button type="button" className={`btn btn-sm ${d.resolved ? 'btn-success' : 'btn-outline-success'}`}
                            aria-pressed={d.resolved} disabled={busy === 'resolved'}
                            onClick={() => toggle('resolved', !d.resolved, d.resolved ? t('reopenedtoast') : t('resolvedtoast'))}>
                            <Icon name="checkcircle" size={15} /> {d.resolved ? t('markunresolved') : t('markresolved')}
                        </button>
                    )}
                </div>
            </div>
        </header>
    );
}
