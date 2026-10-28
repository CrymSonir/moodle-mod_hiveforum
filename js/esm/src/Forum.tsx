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
 * Discussion list page.
 *
 * Mounted by core/react_autoinit from data-react-component="@moodle/lms/mod_hiveforum/Forum".
 * The first page comes in the props; filters, search, tags, sort and "load more" then
 * use mod_hiveforum_get_discussions without reloading the page, and the URL is kept in
 * sync so reload / back / sharing a link restore the same view.
 *
 * @module     mod_hiveforum/Forum
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {useCallback, useEffect, useMemo, useRef, useState} from 'react';
import Icon from '@moodle/lms/mod_hiveforum/common/Icon';
import {StringsProvider, useStrings} from '@moodle/lms/mod_hiveforum/common/i18n';
import {exception, toast} from '@moodle/lms/mod_hiveforum/common/notify';
import {getDiscussions, markAllRead} from '@moodle/lms/mod_hiveforum/common/repository';
import {StreakChip} from '@moodle/lms/mod_hiveforum/common/UserAvatar';
import DiscussionCard, {DiscussionCardSkeleton} from '@moodle/lms/mod_hiveforum/forum/DiscussionCard';
import NewDiscussion from '@moodle/lms/mod_hiveforum/forum/NewDiscussion';
import NotificationMenu from '@moodle/lms/mod_hiveforum/forum/NotificationMenu';
import type {Counts, DiscussionSummary, Filter, ForumProps, Sort} from '@moodle/lms/mod_hiveforum/common/types';

const SEARCH_DEBOUNCE_MS = 350;

const FILTERS: ReadonlyArray<{id: Filter; label: string; count?: keyof Counts}> = [
    {id: 'all', label: 'filter_all'},
    {id: 'unread', label: 'filter_unread', count: 'unread'},
    {id: 'following', label: 'filter_following', count: 'following'},
    {id: 'questions', label: 'filter_questions', count: 'questions'},
    {id: 'unanswered', label: 'filter_unanswered', count: 'unanswered'},
    {id: 'mine', label: 'filter_mine'},
];

const SORTS: ReadonlyArray<Sort> = ['recent', 'newest', 'popular', 'reactions', 'likes', 'replies'];

/**
 * Keep the query string in sync with the current view.
 */
const syncUrl = (cmid: number, filter: Filter, search: string, tag: string, sort: Sort) => {
    const params = new URLSearchParams({id: String(cmid)});
    if (filter !== 'all') {
        params.set('filter', filter);
    }
    if (search) {
        params.set('search', search);
    }
    if (tag) {
        params.set('tag', tag);
    }
    if (sort !== 'recent') {
        params.set('sort', sort);
    }
    window.history.replaceState(null, '', `${window.location.pathname}?${params.toString()}`);
};

