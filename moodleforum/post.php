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
 * The file to manage posts.
 *
 * @package   mod_moodleforum
 * @copyright 2017 Kennet Winter <k_wint10@uni-muenster.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Include config and locallib.
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/locallib.php');
require_once($CFG->libdir . '/completionlib.php');

// Declare optional parameters.
$moodleforum = optional_param('moodleforum', 0, PARAM_INT);
$reply = optional_param('reply', 0, PARAM_INT);
$edit = optional_param('edit', 0, PARAM_INT);
$delete = optional_param('delete', 0, PARAM_INT);
$confirm = optional_param('confirm', 0, PARAM_INT);

// Set the URL that should be used to return to this page.
$PAGE->set_url('/mod/moodleforum/post.php', array(
    'moodleforum' => $moodleforum,
    'reply'          => $reply,
    'edit'           => $edit,
    'delete'         => $delete,
    'confirm'        => $confirm,
));

// These params will be passed as hidden variables later in the form.
$pageparams = array('moodleforum' => $moodleforum, 'reply' => $reply, 'edit' => $edit);

// Get the system context instance.
$systemcontext = context_system::instance();

// Catch guests.
if (!isloggedin() OR isguestuser()) {

    // The user is starting a new discussion in a moodleforum instance.
    if (!empty($moodleforum)) {

        // Check the moodleforum instance is valid.
        if (!$moodleforum = $DB->get_record('moodleforum', array('id' => $moodleforum))) {
            print_error('invalidmoodleforumid', 'moodleforum');
        }

        // The user is replying to an existing moodleforum discussion.
    } else if (!empty($reply)) {

        // Check if the related post exists.
        if (!$parent = moodleforum_get_post_full($reply)) {
            print_error('invalidparentpostid', 'moodleforum');
        }

        // Check if the post is part of a valid discussion.
        if (!$discussion = $DB->get_record('moodleforum_discussions', array('id' => $parent->discussion))) {
            print_error('notpartofdiscussion', 'moodleforum');
        }

        // Check if the post is related to a valid moodleforum instance.
        if (!$moodleforum = $DB->get_record('moodleforum', array('id' => $discussion->moodleforum))) {
            print_error('invalidmoodleforumid', 'moodleforum');
        }
    }

    // Get the related course.
    if (!$course = $DB->get_record('course', array('id' => $moodleforum->course))) {
        print_error('invalidcourseid');
    }

    // Get the related coursemodule and its context.
    if (!$cm = get_coursemodule_from_instance('moodleforum', $moodleforum->id, $course->id)) {
        print_error('invalidcoursemodule');
    }

    // Get the context of the module.
    $modulecontext = context_module::instance($cm->id);

    // Set parameters for the page.
    $PAGE->set_cm($cm, $course, $moodleforum);
    $PAGE->set_context($modulecontext);
    $PAGE->set_title($course->shortname);
    $PAGE->set_heading($course->fullname);

    // The guest needs to login.
    echo $OUTPUT->header();
    $strlogin = get_string('noguestpost', 'forum') . '<br /><br />' . get_string('liketologin');
    echo $OUTPUT->confirm($strlogin, get_login_url(), $CFG->wwwroot . '/mod/moodleforum/view.php?m=' . $moodleforum->id);
    echo $OUTPUT->footer();
    exit;
}

// First step: A general login is needed to post something.
require_login(0, false);

