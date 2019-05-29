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
 * This file keeps track of upgrades to the moodleforum module
 *
 * Sometimes, changes between versions involve alterations to database
 * structures and other major things that may break installations. The upgrade
 * function in this file will attempt to perform all the necessary actions to
 * upgrade your older installation to the current version. If there's something
 * it cannot do itself, it will tell you what you need to do.  The commands in
 * here will all be database-neutral, using the functions defined in DLL libraries.
 *
 * @package   mod_moodleforum
 * @copyright 2017 Kennet Winter <k_wint10@uni-muenster.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute moodleforum upgrade from the given old version
 *
 * @param int $oldversion
 *
 * @return bool
 */
function xmldb_moodleforum_upgrade($oldversion) {
    global $CFG;

    if ($oldversion < 2017110713) {
        // Migrate config.
        set_config('manydiscussions', $CFG->moodleforum_manydiscussions, 'moodleforum');
        set_config('maxbytes', $CFG->moodleforum_maxbytes, 'moodleforum');
        set_config('maxattachments', $CFG->moodleforum_maxattachments, 'moodleforum');
        set_config('maxeditingtime', $CFG->moodleforum_maxeditingtime, 'moodleforum');
        set_config('trackingtype', $CFG->moodleforum_trackingtype, 'moodleforum');
        set_config('trackreadposts', $CFG->moodleforum_trackreadposts, 'moodleforum');
        set_config('allowforcedreadtracking', $CFG->moodleforum_allowforcedreadtracking, 'moodleforum');
        set_config('oldpostdays', $CFG->moodleforum_oldpostdays, 'moodleforum');
        set_config('cleanreadtime', $CFG->moodleforum_cleanreadtime, 'moodleforum');
        set_config('allowratingchange', $CFG->moodleforum_allowratingchange, 'moodleforum');
        set_config('votescalevote', $CFG->moodleforum_votescalevote, 'moodleforum');
        set_config('votescaledownvote', $CFG->moodleforum_votescaledownvote, 'moodleforum');
        set_config('votescaleupvote', $CFG->moodleforum_votescaleupvote, 'moodleforum');
        set_config('votescalesolved', $CFG->moodleforum_votescalesolved, 'moodleforum');
        set_config('votescalehelpful', $CFG->moodleforum_votescalehelpful, 'moodleforum');
        set_config('maxmailingtime', $CFG->moodleforum_maxmailingtime, 'moodleforum');

        // Delete old config.
        set_config('moodleforum_manydiscussions', null, 'moodleforum');
        set_config('moodleforum_maxbytes', null, 'moodleforum');
        set_config('moodleforum_maxattachments', null, 'moodleforum');
        set_config('moodleforum_maxeditingtime', null, 'moodleforum');
        set_config('moodleforum_trackingtype', null, 'moodleforum');
        set_config('moodleforum_trackreadposts', null, 'moodleforum');
        set_config('moodleforum_allowforcedreadtracking', null, 'moodleforum');
        set_config('moodleforum_oldpostdays', null, 'moodleforum');
        set_config('moodleforum_cleanreadtime', null, 'moodleforum');
        set_config('moodleforum_allowratingchange', null, 'moodleforum');
        set_config('moodleforum_votescalevote', null, 'moodleforum');
        set_config('moodleforum_votescaledownvote', null, 'moodleforum');
        set_config('moodleforum_votescaleupvote', null, 'moodleforum');
        set_config('moodleforum_votescalesolved', null, 'moodleforum');
        set_config('moodleforum_votescalehelpful', null, 'moodleforum');
        set_config('moodleforum_maxmailingtime', null, 'moodleforum');

        // Opencast savepoint reached.
        upgrade_mod_savepoint(true, 2017110713, 'moodleforum');
    }
    return true;
}
