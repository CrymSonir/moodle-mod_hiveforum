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
 * Shared state and actions of the thread view.
 *
 * @module     mod_hiveforum/thread/context
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {createContext, useContext} from 'react';
import type {Capabilities, Discussion, Post, User} from '@moodle/lms/mod_hiveforum/common/types';

export interface ThreadActions {
    discussion: Discussion;
    capabilities: Capabilities;
    currentuser: User | null;
    replyingTo: number | null;
    editing: number | null;
    highlighted: number | null;
    setReplyingTo: (postid: number | null) => void;
    setEditing: (postid: number | null) => void;
    reply: (parentid: number, message: string) => Promise<void>;
    update: (post: Post, message: string) => Promise<void>;
    remove: (post: Post) => void;
    accept: (post: Post) => void;
    replacePost: (post: Post) => void;
}

export const ThreadContext = createContext<ThreadActions | null>(null);

export const useThread = (): ThreadActions => {
    const value = useContext(ThreadContext);
    if (!value) {
        throw new Error('useThread() outside of <ThreadContext.Provider>');
    }
    return value;
};
