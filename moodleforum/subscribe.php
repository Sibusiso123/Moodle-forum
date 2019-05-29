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
 * Subscribe to or unsubscribe from a moodleforum or manage moodleforum subscription mode.
 *
 * This script can be used by either individual users to subscribe to or
 * unsubscribe from a moodleforum (no 'mode' param provided), or by moodleforum managers
 * to control the subscription mode (by 'mode' param).
 *
 * @package   mod_moodleforum
 * @copyright 2017 Kennet Winter <k_wint10@uni-muenster.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/locallib.php');

// Define required and optional params.
$id           = required_param('id', PARAM_INT);             // The moodleforum to set subscription on.
$mode         = optional_param('mode', null, PARAM_INT);     // The moodleforum's subscription mode.
$user         = optional_param('user', 0, PARAM_INT);        // The userid of the user to subscribe, defaults to $USER.
$discussionid = optional_param('d', null, PARAM_INT);        // The discussionid to subscribe.
$sesskey      = optional_param('sesskey', null, PARAM_RAW);
$returnurl    = optional_param('returnurl', null, PARAM_RAW);

// Set the url to return to the same action.
$url = new moodle_url('/mod/moodleforum/subscribe.php', array('id' => $id));
if (!is_null($mode)) {
    $url->param('mode', $mode);
}
if ($user !== 0) {
    $url->param('user', $user);
}
if (!is_null($sesskey)) {
    $url->param('sesskey', $sesskey);
}
if (!is_null($discussionid)) {
    $url->param('d', $discussionid);
    if (!$discussion = $DB->get_record('moodleforum_discussions', array('id' => $discussionid, 'moodleforum' => $id))) {
        print_error('invaliddiscussionid', 'moodleforum');
    }
}

// Set the pages URL.
$PAGE->set_url($url);

// Get all necessary objects.
$moodleforum = $DB->get_record('moodleforum', array('id' => $id), '*', MUST_EXIST);
$course         = $DB->get_record('course', array('id' => $moodleforum->course), '*', MUST_EXIST);
$cm             = get_coursemodule_from_instance('moodleforum', $moodleforum->id, $course->id, false, MUST_EXIST);
$context        = context_module::instance($cm->id);

// Define variables.
$notify                             = array();
$notify['success']                  = \core\output\notification::NOTIFY_SUCCESS;
$notify['error']                    = \core\output\notification::NOTIFY_ERROR;
$strings                            = array();
$strings['subscribeenrolledonly']   = get_string('subscribeenrolledonly', 'moodleforum');
$strings['everyonecannowchoose']    = get_string('everyonecannowchoose', 'moodleforum');
$strings['everyoneisnowsubscribed'] = get_string('everyoneisnowsubscribed', 'moodleforum');
$strings['noonecansubscribenow']    = get_string('noonecansubscribenow', 'moodleforum');
$strings['invalidforcesubscribe']   = get_string('invalidforcesubscribe', 'moodleforum');

// Check if the user was requesting the subscription himself.
if ($user) {
    // A manager requested the subscription.

    // Check the login.
    require_sesskey();

    // Check the users capabilities.
    if (!has_capability('mod/moodleforum:managesubscriptions', $context)) {
        print_error('nopermissiontosubscribe', 'moodleforum');
    }

    // Retrieve the user from the database.
    $user = $DB->get_record('user', array('id' => $user), '*', MUST_EXIST);

} else {

    // The user requested the subscription himself.
    $user = $USER;
}

// Check if the user is already subscribed.
$issubscribed = \mod_moodleforum\subscriptions::is_subscribed($user->id, $moodleforum, $discussionid, $cm);

// To subscribe to a moodleforum or a discussion, the user needs to be logged in.
require_login($course, false, $cm);

