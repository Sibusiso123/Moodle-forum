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
 * Library of interface functions and constants for module moodleforum
 *
 * All the core Moodle functions, neeeded to allow the module to work
 * integrated in Moodle should be placed here.
 *
 * All the moodleforum specific functions, needed to implement all the module
 * logic, should go to locallib.php. This will help to save some memory when
 * Moodle is performing actions across all modules.
 *
 * @package   mod_moodleforum
 * @copyright 2017 Kennet Winter <k_wint10@uni-muenster.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/locallib.php');

// Readtracking constants.
define('moodleforum_TRACKING_OFF', 0);
define('moodleforum_TRACKING_OPTIONAL', 1);
define('moodleforum_TRACKING_FORCED', 2);

// Subscription constants.
define('moodleforum_CHOOSESUBSCRIBE', 0);
define('moodleforum_FORCESUBSCRIBE', 1);
define('moodleforum_INITIALSUBSCRIBE', 2);
define('moodleforum_DISALLOWSUBSCRIBE', 3);

// Mailing state constants.
define('moodleforum_MAILED_PENDING', 0);
define('moodleforum_MAILED_SUCCESS', 1);
define('moodleforum_MAILED_ERROR', 2);

// Constants for the post rating.
define('moodleforum_PREFERENCE_STARTER', 0);
define('moodleforum_PREFERENCE_TEACHER', 1);

// Reputation constants.
define('moodleforum_REPUTATION_MODULE', 0);
define('moodleforum_REPUTATION_COURSE', 1);

// Allow negative reputations?
define('moodleforum_REPUTATION_POSITIVE', 0);
define('moodleforum_REPUTATION_NEGATIVE', 1);

// Rating constants.
define('RATING_NEUTRAL', 0);
define('RATING_DOWNVOTE', 1);
define('RATING_REMOVE_DOWNVOTE', 10);
define('RATING_UPVOTE', 2);
define('RATING_REMOVE_UPVOTE', 20);
define('RATING_SOLVED', 3);
define('RATING_REMOVE_SOLVED', 30);
define('RATING_HELPFUL', 4);
define('RATING_REMOVE_HELPFUL', 40);

/* Moodle core API */

/**
 * Returns the information on whether the module supports a feature
 *
 * See {@link plugin_supports()} for more info.
 *
 * @param string $feature FEATURE_xx constant for requested feature
 *
 * @return mixed true if the feature is supported, null if unknown
 */
