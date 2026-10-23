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
 * Shared types. They mirror \mod_hiveforum\local\exporter and \mod_hiveforum\external\structures.
 *
 * @module     mod_hiveforum/common/types
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

export type ReactionType = 'like' | 'love' | 'idea';
export type Filter = 'all' | 'unread' | 'following' | 'questions' | 'mine';
export type Sort = 'recent' | 'newest' | 'popular';
export type Strings = Record<string, string>;

export interface User {
    id: number;
    fullname: string;
    initials: string;
    avatarurl: string;
    profileurl: string;
    isstaff: boolean;
    streak: number;
}

export interface Reaction {
    type: ReactionType;
    count: number;
    active: boolean;
}

export interface DiscussionSummary {
    id: number;
    name: string;
    excerpt: string;
    author: User;
    lastauthor?: User | null;
    participants: User[];
    isquestion: boolean;
    pinned: boolean;
    resolved: boolean;
    following: boolean;
    unread: boolean;
    replycount: number;
    reactions: Reaction[];
    tags: string[];
    timecreated: number;
    timemodified: number;
    url: string;
}

export interface Discussion {
    id: number;
    name: string;
    rawname: string;
    author: User;
    isquestion: boolean;
    pinned: boolean;
    resolved: boolean;
    following: boolean;
    tags: string[];
    firstpostid: number;
    timecreated: number;
    timemodified: number;
    canpin: boolean;
    canresolve: boolean;
    canfollow: boolean;
    canrename: boolean;
}

export interface Post {
    id: number;
    parentid: number;
    author: User;
    message: string;
    rawmessage: string;
    messageformat: number;
    isfirst: boolean;
    isaccepted: boolean;
    edited: boolean;
    timecreated: number;
    timemodified: number;
    reactions: Reaction[];
    canedit: boolean;
    candelete: boolean;
    canaccept: boolean;
    editurl: string;
}

export interface Capabilities {
    canstart: boolean;
    canreply: boolean;
    canreact: boolean;
    canmoderate: boolean;
    canpin: boolean;
    canfollow: boolean;
    allowtags: boolean;
    allowreactions: boolean;
}

export interface Counts {
    all: number;
    unread: number;
    following: number;
    questions: number;
    mine: number;
}

export interface Stats {
    discussions: number;
    posts: number;
    participants: number;
}

export interface PopularTag {
    name: string;
    count: number;
}

/** Props of the list page, built by \mod_hiveforum\output\forum_page. */
export interface ForumProps {
    cmid: number;
    forumname: string;
    filter: Filter;
    search: string;
    tag: string;
    sort: Sort;
    discussions: DiscussionSummary[];
    total: number;
    perpage: number;
    counts: Counts;
    stats: Stats;
    populartags: PopularTag[];
    capabilities: Capabilities;
    currentuser: User | null;
    fulleditorurl: string;
    strings: Strings;
}

/** Props of the thread page, built by \mod_hiveforum\output\thread_page. */
export interface ThreadProps {
    cmid: number;
    forumname: string;
    forumurl: string;
    discussion: Discussion;
    posts: Post[];
    capabilities: Capabilities;
    currentuser: User | null;
    pollinterval: number;
    strings: Strings;
}

/** MoodleFormat constant for markdown. */
export const FORMAT_MARKDOWN = 4;

/** Reaction types, in display order, with their emoji. */
export const REACTIONS: ReadonlyArray<{type: ReactionType; emoji: string}> = [
    {type: 'like', emoji: '👍'},
    {type: 'love', emoji: '❤️'},
    {type: 'idea', emoji: '💡'},
];