// Guests, visitors and not enrolled people cannot subscribe.
$isenrolled = is_enrolled($context, $USER, '', true);
if (is_null($mode) AND !$isenrolled) {

    // Prepare the output.
    $PAGE->set_title($course->shortname);
    $PAGE->set_heading($course->fullname);

    // Redirect guest users to a login page.
    if (isguestuser()) {
        echo $OUTPUT->header();
        $message = $strings['subscribeenrolledonly'] . '<br /></ br>' . get_string('liketologin');
        $url     = new moodle_url('/mod/moodleforum/view.php', array('m' => $id));
        echo $OUTPUT->confirm($message, get_login_url(), $url);
        echo $OUTPUT->footer;
        exit;
    } else {
        // There should not be any links leading to this place. Just redirect.
        $url = new moodle_url('/mod/moodleforum/view.php', array('m' => $id));
        redirect($url, $strings['subscribeenrolledonly'], null, $notify['error']);
    }
}

// Create the url to redirect the user back to where he is coming from.
$urlindex = 'index.php?id=' . $course->id;
$urlview  = 'view.php?m=' . $id;
$returnto = optional_param('backtoindex', 0, PARAM_INT) ? $urlindex : $urlview;
if ($returnurl) {
    $returnto = $returnurl;
}

// Change the general subscription state.
if (!is_null($mode) AND has_capability('mod/moodleforum:managesubscriptions', $context)) {
    require_sesskey();

    // Set the new mode.
    switch ($mode) {

        // Everyone can choose what he wants.
        case moodleforum_CHOOSESUBSCRIBE:
            \mod_moodleforum\subscriptions::set_subscription_mode($moodleforum->id, moodleforum_CHOOSESUBSCRIBE);
            redirect($returnto, $strings['everyonecannowchoose'], null, $notify['success']);
            break;

        // Force users to be subscribed.
        case moodleforum_FORCESUBSCRIBE:
            \mod_moodleforum\subscriptions::set_subscription_mode($moodleforum->id, moodleforum_FORCESUBSCRIBE);
            redirect($strings['everyoneisnowsubscribed'], $string, null, $notify['success']);
            break;

        // Default setting.
        case moodleforum_INITIALSUBSCRIBE:
            // If users are not forced, subscribe all users.
            if ($moodleforum->forcesubscribe <> moodleforum_INITIALSUBSCRIBE) {
                $users = \mod_moodleforum\subscriptions::get_potential_subscribers($context, 0, 'u.id, u.email', '');
                foreach ($users as $user) {
                    \mod_moodleforum\subscriptions::subscribe_user($moodleforum->id, $moodleforum, $context);
                }
            }

            // Change the subscription state.
            \mod_moodleforum\subscriptions::set_subscription_mode($moodleforum->id, moodleforum_INITIALSUBSCRIBE);

            // Redirect the user.
            $string = get_string('everyoneisnowsubscribed', 'moodleforum');
            redirect($returnto, $strings['everyoneisnowsubscribed'], null, $notify['success']);
            break;

        // Do not allow subscriptions.
        case moodleforum_DISALLOWSUBSCRIBE:
            \mod_moodleforum\subscriptions::set_subscription_mode($moodleforum->id, moodleforum_DISALLOWSUBSCRIBE);
            $string = get_string('noonecansubscribenow', 'moodleforum');
            redirect($strings['noonecansubscribenow'], $string, null, $notify['success']);
            break;

        default:
            print_error($strings['invalidforcesubscribe']);
    }
}

// Redirect the user back if the user is forced to be subscribed.
$isforced = \mod_moodleforum\subscriptions::is_forcesubscribed($moodleforum);
if ($isforced) {
    redirect($returnto, $strings['everyoneisnowsubscribed'], null, $notify['success']);
    exit;
}

// Create an info object.
$info                 = new stdClass();
$info->name           = fullname($user);
$info->moodleforum = format_string($moodleforum->name);

