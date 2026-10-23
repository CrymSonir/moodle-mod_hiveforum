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
 * Build the reply tree from the flat list of posts returned by the server.
 *
 * @module     mod_hiveforum/thread/tree
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import type {Post} from '@moodle/lms/mod_hiveforum/common/types';

export interface PostNode {
    post: Post;
    children: PostNode[];
    /** Number of descendants (all levels). */
    descendants: number;
}

/**
 * @param posts flat list, any order
 * @returns the opening post and the tree of replies hanging below it
 */
export function buildTree(posts: Post[]): {root: Post | null; replies: PostNode[]} {
    const sorted = [...posts].sort((a, b) => a.timecreated - b.timecreated || a.id - b.id);
    const root = sorted.find((p) => p.isfirst) ?? null;
    const nodes = new Map<number, PostNode>();
    for (const post of sorted) {
        nodes.set(post.id, {post, children: [], descendants: 0});
    }

    const replies: PostNode[] = [];
    for (const post of sorted) {
        if (root && post.id === root.id) {
            continue;
        }
        const node = nodes.get(post.id)!;
        const parent = post.parentid && post.parentid !== root?.id ? nodes.get(post.parentid) : undefined;
        if (parent) {
            parent.children.push(node);
        } else {
            replies.push(node);
        }
    }

    const count = (node: PostNode): number => {
        node.descendants = node.children.reduce((sum, child) => sum + 1 + count(child), 0);
        return node.descendants;
    };
    replies.forEach(count);

    return {root, replies};
}
