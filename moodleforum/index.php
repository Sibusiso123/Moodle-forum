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
 * moodleforum index.
 *
 * @package   mod_moodleforum
 * @copyright 2017 Kennet Winter <k_wint10@uni-muenster.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Require needed files.
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once($CFG->dirroot . '/course/lib.php');

// Fetch submitted parameters.
$id        = required_param('id', PARAM_INT);
$subscribe = optional_param('subscribe', null, PARAM_INT);

// Set an url to go back to the page.
$url = new moodle_url('/mod/moodleforum/index.php', array('id' => $id));

// Check whether the subscription parameter was set.
if ($subscribe !== null) {
    require_sesskey();
    $url->param('subscribe', $subscribe);
}

// The the url of this page.
$PAGE->set_url($url);

// Check if the id is related to a valid course.
if (!$course = $DB->get_record('course', array('id' => $id))) {
    print_error('invalidcourseid');
}

// From now on, the user must be enrolled to a course.
require_course_login($course);
$PAGE->set_pagelayout('incourse');
$coursecontext = context_course::instance($course->id);
unset($SESSION->fromdiscussion);

// Trigger the course module instace lise viewed evewnt.
$params = array(
    'context' => context_course::instance($course->id)
);
$event  = \mod_moodleforum\event\course_module_instance_list_viewed::create($params);
$event->add_record_snapshot('course', $course);
$event->trigger();

// Cache some strings.
$string                           = array();
$string['moodleforum']         = get_string('moodleforum', 'moodleforum');
$string['moodleforums']        = get_string('moodleforums', 'moodleforum');
$string['modulenameplural']       = get_string('modulenameplural', 'moodleforum');
$string['description']            = get_string('description');
$string['discussions']            = get_string('discussions', 'moodleforum');
$string['subscribed']             = get_string('subscribed', 'moodleforum');
$string['unreadposts']            = get_string('unreadposts', 'moodleforum');
$string['tracking']               = get_string('tracking', 'moodleforum');
$string['markallread']            = get_string('markallread', 'moodleforum');
$string['trackmoodleforum']    = get_string('trackmoodleforum', 'moodleforum');
$string['notrackmoodleforum']  = get_string('notrackmoodleforum', 'moodleforum');
$string['subscribe']              = get_string('subscribe', 'moodleforum');
$string['unsubscribe']            = get_string('unsubscribe', 'moodleforum');
$string['subscribeenrolledonly']  = get_string('subscribeenrolledonly', 'moodleforum');
$string['allsubscribe']           = get_string('allsubscribe', 'moodleforum');
$string['allunsubscribe']         = get_string('allunsubscribe', 'moodleforum');
$string['generalmoodleforums'] = get_string('generalmoodleforums', 'moodleforum');
$string['yes']                    = get_string('yes');
$string['no']                     = get_string('no');

// Begin to print a table for the general area.
$generaltable        = new html_table();
$generaltable->head  = array($string['moodleforum'], $string['description'], $string['discussions']);
$generaltable->align = array('left', 'left', 'center');

// Check whether moodleforums can be tracked.
$cantrack = \mod_moodleforum\readtracking::moodleforum_can_track_moodleforums();
if ($cantrack) {
    $untracked = \mod_moodleforum\readtracking::get_untracked_moodleforums($USER->id, $course->id);

    // Add information about the unread posts to the table.
    $generaltable->head[]  = $string['unreadposts'];
    $generaltable->align[] = 'center';

    // Add information about the tracking to the table.
    $generaltable->head[]  = $string['tracking'];
    $generaltable->align[] = 'center';
}

// Fill the subscription cache for this course and user combination.
\mod_moodleforum\subscriptions::fill_subscription_cache_for_course($course->id, $USER->id);

// Retrieve the sections of the course.
$usesections = course_format_uses_sections($course->format);

// Initiate tables and variables.
$table                   = new html_table();
$generalmoodleforums  = array();
$modinfo                 = get_fast_modinfo($course);
$showsubscriptioncolumns = false;

// Parse and organize all moodleforums.
$sql             = "SELECT m.*
          FROM {moodleforum} m
         WHERE m.course = ?";
$moodleforums = $DB->get_records_sql($sql, array($course->id));

// Loop through allmoodleforums.
foreach ($modinfo->get_instances_of('moodleforum') as $moodleforumid => $cm) {

    // Check whether the user can see the instance.
    if (!$cm->uservisible OR !isset($moodleforums[$moodleforumid])) {
        continue;
    }

    // Get the current moodleforum instance and the context.
    $moodleforum = $moodleforums[$moodleforumid];
    $modulecontext  = context_module::instance($cm->id);

    // Check whether the user can see the list.
    if (!has_capability('mod/moodleforum:viewdiscussion', $modulecontext)) {
        continue;
    }

    // Get information about the subscription state.
    $cansubscribe                 = \mod_moodleforum\subscriptions::is_subscribable($moodleforum);
    $moodleforum->cansubscribe = $cansubscribe || has_capability('mod/moodleforum:managesubscriptions', $modulecontext);
    $moodleforum->issubscribed = \mod_moodleforum\subscriptions::is_subscribed($USER->id, $moodleforum, null);
    $showsubscriptioncolumns      = $showsubscriptioncolumns || $moodleforum->issubscribed || $moodleforum->cansubscribe;

    // Add the moodleforum to the cache.
    $generalmoodleforums[$moodleforumid] = $moodleforum;
}

