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
 * Data-access layer: every web service call of the plugin lives here.
 *
 * @module     mod_hiveforum/common/repository
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {fetchOne} from '@moodle/lms/core/ajax';
import type {
    Counts, Discussion, DiscussionSummary, Filter, Post, Reaction, ReactionType, Sort,
} from '@moodle/lms/mod_hiveforum/common/types';

export interface DiscussionListQuery {
    cmid: number;
    filter: Filter;
    search: string;
    tag: string;
    sort: Sort;
    page: number;
}

export interface DiscussionListResponse {
    discussions: DiscussionSummary[];
    total: number;
    perpage: number;
    counts: Counts;
}

export const getDiscussions = (query: DiscussionListQuery): Promise<DiscussionListResponse> =>
    fetchOne<DiscussionListResponse>({methodname: 'mod_hiveforum_get_discussions', args: {...query}});

export const getDiscussion = (discussionid: number, markread = true): Promise<{discussion: Discussion; posts: Post[]}> =>
    fetchOne({methodname: 'mod_hiveforum_get_discussion', args: {discussionid, markread}});

export const createDiscussion = (args: {
    cmid: number;
    name: string;
    message: string;
    isquestion: boolean;
    tags: string[];
}): Promise<{discussionid: number; url: string}> =>
    fetchOne({methodname: 'mod_hiveforum_create_discussion', args});

export const updateDiscussion = (discussionid: number, name: string, tags: string[]): Promise<{discussion: Discussion}> =>
    fetchOne({methodname: 'mod_hiveforum_update_discussion', args: {discussionid, name, tags}});

export const setDiscussionState = (
    discussionid: number,
    state: 'pinned' | 'resolved' | 'following',
    value: boolean,
): Promise<{discussion: Discussion}> =>
    fetchOne({methodname: 'mod_hiveforum_set_discussion_state', args: {discussionid, state, value}});

export const addPost = (discussionid: number, parentid: number, message: string): Promise<{post: Post}> =>
    fetchOne({methodname: 'mod_hiveforum_add_post', args: {discussionid, parentid, message}});

export const updatePost = (postid: number, message: string): Promise<{post: Post}> =>
    fetchOne({methodname: 'mod_hiveforum_update_post', args: {postid, message}});

export const deletePost = (postid: number): Promise<{discussiondeleted: boolean; forumurl: string}> =>
    fetchOne({methodname: 'mod_hiveforum_delete_post', args: {postid}});

export const toggleReaction = (postid: number, reactiontype: ReactionType): Promise<{reactions: Reaction[]}> =>
    fetchOne({methodname: 'mod_hiveforum_toggle_reaction', args: {postid, reactiontype}});

export const toggleAccepted = (postid: number): Promise<{accepted: boolean; resolved: boolean}> =>
    fetchOne({methodname: 'mod_hiveforum_toggle_accepted', args: {postid}});

export const markAllRead = (cmid: number): Promise<{success: boolean}> =>
    fetchOne({methodname: 'mod_hiveforum_mark_all_read', args: {cmid}});