// First possibility: User is starting a new discussion in a moodleforum instance.
if (!empty($moodleforum)) {

    // Check the moodleforum instance is valid.
    if (!$moodleforum = $DB->get_record('moodleforum', array('id' => $moodleforum))) {
        print_error('invalidmoodleforumid', 'moodleforum');
    }

    // Get the related course.
    if (!$course = $DB->get_record('course', array('id' => $moodleforum->course))) {
        print_error('invalidcourseid');
    }

    // Get the related coursemodule.
    if (!$cm = get_coursemodule_from_instance('moodleforum', $moodleforum->id, $course->id)) {
        print_error('invalidcoursemodule');
    }

    // Retrieve the contexts.
    $modulecontext = context_module::instance($cm->id);
    $coursecontext = context_course::instance($course->id);

    // Check if the user can start a new discussion.
    if (!moodleforum_user_can_post_discussion($moodleforum, $cm, $modulecontext)) {

        // Catch unenrolled user.
        if (!isguestuser() AND !is_enrolled($cousecontext)) {
            if (enrol_selfenrol_available($course->id)) {
                $SESSION->wantsurl = qualified_me();
                $SESSION->enrolcancel = get_local_referer(false);
                redirect(new moodle_url('/enrol/index.php', array(
                    'id'        => $course->id,
                    'returnurl' => '/mod/moodleforum/view.php?m=' . $moodleforum->id
                )), get_string('youneedtoenrol'));
            }
        }

        // Notify the user, that he can not post a new discussion.
        print_error('nopostmoodleforum', 'moodleforum');
    }

    // Where is the user coming from?
    $SESSION->fromurl = get_local_referer(false);

    // Load all the $post variables.
    $post = new stdClass();
    $post->course = $course->id;
    $post->moodleforum = $moodleforum->id;
    $post->discussion = 0;
    $post->parent = 0;
    $post->subject = '';
    $post->userid = $USER->id;
    $post->message = '';

    // Unset where the user is coming from.
    // Allows to calculate the correct return url later.
    unset($SESSION->fromdiscussion);

} else if (!empty($reply)) {
    // Second possibility: The user is writing a new reply.

    // Check if the post exists.
    if (!$parent = moodleforum_get_post_full($reply)) {
        print_error('invalidparentpostid', 'moodleforum');
    }

    // Check if the post is part of a discussion.
    if (!$discussion = $DB->get_record('moodleforum_discussions', array('id' => $parent->discussion))) {
        print_error('notpartofdiscussion', 'moodleforum');
    }

    // Check if the discussion is part of a moodleforum instance.
    if (!$moodleforum = $DB->get_record('moodleforum', array('id' => $discussion->moodleforum))) {
        print_error('invalidmoodleforumid', 'moodleforum');
    }

    // Check if the moodleforum instance is part of a course.
    if (!$course = $DB->get_record('course', array('id' => $discussion->course))) {
        print_error('invalidcourseid');
    }

    // Retrieve the related coursemodule.
    if (!$cm = get_coursemodule_from_instance('moodleforum', $moodleforum->id, $course->id)) {
        print_error('invalidcoursemodule');
    }

    // Ensure the coursemodule is set correctly.
    $PAGE->set_cm($cm, $course, $moodleforum);

    // Retrieve the other contexts.
    $modulecontext = context_module::instance($cm->id);
    $coursecontext = context_course::instance($course->id);

    // Check whether the user is allowed to post.
    if (!moodleforum_user_can_post($moodleforum, $USER, $cm, $course, $modulecontext)) {

        // Give the user the chance to enroll himself to the course.
        if (!isguestuser() AND !is_enrolled($coursecontext)) {
            $SESSION->wantsurl = qualified_me();
            $SESSION->enrolcancel = get_local_referer(false);
            redirect(new moodle_url('/enrol/index.php',
                array('id' => $course->id, 'returnurl' => '/mod/moodleforum/view.php?m=' . $moodleforum->id)),
                get_string('youneedtoenrol'));
        }

        // Print the error message.
        print_error('nopostmoodleforum', 'moodleforum');
    }

    // Make sure the user can post here.
    if (!$cm->visible AND !has_capability('moodle/course:viewhiddenactivities', $modulecontext)) {
        print_error('activityiscurrentlyhidden');
    }

    // Load the $post variable.
    $post = new stdClass();
    $post->course = $course->id;
    $post->moodleforum = $moodleforum->id;
    $post->discussion = $parent->discussion;
    $post->parent = $parent->id;
    $post->subject = $discussion->name;
    $post->userid = $USER->id;
    $post->message = '';

    // Append 'RE: ' to the discussions subject.
    $strre = get_string('re', 'moodleforum');
    if (!(substr($post->subject, 0, strlen($strre)) == $strre)) {
        $post->subject = $strre . ' ' . $post->subject;
    }

    // Unset where the user is coming from.
    // Allows to calculate the correct return url later.
    unset($SESSION->fromdiscussion);


} else if (!empty($edit)) {
    // Third possibility: The user is editing his own post.

    // Check if the submitted post exists.
    if (!$post = moodleforum_get_post_full($edit)) {
        print_error('invalidpostid', 'moodleforum');
    }

    // Get the parent post of this post if it is not the starting post of the discussion.
    if ($post->parent) {
        if (!$parent = moodleforum_get_post_full($post->parent)) {
            print_error('invalidparentpostid', 'moodleforum');
        }
    }

    // Check if the post refers to a valid discussion.
    if (!$discussion = $DB->get_record('moodleforum_discussions', array('id' => $post->discussion))) {
        print_error('notpartofdiscussion', 'moodleforum');
    }

    // Check if the post refers to a valid moodleforum instance.
    if (!$moodleforum = $DB->get_record('moodleforum', array('id' => $discussion->moodleforum))) {
        print_error('invalidmoodleforumid', 'moodleforum');
    }

    // Check if the post refers to a valid course.
    if (!$course = $DB->get_record('course', array('id' => $discussion->course))) {
        print_error('invalidcourseid');
    }

    // Retrieve the related coursemodule.
    if (!$cm = get_coursemodule_from_instance('moodleforum', $moodleforum->id, $course->id)) {
        print_error('invalidcoursemodule');
    } else {
        $modulecontext = context_module::instance($cm->id);
    }

    // Set the pages context.
    $PAGE->set_cm($cm, $course, $moodleforum);

    // Check if the post can be edited.
    $intime = ((time() - $post->created) > get_config('moodleforum', 'maxeditingtime'));
    if ($intime AND !has_capability('mod/moodleforum:editanypost', $modulecontext)) {
        print_error('maxtimehaspassed', 'moodleforum', '', format_time(get_config('moodleforum', 'maxeditingtime')));
    }

    // If the current user is not the one who posted this post.
    if ($post->userid <> $USER->id) {

        // Check if the current user has not the capability to edit any post.
        if (!has_capability('mod/moodleforum:editanypost', $modulecontext)) {

            // Display the error. Capabilities are missing.
            print_error('cannoteditposts', 'moodleforum');
        }
    }

    // Load the $post variable.
    $post->edit = $edit;
    $post->course = $course->id;
    $post->moodleforum = $moodleforum->id;

    // Unset where the user is coming from.
    // Allows to calculate the correct return url later.
    unset($SESSION->fromdiscussion);

} else if (!empty($delete)) {
    // Fourth possibility: The user is deleting a post.
    // Check if the post is existing.
    if (!$post = moodleforum_get_post_full($delete)) {
        print_error('invalidpostid', 'moodleforum');
    }

    // Get the related discussion.
    if (!$discussion = $DB->get_record('moodleforum_discussions', array('id' => $post->discussion))) {
        print_error('notpartofdiscussion', 'moodleforum');
    }

    // Get the related moodleforum instance.
    if (!$moodleforum = $DB->get_record('moodleforum', array('id' => $discussion->moodleforum))) {
        print_error('invalidmoodleforumid', 'moodleoveflow');
    }

    // Get the related coursemodule.
    if (!$cm = get_coursemodule_from_instance('moodleforum', $moodleforum->id, $moodleforum->course)) {
        print_error('invalidcoursemodule');
    }

    // Get the related course.
    if (!$course = $DB->get_record('course', array('id' => $moodleforum->course))) {
        print_error('invalidcourseid');
    }

    // Require a login and retrieve the modulecontext.
    require_login($course, false, $cm);
    $modulecontext = context_module::instance($cm->id);

    // Check some capabilities.
    $deleteownpost = has_capability('mod/moodleforum:deleteownpost', $modulecontext);
    $deleteanypost = has_capability('mod/moodleforum:deleteanypost', $modulecontext);
    if (!(($post->userid == $USER->id AND $deleteownpost) OR $deleteanypost)) {
        print_error('cannotdeletepost', 'moodleforum');
    }

    // Count all replies of this post.
    $replycount = moodleforum_count_replies($post);

    // Has the user confirmed the deletion?
    if (!empty($confirm) AND confirm_sesskey()) {

        // Check if the user has the capability to delete the post.
        $timepassed = time() - $post->created;
        if (($timepassed > get_config('moodleforum', 'maxeditingtime')) AND !$deleteanypost) {
            $url = new moodle_url('/mod/moodleforum/discussion.php', array('d' => $post->discussion));
            print_error('cannotdeletepost', 'moodleforum', moodleforum_go_back_to($url));
        }

        // A normal user cannot delete his post if there are direct replies.
        if ($replycount AND !$deleteanypost) {
            $url = new moodle_url('/mod/moodleforum/discussion.php', array('d' => $post->discussion));
            print_error('couldnotdeletereplies', 'moodleforum', moodleforum_go_back_to($url));
        } else {
            // Delete the post.

            // The post is the starting post of a discussion. Delete the topic as well.
            if (!$post->parent) {
                moodleforum_delete_discussion($discussion, $course, $cm, $moodleforum);

                // Trigger the discussion deleted event.
                $params = array(
                    'objectid' => $discussion->id,
                    'context'  => $modulecontext,
                );

                $event = \mod_moodleforum\event\discussion_deleted::create($params);
                $event->trigger();

                // Redirect the user back to start page of the moodleforum instance.
                redirect("view.php?m=$discussion->moodleforum");
                exit;

            } else if (moodleforum_delete_post($post, $deleteanypost, $course, $cm, $moodleforum)) {
                // Delete a single post.
                // Redirect back to the discussion.
                $discussionurl = new moodle_url('/mod/moodleforum/discussion.php', array('d' => $discussion->id));
                redirect(moodleforum_go_back_to($discussionurl));
                exit;

            } else {
                // Something went wrong.
                print_error('errorwhiledelete', 'moodleforum');
            }
        }
    } else {
        // Deletion needs to be confirmed.

        moodleforum_set_return();
        $PAGE->navbar->add(get_string('delete', 'moodleforum'));
        $PAGE->set_title($course->shortname);
        $PAGE->set_heading($course->fullname);

        // Check if there are replies for the post.
        if ($replycount) {

            // Check if the user has capabilities to delete more than one post.
            if (!$deleteanypost) {
                print_error('couldnotdeletereplies', 'moodleforum',
                    moodleforum_go_back_to(new moodle_url('/mod/moodleforum/discussion.php',
                        array('d' => $post->discussion, 'p' . $post->id))));
            }

            // Request a confirmation to delete the post.
            echo $OUTPUT->header();
            echo $OUTPUT->heading(format_string($moodleforum->name), 2);
            echo $OUTPUT->confirm(get_string("deletesureplural", "moodleforum", $replycount + 1),
                "post.php?delete=$delete&confirm=$delete", $CFG->wwwroot . '/mod/moodleforum/discussion.php?d=' .
                $post->discussion . '#p' . $post->id);

        } else {
            // Delete a single post.

            // Print a confirmation message.
            echo $OUTPUT->header();
            echo $OUTPUT->heading(format_string($moodleforum->name), 2);
            echo $OUTPUT->confirm(get_string("deletesure", "moodleforum", $replycount),
                "post.php?delete=$delete&confirm=$delete",
                $CFG->wwwroot . '/mod/moodleforum/discussion.php?d=' . $post->discussion . '#p' . $post->id);
        }
    }
    echo $OUTPUT->footer();
    exit;

} else {
    // Last posibility: the action is not known.

    print_error('unknownaction');
}