function moodleforum_supports($feature) {

    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the moodleforum into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param stdClass                    $moodleforum Submitted data from the form in mod_form.php
 * @param mod_moodleforum_mod_form $mform          The form instance itself (if needed)
 *
 * @return int The id of the newly inserted moodleforum record
 */
function moodleforum_add_instance(stdClass $moodleforum, mod_moodleforum_mod_form $mform = null) {
    global $DB;

    // Set the current time.
    $moodleforum->timecreated = time();

    // You may have to add extra stuff in here.

    $moodleforum->id = $DB->insert_record('moodleforum', $moodleforum);

    return $moodleforum->id;
}

/**
 * Handle changes following the creation of a moodleforum instance.
 * This function is typically called by the course_module_created observer.
 *
 * @param object   $context        The context of the moodleforum
 * @param stdClass $moodleforum The moodleforum object
 */
function moodleforum_instance_created($context, $moodleforum) {

    // Check if users are forced to be subscribed to the moodleforum instance.
    if ($moodleforum->forcesubscribe == moodleforum_INITIALSUBSCRIBE) {

        // Get a list of all potential subscribers.
        $users = \mod_moodleforum\subscriptions::get_potential_subscribers($context, 'u.id, u.email');

        // Subscribe all potential subscribers to this moodleforum.
        foreach ($users as $user) {
            \mod_moodleforum\subscriptions::subscribe_user($user->id, $moodleforum, $context);
        }
    }
}

/**
 * Updates an instance of the moodleforum in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param stdClass                    $moodleforum An object from the form in mod_form.php
 * @param mod_moodleforum_mod_form $mform          The form instance itself (if needed)
 *
 * @return boolean Success/Fail
 */
function moodleforum_update_instance(stdClass $moodleforum, mod_moodleforum_mod_form $mform = null) {
    global $DB;

    $moodleforum->timemodified = time();
    $moodleforum->id           = $moodleforum->instance;

    // Get the old record.
    $oldmoodleforum = $DB->get_record('moodleforum', array('id' => $moodleforum->id));

    // Find the context of the module.
    $modulecontext = context_module::instance($moodleforum->coursemodule);

    // Check if the subscription state has changed.
    $nowforced   = ($moodleforum->forcesubscribe == moodleforum_INITIALSUBSCRIBE);
    $statechaged = ($moodleforum->forcesubscribe <> $oldmoodleforum->forcesubscribe);
    if ($nowforced AND $statechaged) {

        // Get a list of potential subscribers.
        $users = \mod_moodleforum\subscriptions::get_potential_subscribers($modulecontext, 'u.id, u.email', '');

        // Subscribe all those users to the moodleforum instance.
        foreach ($users as $user) {
            \mod_moodleforum\subscriptions::subscribe_user($user->id, $moodleforum, $modulecontext);
        }
    }

    // Update the moodleforum instance in the database.
    $result = $DB->update_record('moodleforum', $moodleforum);

    return $result;
}

/**
 * This standard function will check all instances of this module
 * and make sure there are up-to-date events created for each of them.
 * If courseid = 0, then every moodleforum event in the site is checked, else
 * only moodleforum events belonging to the course specified are checked.
 * This is only required if the module is generating calendar events.
 *
 * @param int $courseid Course ID
 *
 * @return bool
 */
function moodleforum_refresh_events($courseid = 0) {
    global $DB;

    if ($courseid == 0) {
        if (!$moodleforums = $DB->get_records('moodleforum')) {
            return true;
        }
    } else {
        if (!$moodleforums = $DB->get_records('moodleforum', array('course' => $courseid))) {
            return true;
        }
    }

    /*
    foreach ($moodleforums as $moodleforum) {
        // Create a function such as the one below to deal with updating calendar events.
        // moodleforum_update_events($moodleforum);
    }
    */

    return true;
}

/**
 * Removes an instance of the moodleforum from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 *
 * @return boolean Success/Failure
 */
function moodleforum_delete_instance($id) {
    global $DB;

    // Initiate the variables.
    $result = true;

    // Get the needed objects.
    if (!$moodleforum = $DB->get_record('moodleforum', array('id' => $id))) {
        return false;
    }
    if (!$cm = get_coursemodule_from_instance('moodleforum', $moodleforum->id)) {
        return false;
    }
    if (!$course = $DB->get_record('course', array('id' => $cm->course))) {
        return false;
    }

    // Get the context module.
    $context = context_module::instance($cm->id);

    // Delete all connected files.
    $fs = get_file_storage();
    $fs->delete_area_files($context->id);

    // Delete the subscription elements.
    $DB->delete_records('moodleforum_subscriptions', array('moodleforum' => $moodleforum->id));
    $DB->delete_records('moodleforum_discuss_subs', array('moodleforum' => $moodleforum->id));

    // Delete the discussion recursivly.
    if ($discussions = $DB->get_records('moodleforum_discussions', array('moodleforum' => $moodleforum->id))) {
        require_once('locallib.php');
        foreach ($discussions as $discussion) {
            if (!moodleforum_delete_discussion($discussion, $course, $cm, $moodleforum)) {
                $result = false;
            }
        }
    }

    // Delete the read records.
    \mod_moodleforum\readtracking::moodleforum_delete_read_records(-1, -1, -1, $moodleforum->id);

    // Delete the moodleforum instance.
    if (!$DB->delete_records('moodleforum', array('id' => $moodleforum->id))) {
        $result = false;
    }

    // Return whether the deletion was successful.
    return $result;
}

/**
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 *
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @param stdClass         $course         The course record
 * @param stdClass         $user           The user record
 * @param cm_info|stdClass $mod            The course module info object or record
 * @param stdClass         $moodleforum The moodleforum instance record
 *
 * @return stdClass|null
 */
function moodleforum_user_outline($course, $user, $mod, $moodleforum) {
    $return       = new stdClass();
    $return->time = 0;
    $return->info = '';

    return $return;
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in moodleforum activities and print it out.
 *
 * @param stdClass $course        The course record
 * @param bool     $viewfullnames Should we display full names
 * @param int      $timestart     Print activity since this timestamp
 *
 * @return boolean True if anything was printed, otherwise false
 */
function moodleforum_print_recent_activity($course, $viewfullnames, $timestart) {
    return false;
}

/**
 * Returns all other caps used in the module
 *
 * For example, this could be array('moodle/site:accessallgroups') if the
 * module uses that capability.
 *
 * @return array
 */
function moodleforum_get_extra_capabilities() {
    return array();
}

/* File API */

/**
 * Returns the lists of all browsable file areas within the given module context
 *
 * The file area 'intro' for the activity introduction field is added automatically
 * by {@link file_browser::get_file_info_context_module()}
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 *
 * @return array of [(string)filearea] => (string)description
 */
function moodleforum_get_file_areas($course, $cm, $context) {
    return array(
        'attachment' => get_string('areaattachment', 'mod_moodleforum'),
        'post'       => get_string('areapost', 'mod_moodleforum'),
    );
}

/**
 * File browsing support for moodleforum file areas
 *
 * @package  mod_moodleforum
 * @category files
 *
 * @param file_browser $browser
 * @param array        $areas
 * @param stdClass     $course
 * @param stdClass     $cm
 * @param stdClass     $context
 * @param string       $filearea
 * @param int          $itemid
 * @param string       $filepath
 * @param string       $filename
 *
 * @return file_info instance or null if not found
 */
function moodleforum_get_file_info($browser, $areas, $course, $cm, $context, $filearea, $itemid, $filepath, $filename) {
    return null;
}

/**
 * Serves the files from the moodleforum file areas
 *
 * @package  mod_moodleforum
 * @category files
 *
 * @param stdClass $course        the course object
 * @param stdClass $cm            the course module object
 * @param stdClass $context       the moodleforum's context
 * @param string   $filearea      the name of the file area
 * @param array    $args          extra arguments (itemid, path)
 * @param bool     $forcedownload whether or not force download
 * @param array    $options       additional options affecting the file serving
 */
function moodleforum_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options = array()) {
    global $DB;

    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }

    require_course_login($course, true, $cm);

    $areas = moodleforum_get_file_areas($course, $cm, $context);

    // Filearea must contain a real area.
    if (!isset($areas[$filearea])) {
        return false;
    }

    $postid = (int) array_shift($args);

    if (!$post = $DB->get_record('moodleforum_posts', array('id' => $postid))) {
        return false;
    }

    if (!$discussion = $DB->get_record('moodleforum_discussions', array('id' => $post->discussion))) {
        return false;
    }

    if (!$moodleforum = $DB->get_record('moodleforum', array('id' => $cm->instance))) {
        return false;
    }

    $fs           = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath     = "/$context->id/mod_moodleforum/$filearea/$postid/$relativepath";
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }

    // Make sure groups allow this user to see this file.
    if ($discussion->groupid > 0) {
        $groupmode = groups_get_activity_groupmode($cm, $course);
        if ($groupmode == SEPARATEGROUPS) {
            if (!groups_is_member($discussion->groupid) and !has_capability('moodle/site:accessallgroups', $context)) {
                return false;
            }
        }
    }

    // Make sure we're allowed to see it...
    if (!moodleforum_user_can_see_post($moodleforum, $discussion, $post, null, $cm)) {
        return false;
    }

    // Finally send the file.
    send_stored_file($file, 0, 0, true, $options); // download MUST be forced - security!
}

