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
 * Upgrade steps for mod_hiveforum.
 *
 * @package    mod_hiveforum
 * @copyright  2026 Hiveforum contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Execute mod_hiveforum upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_hiveforum_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2026102300) {
        // Discussions: author of the most recent post.
        $table = new xmldb_table('hiveforum_discussions');
        $field = new xmldb_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'posttype');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Posts: index on parent for thread building / cascading deletes.
        $table = new xmldb_table('hiveforum_posts');
        $index = new xmldb_index('parent', XMLDB_INDEX_NOTUNIQUE, ['parent']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Streaks: keep the best streak ever reached.
        $table = new xmldb_table('hiveforum_streaks');
        $field = new xmldb_field('beststreak', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0', 'currentstreak');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
            $DB->execute('UPDATE {hiveforum_streaks} SET beststreak = currentstreak');
        }

        // Subscriptions ("following").
        $table = new xmldb_table('hiveforum_subscriptions');
        if (!$dbman->table_exists($table)) {
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
            $table->add_field('discussion', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('userid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '0');
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $table->add_key('discussion', XMLDB_KEY_FOREIGN, ['discussion'], 'hiveforum_discussions', ['id']);
            $table->add_key('userid', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);
            $table->add_index('discussion_user', XMLDB_INDEX_UNIQUE, ['discussion', 'userid']);
            $dbman->create_table($table);
        }

        // Backfill usermodified / timemodified from existing posts, and auto-follow for authors.
        $discussions = $DB->get_recordset('hiveforum_discussions', null, '', 'id, userid');
        foreach ($discussions as $d) {
            $last = $DB->get_records('hiveforum_posts', ['discussion' => $d->id], 'timecreated DESC, id DESC', 'id, userid, timecreated', 0, 1);
            if ($last) {
                $last = reset($last);
                $DB->update_record('hiveforum_discussions', (object) [
                    'id' => $d->id,
                    'usermodified' => $last->userid,
                    'timemodified' => $last->timecreated,
                ]);
            }
            $participants = $DB->get_fieldset_select('hiveforum_posts', 'DISTINCT userid', 'discussion = ?', [$d->id]);
            foreach (array_unique(array_merge([$d->userid], $participants)) as $userid) {
                if (!$DB->record_exists('hiveforum_subscriptions', ['discussion' => $d->id, 'userid' => $userid])) {
                    $DB->insert_record('hiveforum_subscriptions', [
                        'discussion' => $d->id, 'userid' => $userid, 'timecreated' => time(),
                    ]);
                }
            }
        }
        $discussions->close();

        upgrade_mod_savepoint(true, 2026102300, 'hiveforum');
    }

    return true;
}