// Check whether the subscription columns need to be displayed.
if ($showsubscriptioncolumns) {
    // The user can subscribe to at least one moodleforum.

    // Add the subscription state to the table.
    $generaltable->head[] = $string['subscribed'];
}

// Handle course wide subscriptions or unsubscriptions if requested.
if (!is_null($subscribe)) {

    // Catch guests and not subscribable moodleforums.
    if (isguestuser() OR !$showsubscriptioncolumns) {

        // Redirect the user back.
        $url          = new moodle_url('/mod/moodleforum/index.php', array('id' => $id));
        $notification = \core\output\notification::NOTIFY_ERROR;
        redirect($url, $string['subscribeenrolledonly'], null, $notification);
    }

    // Loop through all moodleforums.
    foreach ($modinfo->get_instances_of('moodleforum') as $moodleforumid => $cm) {

        // Initiate variables.
        $moodleforum = $moodleforums[$moodleforumid];
        $modulecontext  = context_module::instance($cm->id);
        $cansub         = false;

        // Check capabilities.
        $cap['viewdiscussion']      = has_capability('mod/moodleforum:viewdiscussion', $modulecontext);
        $cap['managesubscriptions'] = has_capability('mod/moodleforum:managesubscriptions', $modulecontext);
        $cap['manageactivities']    = has_capability('moodle/course:manageactivities', $coursecontext, $USER->id);

        // Check whether the user can view the discussions.
        if ($cap['viewdiscussion']) {
            $cansub = true;
        }

        // Check whether the user can manage subscriptions.
        if ($cansub AND $cm->visible == 0 AND !$cap['managesubscriptions']) {
            $cansub = false;
        }

        // Check the subscription state.
        $forcesubscribed = \mod_moodleforum\subscriptions::is_forcesubscribed($moodleforum);
        if (!$forcesubscribed) {

            // Check the current state.
            $subscribed   = \mod_moodleforum\subscriptions::is_subscribed($USER->id, $moodleforum, null);
            $subscribable = \mod_moodleforum\subscriptions::is_subscribable($moodleforum);

            // Check whether to subscribe or unsubscribe the user.
            if ($cap['manageactivities'] OR $subscribable AND $subscribe AND !$subscribed AND $cansub) {
                \mod_moodleforum\subscriptions::subscribe_user($USER->id, $moodleforum, $modulecontext, true);
            } else {
                \mod_moodleforum\subscriptions::unsubscribe_user($USER->id, $moodleforum, $modulecontext, true);
            }
        }
    }

    // Create an url to return the user back to.
    $url      = new moodle_url('/mod/moodleforum/index.php', array('id' => $id));
    $returnto = moodleforum_go_back_to($url);

    // Prepare the message to be displayed.
    $shortname    = format_string($course->shortname, true, array('context' => context_course::instance($course->id)));
    $notification = \core\output\notification::NOTIFY_SUCCESS;

    // Redirect the user depending on the subscription state.
    if ($subscribe) {
        redirect($returnto, get_string('nowallsubscribed', 'moodleforum', $shortname), null, $notification);
    } else {
        redirect($returnto, get_string('nowallunsubscribed', 'moodleforum', $shortname), null, $notification);
    }
}