/* Navigation API */

/**
 * Extends the settings navigation with the moodleforum settings
 *
 * This function is called when the context for the page is a moodleforum module. This is not called by AJAX
 * so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav        complete settings navigation tree
 * @param navigation_node     $moodleforumnode moodleforum administration node
 */
function moodleforum_extend_settings_navigation(settings_navigation $settingsnav, navigation_node $moodleforumnode = null) {
    global $CFG, $DB, $PAGE, $USER;

    // Retrieve the current moodle record.
    $moodleforum = $DB->get_record('moodleforum', array('id' => $PAGE->cm->instance));

    // Check if the user can subscribe to the instance.
    $enrolled        = is_enrolled($PAGE->cm->context, $USER, '', false);
    $activeenrolled  = is_enrolled($PAGE->cm->context, $USER, '', true);
    $canmanage       = has_capability('mod/moodleforum:managesubscriptions', $PAGE->cm->context);
    $forcesubscribed = \mod_moodleforum\subscriptions::is_forcesubscribed($moodleforum);
    $subscdisabled   = \mod_moodleforum\subscriptions::subscription_disabled($moodleforum);
    $cansubscribe    = ($activeenrolled AND !$forcesubscribed AND (!$subscdisabled OR $canmanage));
    $cantrack        = \mod_moodleforum\readtracking::moodleforum_can_track_moodleforums($moodleforum);

    // Display a link to the index.
    if ($enrolled AND $activeenrolled) {

        // Generate the text of the link.
        $linktext = get_string('gotoindex', 'moodleforum');

        // Generate the link.
        $url    = '/mod/moodleforum/index.php';
        $params = array('id' => $moodleforum->course);
        $link   = new moodle_url($url, $params);

        // Add the link to the menu.
        $moodleforumnode->add($linktext, $link, navigation_node::TYPE_SETTING);
    }

    // Display a link to subscribe or unsubscribe.
    if ($cansubscribe) {

        // Choose the linktext depending on the current state of subscription.
        $issubscribed = \mod_moodleforum\subscriptions::is_subscribed($USER->id, $moodleforum, null);
        if ($issubscribed) {
            $linktext = get_string('unsubscribe', 'moodleforum');
        } else {
            $linktext = get_string('subscribe', 'moodleforum');
        }

        // Add the link to the menu.
        $url = new moodle_url('/mod/moodleforum/subscribe.php', array('id' => $moodleforum->id, 'sesskey' => sesskey()));
        $moodleforumnode->add($linktext, $url, navigation_node::TYPE_SETTING);
    }

    // Display a link to enable or disable readtracking.
    if ($enrolled AND $cantrack) {

        // Check some basic capabilities.
        $isoptional   = ($moodleforum->trackingtype == moodleforum_TRACKING_OPTIONAL);
        $forceallowed = get_config('moodleforum', 'allowforcedreadtracking');
        $isforced     = ($moodleforum->trackingtype == moodleforum_TRACKING_FORCED);

        // Check whether the readtracking state can be changed.
        if ($isoptional OR (!$forceallowed AND $isforced)) {

            // Generate the text of the link depending on the current state.
            $istracked = \mod_moodleforum\readtracking::moodleforum_is_tracked($moodleforum);
            if ($istracked) {
                $linktext = get_string('notrackmoodleforum', 'moodleforum');
            } else {
                $linktext = get_string('trackmoodleforum', 'moodleforum');
            }

            // Generate the link.
            $url    = '/mod/moodleforum/tracking.php';
            $params = array('id' => $moodleforum->id, 'sesskey' => sesskey());
            $link   = new moodle_url($url, $params);

            // Add the link to the menu.
            $moodleforumnode->add($linktext, $link, navigation_node::TYPE_SETTING);
        }
    }
}

