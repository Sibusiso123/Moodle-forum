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
 * File for the settings of moodleforum.
 *
 * @package   mod_moodleforum
 * @copyright 2017 Kennet Winter <k_wint10@uni-muenster.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

    require_once($CFG->dirroot . '/mod/moodleforum/lib.php');

    // Number of discussions per page.
    $settings->add(new admin_setting_configtext('moodleforum/manydiscussions', get_string('manydiscussions', 'moodleforum'),
        get_string('configmanydiscussions', 'moodleforum'), 10, PARAM_INT));

    if (isset($CFG->maxbytes)) {
        $maxbytes = 0;
        if (get_config('moodleforum', 'maxbytes')) {
            $maxbytes = get_config('moodleforum', 'maxbytes');
        }
        $settings->add(new admin_setting_configselect('moodleforum/maxbytes', get_string('maxattachmentsize', 'moodleforum'),
            get_string('configmaxbytes', 'moodleforum'), 512000, get_max_upload_sizes($CFG->maxbytes, 0, 0, $maxbytes)));
    }

    // Default number of attachments allowed per post in all moodlevoerflows.
    $settings->add(new admin_setting_configtext('moodleforum/maxattachments', get_string('maxattachments', 'moodleforum'),
        get_string('configmaxattachments', 'moodleforum'), 9, PARAM_INT));

    $settings->add(new admin_setting_configtext('moodleforum/maxeditingtime', get_string('maxeditingtime', 'moodleforum'),
        get_string('configmaxeditingtime', 'moodleforum'), 3600, PARAM_INT));


    // Default read tracking settings.
    $options                                   = array();
    $options[moodleforum_TRACKING_OPTIONAL] = get_string('trackingoptional', 'moodleforum');
    $options[moodleforum_TRACKING_OFF]      = get_string('trackingoff', 'moodleforum');
    $options[moodleforum_TRACKING_FORCED]   = get_string('trackingon', 'moodleforum');
    $settings->add(new admin_setting_configselect('moodleforum/trackingtype', get_string('trackingtype', 'moodleforum'),
        get_string('configtrackingtype', 'moodleforum'), moodleforum_TRACKING_OPTIONAL, $options));

    // Should unread posts be tracked for each user?
    $settings->add(new admin_setting_configcheckbox('moodleforum/trackreadposts',
        get_string('trackmoodleforum', 'moodleforum'), get_string('configtrackmoodleforum', 'moodleforum'), 1));

    // Allow moodleforums to be set to forced read tracking.
    $settings->add(new admin_setting_configcheckbox('moodleforum/allowforcedreadtracking',
        get_string('forcedreadtracking', 'moodleforum'), get_string('configforcedreadtracking', 'moodleforum'), 0));

    // Default number of days that a post is considered old.
    $settings->add(new admin_setting_configtext('moodleforum/oldpostdays', get_string('oldpostdays', 'moodleforum'),
        get_string('configoldpostdays', 'moodleforum'), 14, PARAM_INT));

    // Default time (hour) to execute 'clean_read_records' cron.
    $options = array();
    for ($i = 0; $i < 24; $i++) {
        $options[$i] = sprintf("%02d", $i);
    }
    $settings->add(new admin_setting_configselect('moodleforum/cleanreadtime', get_string('cleanreadtime', 'moodleforum'),
        get_string('configcleanreadtime', 'moodleforum'), 2, $options));

    // Allow users to change their votes?
    $settings->add(new admin_setting_configcheckbox('moodleforum/allowratingchange',
        get_string('allowratingchange', 'moodleforum'), get_string('configallowratingchange', 'moodleforum'), 1));

    // Set scales for the reputation.

    // Votescale: How much reputation gives a vote for another post?
    $settings->add(new admin_setting_configtext('moodleforum/votescalevote', get_string('votescalevote', 'moodleforum'),
        get_string('configvotescalevote', 'moodleforum'), 1, PARAM_INT));

    // Votescale: How much reputation gives a post that has been downvoted?
    $settings->add(new admin_setting_configtext('moodleforum/votescaledownvote',
        get_string('votescaledownvote', 'moodleforum'), get_string('configvotescaledownvote', 'moodleforum'), -5, PARAM_INT));

    // Votescale: How much reputation gives a post that has been upvoted?
    $settings->add(new admin_setting_configtext('moodleforum/votescaleupvote', get_string('votescaleupvote', 'moodleforum'),
        get_string('configvotescaleupvote', 'moodleforum'), 5, PARAM_INT));

    // Votescale: How much reputation gives a post that is marked as solved.
    $settings->add(new admin_setting_configtext('moodleforum/votescalesolved', get_string('votescalesolved', 'moodleforum'),
        get_string('configvotescalesolved', 'moodleforum'), 30, PARAM_INT));

    // Votescale: How much reputation gives a post that is marked as helpful.
    $settings->add(new admin_setting_configtext('moodleforum/votescalehelpful', get_string('votescalehelpful', 'moodleforum'),
        get_string('configvotescalehelpful', 'moodleforum'), 15, PARAM_INT));

    // Number of discussions per page.
    $settings->add(new admin_setting_configtext('moodleforum/maxmailingtime', get_string('maxmailingtime', 'moodleforum'),
        get_string('configmaxmailingtime', 'moodleforum'), 48, PARAM_INT));


}