// Check if there are moodleforums.
if ($generalmoodleforums) {

    // Loop through all of the moodleforums.
    foreach ($generalmoodleforums as $moodleforum) {

        // Retrieve the contexts.
        $cm            = $modinfo->instances['moodleforum'][$moodleforum->id];
        $modulecontext = context_module::instance($cm->id);

        // Count the discussions within the moodleforum.
        $count = moodleforum_count_discussions($moodleforum, $course);

        // Check whether the user can track the moodleforum.
        if ($cantrack) {

            // Check whether the tracking is disabled.
            if ($moodleforum->trackingtype == moodleforum_TRACKING_OFF) {
                $unreadlink  = '-';
                $trackedlink = '-';
            } else {
                // The moodleforum can be tracked.

                // Check if this moodleforum is manually untracked.
                if (isset($untracked[$moodleforum->id])) {
                    $unreadlink = '-';

                } else if ($unread = \mod_moodleforum\readtracking::moodleforum_count_unread_posts_moodleforum($cm,
                    $course)
                ) {
                    // There are unread posts in the moodleforum instance.

                    // Create a string to be displayed.
                    $unreadlink = '<span class="unread">';
                    $unreadlink .= '<a href="view.php?m=' . $moodleforum->id . '">' . $unread . '</a>';
                    $unreadlink .= '<a title="' . $string['markallread'] . '" href="markposts.php?m=' . $moodleforum->id .
                        '&amp;mark=read&amp;sesskey=' . sesskey() . '">';
                    $unreadlink .= '<img src="' . $OUTPUT->image_url('t/markasread') . '" alt="' .
                        $string['markallread'] . '" class="iconsmall" />';
                    $unreadlink .= '</a>';
                    $unreadlink .= '</span>';

                } else {
                    // There are no unread messages for this moodleforum instance.

                    // Create a string to be displayed.
                    $unreadlink = '<span class="read">0</span>';
                }

                // Check whether the moodleforum instance can be tracked.
                $isforced = $moodleforum->trackingtype == moodleforum_TRACKING_FORCED;
                if ($isforced AND (get_config('moodleforum', 'allowforcedreadtracking'))) {
                    // Tracking is set to forced.

                    // Define the string.
                    $trackedlink = $string['yes'];

                } else if ($moodleforum->trackingtype === moodleforum_TRACKING_OFF) {
                    // Tracking is set to off.

                    // Define the string.
                    $trackedlink = '-';

                } else {
                    // Tracking is optional.

                    // Define the url the button is linked to.
                    $trackingurlparams = array('id' => $moodleforum->id, 'sesskey' => sesskey());
                    $trackingurl       = new moodle_url('/mod/moodleforum/tracking.php', $trackingurlparams);

                    // Check whether the moodleforum instance is tracked.
                    if (!isset($untracked[$moodleforum->id])) {
                        $trackingparam = array('title' => $string['notrackmoodleforum']);
                        $trackedlink   = $OUTPUT->single_button($trackingurl, $string['yes'], 'post', $trackingparam);
                    } else {
                        $trackingparam = array('title' => $string['trackmoodleforum']);
                        $trackedlink   = $OUTPUT->single_button($trackingurl, $string['no'], 'post', $trackingparam);
                    }
                }
            }
        }

        // Get information about the moodleforum instance.
        $moodleforum->intro = shorten_text(format_module_intro('moodleforum', $moodleforum, $cm->id), 300);
        $moodleforumname    = format_string($moodleforum->name, true);

        // Check if the context module is visible.
        if ($cm->visible) {
            $style = '';
        } else {
            $style = 'class="dimmed"';
        }

        // Create links to the moodleforum and the discussion.
        $moodleforumlink = "<a href=\"view.php?m=$moodleforum->id\" $style>"
            . format_string($moodleforum->name, true) . '</a>';
        $discussionlink     = "<a href=\"view.php?m=$moodleforum->id\" $style>" . $count . "</a>";

        // Create rows.
        $row = array($moodleforumlink, $moodleforum->intro, $discussionlink);

        // Add the tracking information to the rows.
        if ($cantrack) {
            $row[] = $unreadlink;
            $row[] = $trackedlink;
        }

        // Add the subscription information to the rows.
        if ($showsubscriptioncolumns) {

            // Set options to create the subscription link.
            $suboptions = array(
                'subscribed'      => $string['yes'],
                'unsubscribed'    => $string['no'],
                'forcesubscribed' => $string['yes'],
                'cantsubscribe'   => '-',
            );

            // Add the subscription link to the row.
            $row[] = \mod_moodleforum\subscriptions::moodleforum_get_subscribe_link($moodleforum,
                $modulecontext, $suboptions);
        }

        // Add the rows to the table.
        $generaltable->data[] = $row;
    }
}

// Output the page.
$PAGE->navbar->add($string['moodleforums']);
$PAGE->set_title($course->shortname . ': ' . $string['moodleforums']);
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();

// Show the subscribe all option only to non-guest and enrolled users.
if (!isguestuser() AND isloggedin() AND $showsubscriptioncolumns) {

    // Create a box.
    echo $OUTPUT->box_start('subscription');

    // Create the subscription link.
    $urlparams        = array('id' => $course->id, 'sesskey' => sesskey());
    $subscriptionlink = new moodle_url('/mod/moodleforum/index.php', $urlparams);

    // Give the option to subscribe to all.
    $subscriptionlink->param('subscribe', 1);
    $htmllink = html_writer::link($subscriptionlink, $string['allsubscribe']);
    echo html_writer::tag('div', $htmllink, ['class' => 'helplink']);

    // Give the option to unsubscribe from all.
    $subscriptionlink->param('subscribe', 0);
    $htmllink = html_writer::link($subscriptionlink, $string['allunsubscribe']);
    echo html_writer::tag('div', $htmllink, ['class' => 'helplink']);

    // Print the box.
    echo $OUTPUT->box_end();
    echo $OUTPUT->box('&nbsp;', 'clearer');
}

// Print the moodleforums.
if ($generalmoodleforums) {
    echo $OUTPUT->heading($string['generalmoodleforums'], 2);
    echo html_writer::table($generaltable);
}

// Print the pages footer.
echo $OUTPUT->footer();