/**
 * Determine the current context if one wa not already specified.
 *
 * If a context of type context_module is specified, it is immediately returned and not checked.
 *
 * @param int            $moodleforumid The moodleforum ID
 * @param context_module $context          The current context
 *
 * @return context_module The context determined
 */
function moodleforum_get_context($moodleforumid, $context = null) {
    global $PAGE;

    // If the context does not exist, find the context.
    if (!$context OR !($context instanceof context_module)) {

        // Try to take current page context to save on DB query.
        if ($PAGE->cm AND $PAGE->cm->modname === 'moodleforum' AND $PAGE->cm->instance == $moodleforumid
            AND $PAGE->context->contextlevel == CONTEXT_MODULE AND $PAGE->context->instanceid == $PAGE->cm->id
        ) {
            $context = $PAGE->context;

        } else {

            // Get the context via the coursemodule.
            $cm      = get_coursemodule_from_instance('moodleforum', $moodleforumid);
            $context = \context_module::instance($cm->id);
        }
    }

    // Return the context.
    return $context;
}

/**
 * Sends mail notifications about new posts.
 *
 * @return bool
 */
function moodleforum_send_mails() {
    global $DB, $CFG, $PAGE;

    // Get the course object of the top level site.
    $site = get_site();

    // Get the main renderers.
    $htmlout = $PAGE->get_renderer('mod_moodleforum', 'email', 'htmlemail');
    $textout = $PAGE->get_renderer('mod_moodleforum', 'email', 'textemail');

    // Initiate the arrays that are saving the users that are subscribed to posts that needs sending.
    $users      = array();
    $userscount = 0; // Count($users) is slow. This avoids using this.

    // Status arrays.
    $mailcount  = array();
    $errorcount = array();

    // Cache arrays.
    $discussions     = array();
    $moodleforums = array();
    $courses         = array();
    $coursemodules   = array();
    $subscribedusers = array();

    // Posts older than x days will not be mailed.
    // This will avoid problems with the cron not beeing ran for a long time.
    $timenow   = time();
    $endtime   = $timenow - get_config('moodleforum', 'maxeditingtime');
    $starttime = $endtime - (get_config('moodleforum', 'maxmailingtime') * 60 * 60);

    // Retrieve all unmailed posts.
    $posts = moodleforum_get_unmailed_posts($starttime, $endtime);
    if ($posts) {

        // Mark those posts as mailed.
        if (!moodleforum_mark_old_posts_as_mailed($endtime)) {
            mtrace('Errors occurred while trying to mark some posts as being mailed.');

            return false;
        }

        // Loop through all posts to be mailed.
        foreach ($posts as $postid => $post) {

            // Check the cache if the discussion exists.
            $discussionid = $post->discussion;
            if (!isset($discussions[$discussionid])) {

                // Retrieve the discussion from the database.
                $discussion = $DB->get_record('moodleforum_discussions', array('id' => $post->discussion));

                // If there is a record, update the cache. Else ignore the post.
                if ($discussion) {
                    $discussions[$discussionid] = $discussion;
                    \mod_moodleforum\subscriptions::fill_subscription_cache($discussion->moodleforum);
                    \mod_moodleforum\subscriptions::fill_discussion_subscription_cache($discussion->moodleforum);
                } else {
                    mtrace('Could not find discussion ' . $discussionid);
                    unset($posts[$postid]);
                    continue;
                }
            }

            // Retrieve the connected moodleforum instance from the database.
            $moodleforumid = $discussions[$discussionid]->moodleforum;
            if (!isset($moodleforums[$moodleforumid])) {

                // Retrieve the record from the database and update the cache.
                $moodleforum = $DB->get_record('moodleforum', array('id' => $moodleforumid));
                if ($moodleforum) {
                    $moodleforums[$moodleforumid] = $moodleforum;
                } else {
                    mtrace('Could not find moodleforum ' . $moodleforumid);
                    unset($posts[$postid]);
                    continue;
                }
            }

            // Retrieve the connected courses from the database.
            $courseid = $moodleforums[$moodleforumid]->course;
            if (!isset($courses[$courseid])) {

                // Retrieve the record from the database and update the cache.
                $course = $DB->get_record('course', array('id' => $courseid));
                if ($course) {
                    $courses[$courseid] = $course;
                } else {
                    mtrace('Could not find course ' . $courseid);
                    unset($posts[$postid]);
                    continue;
                }
            }

            // Retrieve the connected course modules from the database.
            if (!isset($coursemodules[$moodleforumid])) {

                // Retrieve the coursemodule and update the cache.
                $cm = get_coursemodule_from_instance('moodleforum', $moodleforumid, $courseid);
                if ($cm) {
                    $coursemodules[$moodleforumid] = $cm;
                } else {
                    mtrace('Could not find course module for moodleforum ' . $moodleforumid);
                    unset($posts[$postid]);
                    continue;
                }
            }

            // Cache subscribed users of each moodleforum.
            if (!isset($subscribedusers[$moodleforumid])) {

                // Retrieve the context module.
                $modulecontext = context_module::instance($coursemodules[$moodleforumid]->id);

                // Retrieve all subscribed users.
                $mid      = $moodleforums[$moodleforumid];
                $subusers = \mod_moodleforum\subscriptions::get_subscribed_users($mid, $modulecontext, 'u.*', true);
                if ($subusers) {

                    // Loop through all subscribed users.
                    foreach ($subusers as $postuser) {

                        // Save the user into the cache.
                        $subscribedusers[$moodleforumid][$postuser->id] = $postuser->id;
                        $userscount++;
                        moodleforum_minimise_user_record($postuser);
                        $users[$postuser->id] = $postuser;
                    }

                    // Release the memory.
                    unset($subusers);
                    unset($postuser);
                }
            }

            // Initiate the count of the mails send and errors.
            $mailcount[$postid]  = 0;
            $errorcount[$postid] = 0;
        }
    }

    // Send mails to the users with information about the posts.
    if ($users AND $posts) {

        // Send one mail to every user.
        foreach ($users as $userto) {

            // Terminate if the process takes more time then two minutes.
            core_php_time_limit::raise(120);

            // Tracing information.
            mtrace('Processing user ' . $userto->id);

            // Initiate the user caches to save memory.
            $userto                = clone($userto);
            $userto->ciewfullnames = array();
            $userto->canpost       = array();
            $userto->markposts     = array();

            // Cache the capabilities of the user.
            cron_setup_user($userto);

            // Reset the caches.
            foreach ($coursemodules as $moodleforumid => $unused) {
                $coursemodules[$moodleforumid]->cache       = new stdClass();
                $coursemodules[$moodleforumid]->cache->caps = array();
                unset($coursemodules[$moodleforumid]->uservisible);
            }

            // Loop through all posts of this users.
            foreach ($posts as $postid => $post) {

                // Initiate variables for the post.
                $discussion     = $discussions[$post->discussion];
                $moodleforum = $moodleforums[$discussion->moodleforum];
                $course         = $courses[$moodleforum->course];
                $cm             =& $coursemodules[$moodleforum->id];

                // Check whether the user is subscribed.
                if (!isset($subscribedusers[$moodleforum->id][$userto->id])) {
                    continue;
                }

                // Check whether the user is subscribed to the discussion.
                $iscm         = $coursemodules[$moodleforum->id];
                $uid          = $userto->id;
                $did          = $post->discussion;
                $issubscribed = \mod_moodleforum\subscriptions::is_subscribed($uid, $moodleforum, $did, $iscm);
                if (!$issubscribed) {
                    continue;
                }

                // Check whether the user unsubscribed to the discussion after it was created.
                $subnow = \mod_moodleforum\subscriptions::fetch_discussion_subscription($moodleforum->id, $userto->id);
                if ($subnow AND isset($subnow[$post->discussion]) AND ($subnow[$post->discussion] > $post->created)) {
                    continue;
                }

                // Check whether the sending user is cached already.
                if (array_key_exists($post->userid, $users)) {
                    $userfrom = $users[$post->userid];
                } else {
                    // We dont know the the user yet.

                    // Retrieve the user from the database.
                    $userfrom = $DB->get_record('user', array('id' => $post->userid));
                    if ($userfrom) {
                        moodleforum_minimise_user_record($userfrom);
                    } else {
                        $uid = $post->userid;
                        $pid = $post->id;
                        mtrace('Could not find user ' . $uid . ', author of post ' . $pid . '. Unable to send message.');
                        continue;
                    }
                }

                // Setup roles and languages.
                cron_setup_user($userto, $course);

                // Cache the users capability to view full names.
                if (!isset($userto->viewfullnames[$moodleforum->id])) {

                    // Find the context module.
                    $modulecontext = context_module::instance($cm->id);

                    // Check the users capabilities.
                    $userto->viewfullnames[$moodleforum->id] = has_capability('moodle/site:viewfullnames', $modulecontext);
                }

                // Cache the users capability to post in the discussion.
                if (!isset($userto->canpost[$discussion->id])) {

                    // Find the context module.
                    $modulecontext = context_module::instance($cm->id);

                    // Check the users capabilities.
                    $canpost = moodleforum_user_can_post($moodleforum, $userto, $cm, $course, $modulecontext);
                    $userto->canpost[$discussion->id] = $canpost;
                }

                // Make sure the current user is allowed to see the post.
                if (!moodleforum_user_can_see_post($moodleforum, $discussion, $post, null, $cm)) {
                    mtrace('User ' . $userto->id . ' can not see ' . $post->id . '. Not sending message.');
                    continue;
                }

                // Sent the email.

                // Preapare to actually send the post now. Build up the content.
                $cleanname     = str_replace('"', "'", strip_tags(format_string($moodleforum->name)));
                $coursecontext = context_course::instance($course->id);
                $shortname     = format_string($course->shortname, true, array('context' => $coursecontext));

                // Define a header to make mails easier to track.
                $emailmessageid          = generate_email_messageid('moodlemoodleforum' . $moodleforum->id);
                $userfrom->customheaders = array(
                    'List-Id: "' . $cleanname . '" ' . $emailmessageid,
                    'List-Help: ' . $CFG->wwwroot . '/mod/moodleforum/view.php?m=' . $moodleforum->id,
                    'Message-ID: ' . generate_email_messageid(hash('sha256', $post->id . 'to' . $userto->id)),
                    'X-Course-Id: ' . $course->id,
                    'X-Course-Name: ' . format_string($course->fullname, true),

                    // Headers to help prevent auto-responders.
                    'Precedence: Bulk',
                    'X-Auto-Response-Suppress: All',
                    'Auto-Submitted: auto-generated',
                );

                // Cache the users capabilities.
                if (!isset($userto->canpost[$discussion->id])) {
                    $canreply = moodleforum_user_can_post($moodleforum, $userto, $cm, $course, $modulecontext);
                } else {
                    $canreply = $userto->canpost[$discussion->id];
                }

                // Format the data.
                $data = new \mod_moodleforum\output\moodleforum_email(
                    $course,
                    $cm,
                    $moodleforum,
                    $discussion,
                    $post,
                    $userfrom,
                    $userto,
                    $canreply
                );

                // Retrieve the unsubscribe-link.
                $userfrom->customheaders[] = sprintf('List-Unsubscribe: <%s>', $data->get_unsubscribediscussionlink());

                // Check the capabilities to view full names.
                if (!isset($userto->viewfullnames[$moodleforum->id])) {
                    $data->viewfullnames = has_capability('moodle/site:viewfullnames', $modulecontext, $userto->id);
                } else {
                    $data->viewfullnames = $userto->viewfullnames[$moodleforum->id];
                }

                // Retrieve needed variables for the mail.
                $var                     = new \stdClass();
                $var->subject            = $data->get_subject();
                $var->moodleforumname = $cleanname;
                $var->sitefullname       = format_string($site->fullname);
                $var->siteshortname      = format_string($site->shortname);
                $var->courseidnumber     = $data->get_courseidnumber();
                $var->coursefullname     = $data->get_coursefullname();
                $var->courseshortname    = $data->get_coursename();
                $postsubject             = html_to_text(get_string('postmailsubject', 'moodleforum', $var), 0);
                $rootid                  = generate_email_messageid(hash('sha256', $discussion->firstpost . 'to' . $userto->id));

                // Check whether the post is a reply.
                if ($post->parent) {

                    // Add a reply header.
                    $parentid                  = generate_email_messageid(hash('sha256', $post->parent . 'to' . $userto->id));
                    $userfrom->customheaders[] = "In-Reply-To: $parentid";

                    // Comments need a reference to the starting post as well.
                    if ($post->parent != $discussion->firstpost) {
                        $userfrom->customheaders[] = "References: $rootid $parentid";
                    } else {
                        $userfrom->customheaders[] = "References: $parentid";
                    }
                }

                // Send the post now.
                mtrace('Sending ', '');

                // Create the message event.
                $eventdata                    = new \core\message\message();
                $eventdata->courseid          = $course->id;
                $eventdata->component         = 'mod_moodleforum';
                $eventdata->name              = 'posts';
                $eventdata->userfrom          = $userfrom;
                $eventdata->userto            = $userto;
                $eventdata->subject           = $postsubject;
                $eventdata->fullmessage       = $textout->render($data);
                $eventdata->fullmessageformat = FORMAT_PLAIN;
                $eventdata->fullmessagehtml   = $htmlout->render($data);
                $eventdata->notification      = 1;

                // Initiate another message array.
                $small                     = new \stdClass();
                $small->user               = fullname($userfrom);
                $formatedstring            = format_string($moodleforum->name, true);
                $small->moodleforumname = "$shortname: " . $formatedstring . ": " . $discussion->name;
                $small->message            = $post->message;

                // Make sure the language is correct.
                $usertol                 = $userto->lang;
                $eventdata->smallmessage = get_string_manager()->get_string('smallmessage', 'moodleforum', $small, $usertol);

                // Generate the url to view the post.
                $url                       = '/mod/moodleforum/discussion.php';
                $params                    = array('d' => $discussion->id);
                $contexturl                = new moodle_url($url, $params, 'p' . $post->id);
                $eventdata->contexturl     = $contexturl->out();
                $eventdata->contexturlname = $discussion->name;

                // Actually send the message.
                $mailsent = message_send($eventdata);

                // Check whether the sending failed.
                if (!$mailsent) {
                    mtrace('Error: mod/moodleforum/classes/task/send_mail.php execute(): ' .
                        "Could not send out mail for id $post->id to user $userto->id ($userto->email) .. not trying again.");
                    $errorcount[$post->id]++;
                } else {
                    $mailcount[$post->id]++;
                }

                // Tracing message.
                mtrace('post ' - $post->id . ': ' . $discussion->name);
            }

            // Release the memory.
            unset($userto);
        }
    }

    // Check for all posts whether errors occurred.
    if ($posts) {

        // Loop through all posts.
        foreach ($posts as $post) {

            // Tracing information.
            mtrace($mailcount[$post->id] . " users were sent post $post->id, '$discussion->name'");

            // Mark the posts with errors in the database.
            if ($errorcount[$post->id]) {
                $DB->set_field('moodleforum_posts', 'mailed', moodleforum_MAILED_ERROR, array('id' => $post->id));
            }
        }
    }

    // The task was completed.
    return true;
}

