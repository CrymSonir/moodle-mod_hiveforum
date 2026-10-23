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
 * A post and, recursively, its replies.
 *
 * @module     mod_hiveforum/thread/PostItem
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {useEffect, useRef, useState, type CSSProperties} from 'react';
import Composer from '@moodle/lms/mod_hiveforum/common/Composer';
import Icon from '@moodle/lms/mod_hiveforum/common/Icon';
import {useStrings} from '@moodle/lms/mod_hiveforum/common/i18n';
import {filterContentUpdated, toast} from '@moodle/lms/mod_hiveforum/common/notify';
import ReactionBar from '@moodle/lms/mod_hiveforum/common/ReactionBar';
import {RelativeTime} from '@moodle/lms/mod_hiveforum/common/time';
import UserAvatar, {StreakChip} from '@moodle/lms/mod_hiveforum/common/UserAvatar';
import {FORMAT_MARKDOWN, type Post} from '@moodle/lms/mod_hiveforum/common/types';
import {useThread} from '@moodle/lms/mod_hiveforum/thread/context';
import type {PostNode} from '@moodle/lms/mod_hiveforum/thread/tree';

/** Deeper replies are not indented further, to keep posts readable on small screens. */
const MAX_DEPTH = 3;

/**
 * The HTML of a post (already cleaned by format_text on the server).
 */
function Message({html}: {html: string}) {
    const ref = useRef<HTMLDivElement>(null);
    useEffect(() => filterContentUpdated(ref.current), [html]);
    return <div ref={ref} className="hf-post__message" dangerouslySetInnerHTML={{__html: html}} />;
}

interface PostItemProps {
    post: Post;
    node?: PostNode;
    depth?: number;
    parentAuthor?: string;
    variant?: 'opening' | 'reply';
}

