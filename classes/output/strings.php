<?php
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

namespace mod_hiveforum\output;

/**
 * Language strings sent to the React UI with the initial props.
 *
 * Sending them up front avoids a flash of untranslated text and a string round-trip.
 * Placeholders ({$a}, {$a->x}) are kept raw and replaced in the browser.
 *
 * @package    mod_hiveforum
 * @copyright  2026 Hiveforum contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class strings {
    /** @var string[] Keys of mod_hiveforum used by the UI (see js/esm/src). */
    public const KEYS = [
        'accept', 'acceptedanswer', 'acceptedanswerby', 'addreply', 'addtags', 'allmarkedread', 'answeraccepted',
        'answerplaceholder', 'answerunaccepted', 'cancel', 'clearsearch', 'close', 'copylink', 'delete', 'deleteconfirm',
        'deletediscussion', 'deletediscussionconfirm', 'deletepost', 'discussiontitle', 'discussiontitleplaceholder',
        'discussiontype', 'edit', 'edited', 'editpost', 'emptydiscussions', 'emptydiscussions_help', 'emptyresults',
        'emptyresults_help', 'errornameempty', 'filter_all', 'filter_following', 'filter_mine', 'filter_questions',
        'filter_unread', 'filters', 'follow', 'followed', 'followedtoast', 'following_btn', 'format_bold', 'format_code',
        'format_italic', 'format_link', 'format_linktext', 'format_list', 'format_quote', 'formatting', 'fulleditor',
        'hideonereply', 'hidereplies', 'lastreplyby', 'linkcopied', 'loadmore', 'markallread', 'markdownhint',
        'markresolved', 'markunresolved', 'message', 'messageplaceholder', 'newdiscussion', 'newreplymany', 'newreplyone',
        'noreplies', 'noreplies_help', 'onereply', 'participantsnames', 'pindiscussion', 'pinned', 'pinnedtoast',
        'populartags', 'postdeleted', 'postupdated', 'publishdiscussion', 'question', 'questiontitleplaceholder',
        'reaction_idea', 'reaction_like', 'reaction_love', 'reactioncount', 'reactions', 'reactionstotal', 'removetag',
        'renamediscussion', 'reopenedtoast', 'repliescount', 'reply', 'replyplaceholder', 'replyto', 'resetfilters',
        'resolved', 'resolvedtoast', 'resultsmany', 'resultsnone', 'resultsone', 'savechanges', 'searchdiscussions',
        'sendshortcut', 'showonereply', 'showreplies', 'sort_newest', 'sort_popular', 'sort_recent', 'sortby', 'staff',
        'startedby', 'startfirst', 'stat_discussion', 'stat_discussion_n', 'stat_discussions', 'stat_discussions_n',
        'stat_message', 'stat_messages', 'stat_participant', 'stat_participants', 'streakweeks', 'typediscussion',
        'typediscussion_help', 'typequestion', 'typequestion_help', 'unaccept', 'unfollowed', 'unpindiscussion',
        'unpinned', 'unreadactivity',
    ];

    /**
     * Raw strings (placeholders untouched) for the current language.
     *
     * @return array<string, string>
     */
    public static function get(): array {
        $manager = get_string_manager();
        $all = $manager->load_component_strings('mod_hiveforum', current_language());
        $result = [];
        foreach (self::KEYS as $key) {
            $result[$key] = $all[$key] ?? $key;
        }
        return $result;
    }
}