/**
 * Returns a list of all posts that have not been mailed yet.
 *
 * @param int $starttime posts created after this time
 * @param int $endtime   posts created before this time
 *
 * @return array
 */
function moodleforum_get_unmailed_posts($starttime, $endtime) {
    global $DB;

    // Set params for the sql query.
    $params               = array();
    $params['mailed']     = moodleforum_MAILED_PENDING;
    $params['ptimestart'] = $starttime;
    $params['ptimeend']   = $endtime;

    // Retrieve the records.
    $sql = "SELECT p.*, d.course, d.moodleforum
            FROM {moodleforum_posts} p
            JOIN {moodleforum_discussions} d ON d.id = p.discussion
            WHERE p.mailed = :mailed AND p.created >= :ptimestart AND p.created < :ptimeend
            ORDER BY p.modified ASC";

    return $DB->get_records_sql($sql, $params);
}

/**
 * Marks posts before a certain time as being mailed already.
 *
 * @param int $endtime
 *
 * @return bool
 */
function moodleforum_mark_old_posts_as_mailed($endtime) {
    global $DB;

    // Get the current timestamp.
    $now = time();

    // Define variables for the sql query.
    $params                  = array();
    $params['mailedsuccess'] = moodleforum_MAILED_SUCCESS;
    $params['now']           = $now;
    $params['endtime']       = $endtime;
    $params['mailedpending'] = moodleforum_MAILED_PENDING;

    // Define the sql query.
    $sql = "UPDATE {moodleforum_posts}
            SET mailed = :mailedsuccess
            WHERE (created < :endtime) AND mailed = :mailedpending";

    return $DB->execute($sql, $params);

}