// Check if the user is subscribed to the moodleforum.
// The action is to unsubscribe the user.
if ($issubscribed) {

    // Check if there is a sesskey.
    if (is_null($sesskey)) {

        // Perpare the output.
        $PAGE->set_title($course->shortname);
        $PAGE->set_heading($course->fullname);
        echo $OUTPUT->header();

        // Create an url to get back to the view.
        $viewurl = new moodle_url('/mod/moodleforum/view.php', array('m' => $id));

        // Was a discussion id submitted?
        if ($discussionid) {

            // Create a new info object.
            $info2                 = new stdClass();
            $info2->moodleforum = format_string($moodleforum->name);
            $info2->discussion     = format_string($discussion->name);

            // Create a confirm statement.
            $string = get_string('confirmunsubscribediscussion', 'moodleforum', $info2);
            echo $OUTPUT->confirm($string, $PAGE->url, $viewurl);

        } else {
            // The discussion is not involved.

            // Create a confirm statement.
            $string = get_string('confirmunsubscribe', 'moodleforum', format_string($moodleforum->name));
            echo $OUTPUT->confirm($string, $PAGE->url, $viewurl);
        }

        // Print the rest of the page.
        echo $OUTPUT->footer();
        exit;
    }

    // From now on, a valid session key needs to be set.
    require_sesskey();

    // Check if a discussion id is submitted.
    if ($discussionid === null) {

        // Unsubscribe the user and redirect him back to where he is coming from.
        if (\mod_moodleforum\subscriptions::unsubscribe_user($user->id, $moodleforum, $context, true)) {
            redirect($returnto, get_string('nownotsubscribed', 'moodleforum', $info), null, $notify['success']);
        } else {
            print_error('cannotunsubscribe', 'moodleforum', get_local_referer(false));
        }

    } else {

        // Unsubscribe the user from the discussion.
        if (\mod_moodleforum\subscriptions::unsubscribe_user_from_discussion($user->id, $discussion, $context)) {
            $info->discussion = $discussion->name;
            redirect($returnto, get_string('discussionnownotsubscribed', 'moodleforum', $info), null, $notify['success']);
        } else {
            print_error('cannotunsubscribe', 'moodleforum', get_local_referer(false));
        }
    }

} else {
    // The user needs to be subscribed.

    // Check the capabilities.
    $capabilities                        = array();
    $capabilities['managesubscriptions'] = has_capability('mod/moodleforum:managesubscriptions', $context);
    $capabilities['viewdiscussion']      = has_capability('mod/moodleforum:viewdiscussion', $context);
    require_sesskey();

    // Check if subscriptionsare allowed.
    $disabled = \mod_moodleforum\subscriptions::subscription_disabled($moodleforum);
    if ($disabled AND !$capabilities['managesubscriptions']) {
        print_error('disallowsubscribe', 'moodleforum', get_local_referer(false));
    }

    // Check if the user can view discussions.
    if (!$capabilities['viewdiscussion']) {
        print_error('noviewdiscussionspermission', 'moodleforum', get_local_referer(false));
    }

    // Check the session key.
    if (is_null($sesskey)) {

        // Prepare the output.
        $PAGE->set_title($course->shortname);
        $PAGE->set_heading($course->fullname);
        echo $OUTPUT->header();

        // Create the url to redirect the user back to.
        $viewurl = new moodle_url('/mod/moodleforum/view.php', array('m' => $id));

        // Check whether a discussion is referenced.
        if ($discussionid) {

            // Create a new info object.
            $info2                 = new stdClass();
            $info2->moodleforum = format_string($moodleforum->name);
            $info2->discussion     = format_string($discussion->name);

            // Create a confirm dialog.
            $string = get_string('confirmsubscribediscussion', 'moodleforum', $info2);
            echo $OUTPUT->confirm($string, $PAGE->url, $viewurl);

        } else {
            // No discussion is referenced.

            // Create a confirm dialog.
            $string = get_string('confirmsubscribe', 'moodleforum', format_string($moodleforum->name));
            echo $OUTPUT->confirm($string, $PAGE->url, $viewurl);
        }

        // Print the missing part of the page.
        echo $OUTPUT->footer();
        exit;
    }

    // From now on, there needs to be a valid session key.
    require_sesskey();

    // Check if the subscription is refered to a discussion.
    if ($discussionid == null) {

        // Subscribe the user to the moodleforum instance.
        \mod_moodleforum\subscriptions::subscribe_user($user->id, $moodleforum, $context, true);
        redirect($returnto, get_string('nowsubscribed', 'moodleforum', $info), null, $notify['success']);
        exit;

    } else {
        $info->discussion = $discussion->name;

        // Subscribe the user to the discussion.
        \mod_moodleforum\subscriptions::subscribe_user_to_discussion($user->id, $discussion, $context);
        redirect($returnto, get_string('discussionnowsubscribed', 'moodleforum', $info), null, $notify['success']);
        exit;
    }
}