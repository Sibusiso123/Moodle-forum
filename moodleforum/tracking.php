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
 * Set tracking option for the moodleforum.
 *
 * @package   mod_moodleforum
 * @copyright 2017 Kennet Winter <k_wint10@uni-muenster.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Require needed files.
require_once("../../config.php");
require_once("locallib.php");

// Get submitted parameters.
$id         = required_param('id', PARAM_INT);                       // The moodleforum to track or untrack.
$returnpage = optional_param('returnpage', 'index.php', PARAM_FILE); // The page to return to.

// A session key is needed to change the tracking options.
require_sesskey();

// Retrieve the moodleforum instance to track or untrack.
if (!$moodleforum = $DB->get_record("moodleforum", array("id" => $id))) {
    print_error('invalidmoodleforumid', 'moodleforum');
}

// Retrieve the course of the instance.
if (!$course = $DB->get_record("course", array("id" => $moodleforum->course))) {
    print_error('invalidcoursemodule');
}

// Retrieve the course module of that course.
if (!$cm = get_coursemodule_from_instance("moodleforum", $moodleforum->id, $course->id)) {
    print_error('invalidcoursemodule');
}

// From now on the user needs to be logged in and enrolled.
require_login($course, false, $cm);

// Set the page to return to.
$url           = '/mod/moodleforum/' . $returnpage;
$params        = array('id' => $course->id, 'm' => $moodleforum->id);
$returnpageurl = new moodle_url($url, $params);
$returnto      = moodleforum_go_back_to($returnpageurl);

// Check whether the user can track the moodleforum instance.
$cantrack = \mod_moodleforum\readtracking::moodleforum_can_track_moodleforums($moodleforum);

// Do not continue if the user is not allowed to track the moodleforum. Redirect the user back.
if (!$cantrack) {
    redirect($returnto);
    exit;
}

// Create an info object.
$info                 = new stdClass();
$info->name           = fullname($USER);
$info->moodleforum = format_string($moodleforum->name);

// Set parameters for an event.
$eventparams = array(
    'context'       => context_module::instance($cm->id),
    'relateduserid' => $USER->id,
    'other'         => array('moodleforumid' => $moodleforum->id),
);

// Check whether the moodleforum is tracked.
$istracked = \mod_moodleforum\readtracking::moodleforum_is_tracked($moodleforum);
if ($istracked) {
    // The moodleforum instance is tracked. The next step is to untrack.

    // Untrack the moodleforum instance.
    if (\mod_moodleforum\readtracking::moodleforum_stop_tracking($moodleforum->id)) {
        // Successful stopped to track.

        // Trigger the readtracking disabled event.
        $event = \mod_moodleforum\event\readtracking_disabled::create($eventparams);
        $event->trigger();

        // Redirect the user back to where he is coming from.
        redirect($returnpageurl, get_string('nownottracking', 'moodleforum', $info), 1);

    } else {
        // The insertion failed.

        // Print an error message.
        print_error('cannottrack', 'moodleforum', get_local_referer(false));
    }

} else {
    // The moodleforum instance is not tracked. The next step is to track.

    // Track the moodleforum instance.
    if (\mod_moodleforum\readtracking::moodleforum_start_tracking($moodleforum->id)) {
        // Successfully started to track.

        // Trigger the readtracking event.
        $event = \mod_moodleforum\event\readtracking_enabled::create($eventparams);
        $event->trigger();

        // Redirect the user back to where he is coming from.
        redirect($returnto, get_string('nowtracking', 'moodleforum', $info), 1);

    } else {
        // The deletion failed.

        // Print an error message.
        print_error('cannottrack', 'moodleforum', get_local_referer(false));
    }
}