/**
 * Removes unnecessary information from the user records for the mail generation.
 *
 * @param stdClass $user
 */
function moodleforum_minimise_user_record(stdClass $user) {

    // Remove all information for the mail generation that are not needed.
    unset($user->institution);
    unset($user->department);
    unset($user->address);
    unset($user->city);
    unset($user->url);
    unset($user->currentlogin);
    unset($user->description);
    unset($user->descriptionformat);
}

/**
 * Adds information about unread messages, that is only required for the course view page (and
 * similar), to the course-module object.
 *
 * @param cm_info $cm Course-module object
 */
function moodleforum_cm_info_view(cm_info $cm) {

    $cantrack = \mod_moodleforum\readtracking::moodleforum_can_track_moodleforums();
    if ($cantrack) {
        $unread = \mod_moodleforum\readtracking::moodleforum_count_unread_posts_moodleforum($cm,
            $cm->get_course());
        if ($unread) {
            $out = '<span class="unread"> <a href="' . $cm->url . '">';
            if ($unread == 1) {
                $out .= get_string('unreadpostsone', 'moodleforum');
            } else {
                $out .= get_string('unreadpostsnumber', 'moodleforum', $unread);
            }
            $out .= '</a></span>';
            $cm->set_after_link($out);
        }
    }
}

/**
 * Check if the user can create attachments in moodleforum.
 *
 * @param  stdClass $moodleforum moodleforum object
 * @param  stdClass $context        context object
 *
 * @return bool true if the user can create attachments, false otherwise
 * @since  Moodle 3.3
 */
function moodleforum_can_create_attachment($moodleforum, $context) {
    // If maxbytes == 1 it means no attachments at all.
    if (empty($moodleforum->maxattachments) || $moodleforum->maxbytes == 1 ||
        !has_capability('mod/moodleforum:createattachment', $context)
    ) {
        return false;
    }

    return true;
}