// Second step: The user must be logged on properly. Must be enrolled to the course as well.
require_login($course, false, $cm);

// Get the contexts.
$modulecontext = context_module::instance($cm->id);
$coursecontext = context_course::instance($course->id);

// Get the subject.
if ($edit) {
    $subject = $discussion->name;
} else if ($reply) {
    $subject = $post->subject;
} else if ($moodleforum) {
    $subject = $post->subject;
}

// Get attachments.
$draftitemid = file_get_submitted_draft_itemid('attachments');
file_prepare_draft_area($draftitemid,
    $modulecontext->id,
    'mod_moodleforum',
    'attachment',
    empty($post->id) ? null : $post->id,
    mod_moodleforum_post_form::attachment_options($moodleforum));

// Prepare the form.
$formarray = array(
    'course'         => $course,
    'cm'             => $cm,
    'coursecontext'  => $coursecontext,
    'modulecontext'  => $modulecontext,
    'moodleforum' => $moodleforum,
    'post'           => $post,
    'edit'           => $edit,
);
$mformpost = new mod_moodleforum_post_form('post.php', $formarray, 'post', '', array('id' => 'mformmoodleforum'));

// The current user is not the original author.
// Append the message to the end of the message.
if ($USER->id != $post->userid) {

    // Create a temporary object.
    $data = new stdClass();
    $data->date = userdate($post->modified);
    $post->messageformat = editors_get_preferred_format();

    // Append the message depending on the messages format.
    if ($post->messageformat == FORMAT_HTML) {
        $data->name = '<a href="' . $CFG->wwwroot . '/user/view.php?id' . $USER->id .
            '&course=' . $post->course . '">' . fullname($USER) . '</a>';
        $post->message .= '<p><span class="edited">(' . get_string('editedby', 'moodleforum', $data) . ')</span></p>';
    } else {
        $data->name = fullname($USER);
        $post->message .= "\n\n(" . get_string('editedby', 'moodleforum', $data) - ')';
    }

    // Delete the temporary object.
    unset($data);
}

