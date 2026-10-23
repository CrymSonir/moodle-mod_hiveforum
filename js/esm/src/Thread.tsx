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
 * Discussion page: opening post, accepted answer, threaded replies, inline reply / edit,
 * and a live check for new replies while the tab is visible.
 *
 * Mounted by core/react_autoinit from data-react-component="@moodle/lms/mod_hiveforum/Thread".
 *
 * @module     mod_hiveforum/Thread
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {useCallback, useEffect, useMemo, useRef, useState} from 'react';
import Composer from '@moodle/lms/mod_hiveforum/common/Composer';
import ConfirmDialog from '@moodle/lms/mod_hiveforum/common/ConfirmDialog';
import Icon from '@moodle/lms/mod_hiveforum/common/Icon';
import {StringsProvider, useStrings} from '@moodle/lms/mod_hiveforum/common/i18n';
import {exception, toast} from '@moodle/lms/mod_hiveforum/common/notify';
import {addPost, deletePost, getDiscussion, toggleAccepted, updatePost} from '@moodle/lms/mod_hiveforum/common/repository';
import type {Discussion, Post, ThreadProps, User} from '@moodle/lms/mod_hiveforum/common/types';
import {ThreadContext, type ThreadActions} from '@moodle/lms/mod_hiveforum/thread/context';
import PostItem from '@moodle/lms/mod_hiveforum/thread/PostItem';
import ThreadHeader from '@moodle/lms/mod_hiveforum/thread/ThreadHeader';
import {buildTree} from '@moodle/lms/mod_hiveforum/thread/tree';

/** Scroll to a post and flash it. */
const focusPost = (postid: number, setHighlighted: (id: number | null) => void) => {
    window.requestAnimationFrame(() => {
        const el = document.getElementById(`p${postid}`);
        if (el) {
            const reduce = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
            el.scrollIntoView({behavior: reduce ? 'auto' : 'smooth', block: 'center'});
        }
        setHighlighted(postid);
        window.setTimeout(() => setHighlighted(null), 2400);
    });
};

