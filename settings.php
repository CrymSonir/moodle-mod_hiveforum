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
 * Site administration settings.
 *
 * @package    mod_hiveforum
 * @copyright  2026 Hiveforum contributors
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    // Global switch: when off, no Hiveforum notification is sent on the whole site.
    $settings->add(new admin_setting_configcheckbox(
        'mod_hiveforum/enablenotifications',
        new lang_string('enablenotifications', 'hiveforum'),
        new lang_string('enablenotifications_desc', 'hiveforum'),
        1
    ));

    $settings->add(new admin_setting_configselect(
        'mod_hiveforum/defaultsubscriptionmode',
        new lang_string('defaultsubscriptionmode', 'hiveforum'),
        new lang_string('defaultsubscriptionmode_desc', 'hiveforum'),
        0,
        [
            0 => new lang_string('subscriptionoptional', 'hiveforum'),
            1 => new lang_string('subscriptionforced', 'hiveforum'),
            2 => new lang_string('subscriptionauto', 'hiveforum'),
            3 => new lang_string('subscriptiondisabled', 'hiveforum'),
        ]
    ));
}
