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
 * A discussion in the list.
 *
 * The whole card is clickable through a stretched title link; tags remain
 * separate buttons above it (filtering by tag).
 *
 * @module     mod_hiveforum/forum/DiscussionCard
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Icon from '@moodle/lms/mod_hiveforum/common/Icon';
import {useStrings} from '@moodle/lms/mod_hiveforum/common/i18n';
import {ReactionSummary} from '@moodle/lms/mod_hiveforum/common/ReactionBar';
import {RelativeTime} from '@moodle/lms/mod_hiveforum/common/time';
import UserAvatar, {AvatarStack} from '@moodle/lms/mod_hiveforum/common/UserAvatar';
import type {DiscussionSummary} from '@moodle/lms/mod_hiveforum/common/types';

interface DiscussionCardProps {
    discussion: DiscussionSummary;
    activeTag: string;
    onTag: (tag: string) => void;
}

export default function DiscussionCard({discussion: d, activeTag, onTag}: DiscussionCardProps) {
    const t = useStrings();
    const classes = [
        'hf-card',
        d.unread ? 'is-unread' : '',
        d.pinned ? 'is-pinned' : '',
        d.isquestion ? (d.resolved ? 'is-resolved' : 'is-open-question') : '',
    ].filter(Boolean).join(' ');

    return (
        <article className={classes} aria-labelledby={`hf-d-${d.id}`}>
            <div className="hf-card__avatar">
                <UserAvatar user={d.author} size="lg" decorative />
            </div>
            <div className="hf-card__main">
                <div className="hf-card__badges">
                    {d.pinned && <span className="hf-badge hf-badge--pinned"><Icon name="pin" size={12} />{t('pinned')}</span>}
                    {d.isquestion && !d.resolved && (
                        <span className="hf-badge hf-badge--question"><Icon name="question" size={12} />{t('question')}</span>
                    )}
                    {d.isquestion && d.resolved && (
                        <span className="hf-badge hf-badge--resolved"><Icon name="check" size={12} />{t('resolved')}</span>
                    )}
                    {d.groupname && <span className="hf-badge hf-badge--group"><Icon name="users2" size={12} />{d.groupname}</span>}
                    {d.following && <span className="hf-badge hf-badge--ghost"><Icon name="bellcheck" size={12} />{t('followed')}</span>}
                </div>
                <h3 className="hf-card__title" id={`hf-d-${d.id}`}>
                    {d.unread && <span className="hf-dot" title={t('unreadactivity')}><span className="visually-hidden">{t('unreadactivity')}</span></span>}
                    <a href={d.url} className="hf-card__link" dangerouslySetInnerHTML={{__html: d.name}} />
                </h3>
                {d.excerpt && <p className="hf-card__excerpt">{d.excerpt}</p>}
                <div className="hf-card__meta">
                    <span className="hf-card__author">{d.author.fullname}</span>
                    {d.lastauthor ? (
                        <span>{t('lastreplyby', d.lastauthor.fullname)} <RelativeTime timestamp={d.timemodified} /></span>
                    ) : (
                        <RelativeTime timestamp={d.timecreated} />
                    )}
                    {d.tags.length > 0 && (
                        <span className="hf-card__tags">
                            {d.tags.map((tag) => (
                                <button
                                    key={tag}
                                    type="button"
                                    className={`hf-tag ${tag === activeTag ? 'is-active' : ''}`}
                                    onClick={() => onTag(tag === activeTag ? '' : tag)}
                                    aria-pressed={tag === activeTag}
                                >
                                    #{tag}
                                </button>
                            ))}
                        </span>
                    )}
                </div>
            </div>
            <div className="hf-card__side">
                <span className={`hf-replies ${d.replycount === 0 ? 'is-zero' : ''}`}
                    title={t.plural(d.replycount, 'onereply', 'repliescount', 'noreplies')}>
                    <Icon name="chat" size={15} />
                    <span aria-hidden="true">{d.replycount}</span>
                    <span className="visually-hidden">{t.plural(d.replycount, 'onereply', 'repliescount', 'noreplies')}</span>
                </span>
                <ReactionSummary reactions={d.reactions} />
                <AvatarStack users={d.participants} />
            </div>
        </article>
    );
}

/**
 * Placeholder card while loading.
 */
export function DiscussionCardSkeleton() {
    return (
        <div className="hf-card hf-card--skeleton" aria-hidden="true">
            <span className="hf-skel hf-skel--avatar" />
            <div className="hf-card__main">
                <span className="hf-skel hf-skel--title" />
                <span className="hf-skel hf-skel--line" />
                <span className="hf-skel hf-skel--line hf-skel--short" />
            </div>
        </div>
    );
}