function ThreadView(props: ThreadProps) {
    const t = useStrings();
    const {capabilities, currentuser, pollinterval} = props;

    const [discussion, setDiscussion] = useState<Discussion>(props.discussion);
    const [posts, setPosts] = useState<Post[]>(props.posts);
    const [replyingTo, setReplyingTo] = useState<number | null>(null);
    const [editing, setEditing] = useState<number | null>(null);
    const [highlighted, setHighlighted] = useState<number | null>(null);
    const [incoming, setIncoming] = useState<{discussion: Discussion; posts: Post[]; count: number} | null>(null);
    const [toDelete, setToDelete] = useState<Post | null>(null);
    const postsRef = useRef(posts);
    postsRef.current = posts;

    const {root, replies} = useMemo(() => buildTree(posts), [posts]);
    const accepted = discussion.isquestion ? posts.find((p) => p.isaccepted) : undefined;
    const participants = useMemo(() => {
        const seen = new Map<number, User>();
        [...posts].reverse().forEach((p) => seen.has(p.author.id) || seen.set(p.author.id, p.author));
        return [...seen.values()];
    }, [posts]);

    // Deep link to a post (#p123).
    useEffect(() => {
        const match = window.location.hash.match(/^#p(\d+)$/);
        if (match) {
            focusPost(Number(match[1]), setHighlighted);
        }
    }, []);

    // Live check for new replies while the page is visible.
    useEffect(() => {
        if (!pollinterval) {
            return undefined;
        }
        const check = () => {
            if (document.visibilityState !== 'visible') {
                return;
            }
            getDiscussion(discussion.id, false)
                .then((result) => {
                    const known = new Set(postsRef.current.map((p) => p.id));
                    const fresh = result.posts.filter((p) => !known.has(p.id) && p.author.id !== currentuser?.id);
                    if (fresh.length) {
                        setIncoming({discussion: result.discussion, posts: result.posts, count: fresh.length});
                    }
                })
                .catch(() => undefined); // Silent: a later check will retry.
        };
        const id = window.setInterval(check, pollinterval * 1000);
        document.addEventListener('visibilitychange', check);
        return () => {
            window.clearInterval(id);
            document.removeEventListener('visibilitychange', check);
        };
    }, [discussion.id, pollinterval, currentuser?.id]);

    const showIncoming = () => {
        if (!incoming) {
            return;
        }
        const known = new Set(posts.map((p) => p.id));
        const first = incoming.posts.find((p) => !known.has(p.id));
        setDiscussion(incoming.discussion);
        setPosts(incoming.posts);
        setIncoming(null);
        getDiscussion(discussion.id, true).catch(() => undefined); // Mark as read.
        if (first) {
            focusPost(first.id, setHighlighted);
        }
    };

    const replacePost = useCallback((post: Post) => {
        setPosts((prev) => prev.map((p) => (p.id === post.id ? post : p)));
    }, []);

    const reply = useCallback(async(parentid: number, message: string) => {
        try {
            const result = await addPost(discussion.id, parentid, message);
            setPosts((prev) => [...prev, result.post]);
            setReplyingTo(null);
            setDiscussion((prev) => ({...prev, following: true}));
            focusPost(result.post.id, setHighlighted);
        } catch (error) {
            exception(error);
            throw error;
        }
    }, [discussion.id]);

    const update = useCallback(async(post: Post, message: string) => {
        try {
            const result = await updatePost(post.id, message);
            replacePost(result.post);
            setEditing(null);
            toast(t('postupdated'));
        } catch (error) {
            exception(error);
            throw error;
        }
    }, [replacePost, t]);

    const confirmDelete = (post: Post) => {
        deletePost(post.id)
            .then((result) => {
                if (result.discussiondeleted) {
                    window.location.href = result.forumurl;
                    return;
                }
                // Remove the post and its descendants.
                setPosts((prev) => {
                    const removed = new Set([post.id]);
                    let changed = true;
                    while (changed) {
                        changed = false;
                        for (const p of prev) {
                            if (!removed.has(p.id) && removed.has(p.parentid)) {
                                removed.add(p.id);
                                changed = true;
                            }
                        }
                    }
                    return prev.filter((p) => !removed.has(p.id));
                });
                if (post.isaccepted) {
                    setDiscussion((prev) => ({...prev, resolved: false}));
                }
                toast(t('postdeleted'));
            })
            .catch(exception);
    };

    const accept = useCallback((post: Post) => {
        toggleAccepted(post.id)
            .then((result) => {
                setPosts((prev) => prev.map((p) => ({...p, isaccepted: p.id === post.id ? result.accepted : false})));
                setDiscussion((prev) => ({...prev, resolved: result.resolved}));
                toast(result.accepted ? t('answeraccepted') : t('answerunaccepted'));
            })
            .catch(exception);
    }, [t]);

    const actions: ThreadActions = {
        discussion,
        capabilities,
        currentuser,
        replyingTo,
        editing,
        highlighted,
        setReplyingTo: (id) => {
            setReplyingTo(id);
            setEditing(null);
        },
        setEditing: (id) => {
            setEditing(id);
            setReplyingTo(null);
        },
        reply,
        update,
        remove: setToDelete,
        accept,
        replacePost,
    };

    const replycount = Math.max(0, posts.length - 1);

    return (
        <ThreadContext.Provider value={actions}>
            <div className="hf hf-thread">
                <ThreadHeader
                    discussion={discussion}
                    forumurl={props.forumurl}
                    replycount={replycount}
                    participants={participants}
                    allowtags={capabilities.allowtags}
                    onChange={setDiscussion}
                />

                {root && (
                    <ol className="hf-thread__list hf-thread__list--opening">
                        <PostItem post={root} variant="opening" />
                    </ol>
                )}

                {accepted && (
                    <a className="hf-answer" href={`#p${accepted.id}`} onClick={(e) => {
                        e.preventDefault();
                        focusPost(accepted.id, setHighlighted);
                    }}>
                        <span className="hf-answer__icon"><Icon name="checkcircle" size={20} /></span>
                        <span className="hf-answer__body">
                            <strong>{t('acceptedanswerby', accepted.author.fullname)}</strong>
                            <span className="hf-answer__excerpt">
                                {new DOMParser().parseFromString(accepted.message, 'text/html').body.textContent?.slice(0, 180)}
                            </span>
                        </span>
                        <Icon name="arrowdown" size={16} />
                    </a>
                )}

                <div className="hf-thread__repliesheading">
                    <h3>{t.plural(replycount, 'onereply', 'repliescount', 'noreplies')}</h3>
                </div>

                {replies.length > 0 ? (
                    <ol className="hf-thread__list">
                        {replies.map((node) => <PostItem key={node.post.id} post={node.post} node={node} />)}
                    </ol>
                ) : (
                    <p className="hf-thread__empty">{t('noreplies_help')}</p>
                )}

                {capabilities.canreply && root && (
                    <section className="hf-thread__composer" aria-label={t('addreply')}>
                        <Composer
                            draftKey={`reply/${discussion.id}/0`}
                            label={t('addreply')}
                            placeholder={discussion.isquestion ? t('answerplaceholder') : t('replyplaceholder')}
                            submitLabel={t('reply')}
                            user={currentuser}
                            compact
                            onSubmit={(message) => reply(root.id, message)}
                        />
                    </section>
                )}

                {incoming && (
                    <div className="hf-incoming" role="status">
                        <button type="button" className="hf-incoming__btn" onClick={showIncoming}>
                            <Icon name="arrowdown" size={16} />
                            {t.plural(incoming.count, 'newreplyone', 'newreplymany')}
                        </button>
                    </div>
                )}

                <ConfirmDialog
                    open={toDelete !== null}
                    title={toDelete?.isfirst ? t('deletediscussion') : t('deletepost')}
                    message={toDelete?.isfirst ? t('deletediscussionconfirm') : t('deleteconfirm')}
                    confirmLabel={t('delete')}
                    danger
                    onConfirm={() => toDelete && confirmDelete(toDelete)}
                    onClose={() => setToDelete(null)}
                />
            </div>
        </ThreadContext.Provider>
    );
}

export default function Thread(props: ThreadProps) {
    return (
        <StringsProvider strings={props.strings}>
            <ThreadView {...props} />
        </StringsProvider>
    );
}