// Define the heading for the form.
$formheading = '';
if (!empty($parent)) {
    $heading = get_string('yourreply', 'moodleforum');
    $formheading = get_string('reply', 'moodleforum');
} else {
    $heading = get_string('yournewtopic', 'moodleforum');
}

// Get the original post.
$postid = empty($post->id) ? null : $post->id;
$postmessage = empty($post->message) ? null : $post->message;

// Set data for the form.
$param1 = (isset($discussion->id) ? array($discussion->id) : array());
$param2 = (isset($post->format) ? array('format' => $post->format) : array());
$param3 = (isset($discussion->timestart) ? array('timestart' => $discussion->timestart) : array());
$param4 = (isset($discussion->timeend) ? array('timeend' => $discussion->timeend) : array());
$param5 = (isset($discussion->id) ? array('discussion' => $discussion->id) : array());
$mformpost->set_data(array(
        'attachments' => $draftitemid,
        'general'     => $heading,
        'subject'     => $subject,
        'message'     => array(
            'text'   => $postmessage,
            'format' => editors_get_preferred_format(),
            'itemid' => $postid,
        ),
        'userid'      => $post->userid,
        'parent'      => $post->parent,
        'discussion'  => $post->discussion,
        'course'      => $course->id
    ) + $pageparams + $param1 + $param2 + $param3 + $param4 + $param5);

