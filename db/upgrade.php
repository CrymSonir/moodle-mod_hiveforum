<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade steps for mod_hiveforum.
 * Add if ($oldversion < YYYYMMDDXX) { ... } blocks here as the schema evolves.
 */
function xmldb_hiveforum_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    // No upgrade steps yet - initial release.

    return true;
}