function ForumView(props: ForumProps) {
    const t = useStrings();
    const {cmid, capabilities: caps, currentuser, stats} = props;

    const [filter, setFilter] = useState<Filter>(props.filter);
    const [search, setSearch] = useState(props.search);
    const [searchInput, setSearchInput] = useState(props.search);
    const [tag, setTag] = useState(props.tag);
    const [sort, setSort] = useState<Sort>(props.sort);
    const [discussions, setDiscussions] = useState<DiscussionSummary[]>(props.discussions);
    const [total, setTotal] = useState(props.total);
    const [counts, setCounts] = useState<Counts>(props.counts);
    const [page, setPage] = useState(0);
    const [loading, setLoading] = useState(false);
    const [loadingMore, setLoadingMore] = useState(false);
    const [composing, setComposing] = useState(false);
    const firstRender = useRef(true);
    const requestId = useRef(0);

    // Debounced search.
    useEffect(() => {
        const id = window.setTimeout(() => setSearch(searchInput.trim()), SEARCH_DEBOUNCE_MS);
        return () => window.clearTimeout(id);
    }, [searchInput]);

    // Reload the first page whenever the query changes (but not on mount: the props already have it).
    useEffect(() => {
        if (firstRender.current) {
            firstRender.current = false;
            return;
        }
        syncUrl(cmid, filter, search, tag, sort);
        const current = ++requestId.current;
        setLoading(true);
        getDiscussions({cmid, filter, search, tag, sort, page: 0, groupid: props.groupid})
            .then((result) => {
                if (current !== requestId.current) {
                    return; // A newer request superseded this one.
                }
                setDiscussions(result.discussions);
                setTotal(result.total);
                setCounts(result.counts);
                setPage(0);
            })
            .catch(exception)
            .finally(() => current === requestId.current && setLoading(false));
    }, [cmid, filter, search, tag, sort, props.groupid]);

    const loadMore = useCallback(() => {
        const next = page + 1;
        setLoadingMore(true);
        getDiscussions({cmid, filter, search, tag, sort, page: next, groupid: props.groupid})
            .then((result) => {
                setDiscussions((prev) => {
                    const known = new Set(prev.map((d) => d.id));
                    return [...prev, ...result.discussions.filter((d) => !known.has(d.id))];
                });
                setTotal(result.total);
                setPage(next);
            })
            .catch(exception)
            .finally(() => setLoadingMore(false));
    }, [cmid, filter, search, tag, sort, page, props.groupid]);

    const onMarkAllRead = () => {
        markAllRead(cmid)
            .then(() => {
                setDiscussions((prev) => prev.map((d) => ({...d, unread: false})));
                setCounts((prev) => ({...prev, unread: 0}));
                toast(t('allmarkedread'));
                if (filter === 'unread') {
                    setDiscussions([]);
                    setTotal(0);
                }
            })
            .catch(exception);
    };

    const tagSuggestions = useMemo(() => props.populartags.map((p) => p.name), [props.populartags]);
    const hasQuery = search !== '' || tag !== '' || filter !== 'all';
    const remaining = total - discussions.length;

    return (
        <div className="hf hf-forum">
            <header className="hf-hero">
                <div className="hf-hero__stats">
                    <span className="hf-stat"><Icon name="layers" size={16} />
                        <strong>{stats.discussions}</strong> {t.plural(stats.discussions, 'stat_discussion', 'stat_discussions')}
                    </span>
                    <span className="hf-stat"><Icon name="chat" size={16} />
                        <strong>{stats.posts}</strong> {t.plural(stats.posts, 'stat_message', 'stat_messages')}
                    </span>
                    <span className="hf-stat"><Icon name="users" size={16} />
                        <strong>{stats.participants}</strong> {t.plural(stats.participants, 'stat_participant', 'stat_participants')}
                    </span>
                </div>
                <div className="hf-hero__actions">
                    {currentuser && <StreakChip weeks={currentuser.streak} large />}
                    {caps.canviewreports && (
                        <a className="hf-iconbtn" href={props.reporturl} title={t('engagementreport')} aria-label={t('engagementreport')}>
                            <Icon name="report" />
                        </a>
                    )}
                    {caps.canmanagesubscriptions && (
                        <a className="hf-iconbtn" href={props.subscribersurl} title={t('managesubscriptions')}
                            aria-label={t('managesubscriptions')}>
                            <Icon name="users2" />
                        </a>
                    )}
                    {props.preferences && <NotificationMenu cmid={cmid} preferences={props.preferences} />}
                    {caps.canstart && !composing && (
                        <button type="button" className="btn btn-primary hf-btn-new" onClick={() => setComposing(true)}>
                            <Icon name="plus" size={18} /> {t('newdiscussion')}
                        </button>
                    )}
                </div>
            </header>

            {composing && (
                <NewDiscussion
                    cmid={cmid}
                    allowtags={caps.allowtags}
                    suggestions={tagSuggestions}
                    fulleditorurl={props.fulleditorurl}
                    user={currentuser}
                    posttypes={caps.posttypes}
                    targets={caps.posttargets}
                    activegroup={props.groupid}
                    onClose={() => setComposing(false)}
                />
            )}

            <div className="hf-toolbar">
                <nav className="hf-tabs" aria-label={t('filters')}>
                    {FILTERS.filter((f) => f.id !== 'mine' || currentuser).map((f) => {
                        const count = f.count ? counts[f.count] : 0;
                        return (
                            <button
                                key={f.id}
                                type="button"
                                className={`hf-tab ${filter === f.id ? 'is-active' : ''}`}
                                aria-pressed={filter === f.id}
                                onClick={() => setFilter(f.id)}
                            >
                                {t(f.label)}
                                {count > 0 && <span className={`hf-tab__count ${f.id === 'unread' ? 'is-accent' : ''}`}>{count}</span>}
                            </button>
                        );
                    })}
                </nav>
                <div className="hf-toolbar__right">
                    <div className="hf-search" role="search">
                        <Icon name="search" size={16} className="hf-search__icon" />
                        <label htmlFor="hf-search-input" className="visually-hidden">{t('searchdiscussions')}</label>
                        <input
                            id="hf-search-input"
                            type="search"
                            className="hf-search__input"
                            placeholder={t('searchdiscussions')}
                            value={searchInput}
                            onChange={(e) => setSearchInput(e.target.value)}
                        />
                        {searchInput && (
                            <button type="button" className="hf-search__clear" onClick={() => setSearchInput('')}
                                aria-label={t('clearsearch')}>
                                <Icon name="x" size={14} />
                            </button>
                        )}
                    </div>
                    <label className="hf-sort">
                        <span className="visually-hidden">{t('sortby')}</span>
                        <Icon name="sort" size={16} />
                        <select value={sort} onChange={(e) => setSort(e.target.value as Sort)}>
                            {SORTS.map((s) => <option key={s} value={s}>{t(`sort_${s}`)}</option>)}
                        </select>
                    </label>
                </div>
            </div>

            {(props.populartags.length > 0 || tag) && (
                <div className="hf-tagbar" aria-label={t('populartags')}>
                    <Icon name="tag" size={15} className="hf-tagbar__icon" />
                    {tag && !tagSuggestions.includes(tag) && (
                        <button type="button" className="hf-tag is-active" aria-pressed="true" onClick={() => setTag('')}>
                            #{tag} <Icon name="x" size={12} />
                        </button>
                    )}
                    {props.populartags.map((p) => (
                        <button
                            key={p.name}
                            type="button"
                            className={`hf-tag ${tag === p.name ? 'is-active' : ''}`}
                            aria-pressed={tag === p.name}
                            onClick={() => setTag(tag === p.name ? '' : p.name)}
                        >
                            #{p.name} <span className="hf-tag__count">{p.count}</span>
                            {tag === p.name && <Icon name="x" size={12} />}
                        </button>
                    ))}
                </div>
            )}

            <div className="hf-listhead" aria-live="polite">
                <span>
                    {hasQuery ? t.plural(total, 'resultsone', 'resultsmany', 'resultsnone') : t.plural(total, 'stat_discussion_n', 'stat_discussions_n')}
                </span>
                {counts.unread > 0 && currentuser && (
                    <button type="button" className="btn btn-link btn-sm hf-markread" onClick={onMarkAllRead}>
                        <Icon name="checkcircle" size={14} /> {t('markallread')}
                    </button>
                )}
            </div>

            <div className={`hf-list ${loading ? 'is-loading' : ''}`} aria-busy={loading}>
                {loading && discussions.length === 0 && [0, 1, 2].map((i) => <DiscussionCardSkeleton key={i} />)}
                {discussions.map((d) => (
                    <DiscussionCard key={d.id} discussion={d} activeTag={tag} onTag={setTag} />
                ))}
                {!loading && discussions.length === 0 && (
                    <div className="hf-empty">
                        <div className="hf-empty__art" aria-hidden="true">
                            <svg viewBox="0 0 120 104" width="120" height="104">
                                <path className="hf-hex hf-hex--a" d="M30 4l26 15v30L30 64 4 49V19z" />
                                <path className="hf-hex hf-hex--b" d="M90 4l26 15v30L90 64 64 49V19z" />
                                <path className="hf-hex hf-hex--c" d="M60 40l26 15v30L60 100 34 85V55z" />
                            </svg>
                        </div>
                        <h3 className="hf-empty__title">{hasQuery ? t('emptyresults') : t('emptydiscussions')}</h3>
                        <p className="hf-empty__text">{hasQuery ? t('emptyresults_help') : t('emptydiscussions_help')}</p>
                        {hasQuery ? (
                            <button type="button" className="btn btn-outline-secondary" onClick={() => {
                                setFilter('all');
                                setSearchInput('');
                                setTag('');
                            }}>{t('resetfilters')}</button>
                        ) : caps.canstart && !composing && (
                            <button type="button" className="btn btn-primary" onClick={() => setComposing(true)}>
                                <Icon name="plus" size={16} /> {t('startfirst')}
                            </button>
                        )}
                    </div>
                )}
            </div>

            {remaining > 0 && (
                <div className="hf-more">
                    <button type="button" className="btn btn-outline-secondary" onClick={loadMore} disabled={loadingMore}>
                        {loadingMore && <span className="spinner-border spinner-border-sm me-2" aria-hidden="true" />}
                        {t('loadmore', remaining)}
                    </button>
                </div>
            )}
        </div>
    );
}

export default function Forum(props: ForumProps) {
    return (
        <StringsProvider strings={props.strings}>
            <ForumView {...props} />
        </StringsProvider>
    );
}