// Is it canceled?
if ($mformpost->is_cancelled()) {

    // Redirect the user back.
    if (!isset($discussion->id)) {
        redirect(new moodle_url('/mod/moodleforum/view.php', array('m' => $moodleforum->id)));
    } else {
        redirect(new moodle_url('/mod/moodleforum/discussion.php', array('d' => $discussion->id)));
    }

    // Cancel.
    exit();
}

// Is it submitted?
if ($fromform = $mformpost->get_data()) {

    // Redirect url in case of occuring errors.
    if (empty($SESSION->fromurl)) {
        $errordestination = "$CFG->wwwroot/mod/moodleforum/view.php?m=$moodleforum->id";
    } else {
        $errordestination = $SESSION->fromurl;
    }

    // Format the submitted data.
    $fromform->messageformat = $fromform->message['format'];
    $fromform->message = $fromform->message['text'];
    $fromform->messagetrust = trusttext_trusted($modulecontext);

    // If we are updating a post.
    if ($fromform->edit) {

        // Initiate some variables.
        unset($fromform->groupid);
        $fromform->id = $fromform->edit;
        $message = '';

        // The FORUM-Plugin had an bug: https://tracker.moodle.org/browse/MDL-4314
        // This is a fix for it.
        if (!$realpost = $DB->get_record('moodleforum_posts', array('id' => $fromform->id))) {
            $realpost = new stdClass();
            $realpost->userid = -1;
        }

        // Check the capabilities of the user.
        // He may proceed if he can edit any post or if he has the startnewdiscussion
        // capability or the capability to reply and is editing his own post.
        $editanypost = has_capability('mod/moodleforum:editanypost', $modulecontext);
        $replypost = has_capability('mod/moodleforum:replypost', $modulecontext);
        $startdiscussion = has_capability('mod/moodleforum:startdiscussion', $modulecontext);
        $ownpost = ($realpost->userid == $USER->id);
        if (!((($ownpost AND $replypost OR $startdiscussion)) OR $editanypost)) {
            print_error('cannotupdatepost', 'moodleforum');
        }

        // Update the post or print an error message.
        $updatepost = $fromform;
        $updatepost->moodleforum = $moodleforum->id;
        if (!moodleforum_update_post($updatepost, $mformpost)) {
            print_error('couldnotupdate', 'moodleforum', $errordestination);
        }

        // Create a success-message.
        if ($realpost->userid == $USER->id) {
            $message .= get_string('postupdated', 'moodleforum');
        } else {
            $realuser = $DB->get_record('user', array('id' => $realpost->userid));
            $message .= get_string('editedpostupdated', 'moodleforum', fullname($realuser));
        }

        // Create a link to go back to the discussion.
        $discussionurl = new moodle_url('/mod/moodleforum/discussion.php', array('d' => $discussion->id), 'p' . $fromform->id);

        // Set some parameters.
        $params = array(
            'context'  => $modulecontext,
            'objectid' => $fromform->id,
            'other'    => array(
                'discussionid'     => $discussion->id,
                'moodleforumid' => $moodleforum->id,
            ));

        // If the editing user is not the original author, add the original author to the params.
        if ($realpost->userid != $USER->id) {
            $params['relateduserid'] = $realpost->userid;
        }

        // Trigger post updated event.
        $event = \mod_moodleforum\event\post_updated::create($params);
        $event->trigger();

        // Redirect back to the discussion.
        redirect(moodleforum_go_back_to($discussionurl), $message, null, \core\output\notification::NOTIFY_SUCCESS);

        // Cancel.
        exit;

    } else if ($fromform->discussion) {
        // Add a new post to an existing discussion.

        // Set some basic variables.
        unset($fromform->groupid);
        $message = '';
        $addpost = $fromform;
        $addpost->moodleforum = $moodleforum->id;

        // Create the new post.
        if ($fromform->id = moodleforum_add_new_post($addpost)) {

            // Subscribe to this thread.
            $discussion = new \stdClass();
            $discussion->id = $fromform->discussion;
            $discussion->moodleforum = $moodleforum->id;
            \mod_moodleforum\subscriptions::moodleforum_post_subscription($fromform,
                $moodleforum, $discussion, $modulecontext);

            // Print a success-message.
            $message .= '<p>' . get_string("postaddedsuccess", "moodleforum") . '</p>';
            $message .= '<p>' . get_string("postaddedtimeleft", "moodleforum",
                    format_time(get_config('moodleforum', 'maxeditingtime'))) . '</p>';

            // Set the URL that links back to the discussion.
            $link = '/mod/moodleforum/discussion.php';
            $discussionurl = new moodle_url($link, array('d' => $discussion->id), 'p' . $fromform->id);

            // Trigger post created event.
            $params = array(
                'context'  => $modulecontext,
                'objectid' => $fromform->id,
                'other'    => array(
                    'discussionid'     => $discussion->id,
                    'moodleforumid' => $moodleforum->id,
                ));
            $event = \mod_moodleforum\event\post_created::create($params);
            $event->trigger();
            redirect(
                moodleforum_go_back_to($discussionurl),
                $message,
                \core\output\notification::NOTIFY_SUCCESS
            );

            // Print an error if the answer could not be added.
        } else {
            print_error('couldnotadd', 'moodleforum', $errordestination);
        }

        // The post has been added.
        exit;

    } else {
        // Add a new discussion.

        // The location to redirect the user after successfully posting.
        $redirectto = new moodle_url('view.php', array('m' => $fromform->moodleforum));

        $discussion = $fromform;
        $discussion->name = $fromform->subject;

        // Check if the user is allowed to post here.
        if (!moodleforum_user_can_post_discussion($moodleforum)) {
            print_error('cannotcreatediscussion', 'moodleforum');
        }

        // Check if the creation of the new discussion failed.
        if (!$discussion->id = moodleforum_add_discussion($discussion, $modulecontext)) {

            print_error('couldnotadd', 'moodleforum', $errordestination);

        } else {    // The creation of the new discussion was successful.

            $params = array(
                'context'  => $modulecontext,
                'objectid' => $discussion->id,
                'other'    => array(
                    'moodleforumid' => $moodleforum->id,
                )
            );

            $message = '<p>' . get_string("postaddedsuccess", "moodleforum") . '</p>';

            // Trigger the discussion created event.
            $params = array(
                'context'  => $modulecontext,
                'objectid' => $discussion->id,
            );
            $event = \mod_moodleforum\event\discussion_created::create($params);
            $event->trigger();
            // Subscribe to this thread.
            $discussion->moodleforum = $moodleforum->id;
            \mod_moodleforum\subscriptions::moodleforum_post_subscription($fromform,
                $moodleforum, $discussion, $modulecontext);
        }

        // Redirect back to te discussion.
        redirect(moodleforum_go_back_to($redirectto->out()), $message, null, \core\output\notification::NOTIFY_SUCCESS);

        // Do not continue.
        exit;
    }
}

// If the script gets to this point, nothing has been submitted.
// We have to display the form.
// $course and $moodleforum are defined.
// $discussion is only used for replying and editing.

// Define the message to be displayed above the form.
$toppost = new stdClass();
$toppost->subject = get_string("addanewdiscussion", "moodleforum");

// Initiate the page.
$PAGE->set_title("$course->shortname: $moodleforum->name " . format_string($toppost->subject));
$PAGE->set_heading($course->fullname);

// Display the header.
echo $OUTPUT->header();
echo $OUTPUT->heading(format_string($moodleforum->name), 2);

// Show the description of the instance.
if (!empty($moodleforum->intro)) {
    echo $OUTPUT->box(format_module_intro('moodleforum', $moodleforum, $cm->id), 'generalbox', 'intro');
}

// Display the form.
$mformpost->display();

// Display the footer.
echo $OUTPUT->footer();