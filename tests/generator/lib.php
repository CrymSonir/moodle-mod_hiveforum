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

/**
 * Data generator for mod_hiveforum.
 *
 * @package    mod_hiveforum
 * @copyright  2026 Hiveforum contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_hiveforum_generator extends testing_module_generator {
    /**
     * Create an instance.
     *
     * @param array|stdClass $record
     * @param array|null $options
     * @return stdClass
     */
    public function create_instance($record = null, ?array $options = null) {
        $record = (object) (array) $record;
        $record->allowreactions = $record->allowreactions ?? 1;
        $record->allowtags = $record->allowtags ?? 1;
        return parent::create_instance($record, (array) $options);
    }

    /**
     * Create a discussion with its opening post (raw records, works with any schema version).
     *
     * @param array $record hiveforum, userid, name, message, posttype, timecreated
     * @return stdClass discussion
     */
    public function create_discussion(array $record): stdClass {
        global $DB;
        $time = $record['timecreated'] ?? time();
        $d = (object) [
            'hiveforum' => $record['hiveforum'],
            'userid' => $record['userid'],
            'name' => $record['name'] ?? 'Discussion',
            'firstpost' => 0,
            'pinned' => $record['pinned'] ?? 0,
            'resolved' => 0,
            'posttype' => $record['posttype'] ?? 'discussion',
            'timecreated' => $time,
            'timemodified' => $time,
        ];
        if ($DB->get_manager()->field_exists('hiveforum_discussions', 'usermodified')) {
            $d->usermodified = $record['userid'];
        }
        $d->id = $DB->insert_record('hiveforum_discussions', $d);
        $postid = $DB->insert_record('hiveforum_posts', (object) [
            'discussion' => $d->id, 'parent' => 0, 'userid' => $record['userid'],
            'message' => $record['message'] ?? 'Hello', 'messageformat' => $record['messageformat'] ?? FORMAT_MARKDOWN,
            'isaccepted' => 0, 'timecreated' => $time, 'timemodified' => $time,
        ]);
        $DB->set_field('hiveforum_discussions', 'firstpost', $postid, ['id' => $d->id]);
        $d->firstpost = $postid;
        foreach ($record['tags'] ?? [] as $tag) {
            $DB->insert_record('hiveforum_tags', ['discussion' => $d->id, 'tag' => $tag]);
        }
        return $d;
    }

    /**
     * Create a reply (raw records).
     *
     * @param array $record discussion, userid, parent, message, timecreated
     * @return stdClass post
     */
    public function create_post(array $record): stdClass {
        global $DB;
        $time = $record['timecreated'] ?? time();
        $discussion = $DB->get_record('hiveforum_discussions', ['id' => $record['discussion']], '*', MUST_EXIST);
        $p = (object) [
            'discussion' => $discussion->id, 'parent' => $record['parent'] ?? $discussion->firstpost,
            'userid' => $record['userid'], 'message' => $record['message'] ?? 'Reply',
            'messageformat' => $record['messageformat'] ?? FORMAT_MARKDOWN, 'isaccepted' => 0,
            'timecreated' => $time, 'timemodified' => $time,
        ];
        $p->id = $DB->insert_record('hiveforum_posts', $p);
        $DB->set_field('hiveforum_discussions', 'timemodified', max($time, $discussion->timemodified), ['id' => $discussion->id]);
        return $p;
    }
}