export default function PostItem({post, node, depth = 0, parentAuthor, variant = 'reply'}: PostItemProps) {
    const t = useStrings();
    const thread = useThread();
    const {discussion, capabilities: caps, currentuser} = thread;
    const [collapsed, setCollapsed] = useState(false);

    const isReplying = thread.replyingTo === post.id;
    const isEditing = thread.editing === post.id;
    const inlineEditable = post.messageformat === FORMAT_MARKDOWN;
    const isOwn = currentuser?.id === post.author.id;

    const copyLink = () => {
        const url = new URL(window.location.href);
        url.hash = `p${post.id}`;
        navigator.clipboard?.writeText(url.toString())
            .then(() => toast(t('linkcopied')))
            .catch(() => undefined);
    };

    const classes = [
        'hf-post',
        `hf-post--${variant}`,
        post.isaccepted ? 'is-accepted' : '',
        thread.highlighted === post.id ? 'is-highlighted' : '',
        post.author.isstaff ? 'is-staff' : '',
        isOwn ? 'is-own' : '',
    ].filter(Boolean).join(' ');

    return (
        <li className="hf-thread__item" style={{'--hf-depth': Math.min(depth, MAX_DEPTH)} as CSSProperties}>
            <article className={classes} id={`p${post.id}`} aria-labelledby={`hf-p-${post.id}-author`}>
                <header className="hf-post__head">
                    <UserAvatar user={post.author} size={variant === 'opening' ? 'lg' : 'md'} decorative />
                    <div className="hf-post__who">
                        <span className="hf-post__line">
                            <a className="hf-post__author" id={`hf-p-${post.id}-author`} href={post.author.profileurl}>
                                {post.author.fullname}
                            </a>
                            {post.author.isstaff && <span className="hf-badge hf-badge--staff">{t('staff')}</span>}
                            <StreakChip weeks={post.author.streak} />
                            {post.isaccepted && (
                                <span className="hf-badge hf-badge--resolved"><Icon name="check" size={12} />{t('acceptedanswer')}</span>
                            )}
                        </span>
                        <span className="hf-post__time">
                            {depth > MAX_DEPTH && parentAuthor && (
                                <span className="hf-post__inreply"><Icon name="reply" size={12} /> {parentAuthor}</span>
                            )}
                            <a href={`#p${post.id}`} className="hf-post__permalink"><RelativeTime timestamp={post.timecreated} /></a>
                            {post.edited && <span className="hf-post__edited">{t('edited')}</span>}
                        </span>
                    </div>
                </header>

                {isEditing ? (
                    <div className="hf-post__edit">
                        <Composer
                            label={t('editpost')}
                            initialValue={post.rawmessage}
                            submitLabel={t('savechanges')}
                            autoFocus
                            onSubmit={(message) => thread.update(post, message)}
                            onCancel={() => thread.setEditing(null)}
                        />
                    </div>
                ) : (
                    <Message html={post.message} />
                )}

                <footer className="hf-post__foot">
                    {caps.allowreactions && (
                        <ReactionBar
                            postid={post.id}
                            reactions={post.reactions}
                            canreact={caps.canreact}
                            onChange={(reactions) => thread.replacePost({...post, reactions})}
                        />
                    )}
                    <div className="hf-post__actions">
                        {caps.canreply && (
                            <button type="button" className={`hf-action ${isReplying ? 'is-active' : ''}`}
                                onClick={() => thread.setReplyingTo(isReplying ? null : post.id)} aria-expanded={isReplying}>
                                <Icon name="reply" size={15} /> {t('reply')}
                            </button>
                        )}
                        {post.canaccept && (
                            <button type="button" className={`hf-action hf-action--accept ${post.isaccepted ? 'is-active' : ''}`}
                                onClick={() => thread.accept(post)} aria-pressed={post.isaccepted}>
                                <Icon name="checkcircle" size={15} /> {post.isaccepted ? t('unaccept') : t('accept')}
                            </button>
                        )}
                        <span className="hf-post__tools">
                            <button type="button" className="hf-iconbtn hf-iconbtn--sm" onClick={copyLink}
                                title={t('copylink')} aria-label={t('copylink')}>
                                <Icon name="link" size={15} />
                            </button>
                            {post.canedit && (inlineEditable ? (
                                <button type="button" className="hf-iconbtn hf-iconbtn--sm"
                                    onClick={() => thread.setEditing(isEditing ? null : post.id)}
                                    title={t('edit')} aria-label={t('edit')}>
                                    <Icon name="edit" size={15} />
                                </button>
                            ) : (
                                <a className="hf-iconbtn hf-iconbtn--sm" href={post.editurl} title={t('edit')} aria-label={t('edit')}>
                                    <Icon name="edit" size={15} />
                                </a>
                            ))}
                            {post.candelete && (
                                <button type="button" className="hf-iconbtn hf-iconbtn--sm hf-iconbtn--danger"
                                    onClick={() => thread.remove(post)} title={t('delete')} aria-label={t('delete')}>
                                    <Icon name="trash" size={15} />
                                </button>
                            )}
                        </span>
                    </div>
                </footer>

                {isReplying && (
                    <div className="hf-post__reply">
                        <Composer
                            draftKey={`reply/${discussion.id}/${post.id}`}
                            label={t('replyto', post.author.fullname)}
                            placeholder={t('replyto', post.author.fullname)}
                            submitLabel={t('reply')}
                            autoFocus
                            user={currentuser}
                            onSubmit={(message) => thread.reply(post.id, message)}
                            onCancel={() => thread.setReplyingTo(null)}
                        />
                    </div>
                )}
            </article>

            {node && node.children.length > 0 && (
                <>
                    <button type="button" className="hf-collapse" onClick={() => setCollapsed(!collapsed)} aria-expanded={!collapsed}>
                        <Icon name={collapsed ? 'chevronright' : 'chevrondown'} size={14} />
                        {collapsed
                            ? t.plural(node.descendants, 'showonereply', 'showreplies')
                            : t.plural(node.descendants, 'hideonereply', 'hidereplies')}
                    </button>
                    {!collapsed && (
                        <ol className="hf-thread__children">
                            {node.children.map((child) => (
                                <PostItem
                                    key={child.post.id}
                                    post={child.post}
                                    node={child}
                                    depth={depth + 1}
                                    parentAuthor={post.author.fullname}
                                />
                            ))}
                        </ol>
                    )}
                </>
            )}
        </li>
    );
}
