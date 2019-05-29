<?php
// This file is part of a plugin for Moodle - http://moodle.org/
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
 * moodleforum readtracking manager.
 *
 * @package   mod_moodleforum
 * @copyright 2017 Kennet Winter <k_wint10@uni-muenster.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_moodleforum;

defined('MOODLE_INTERNAL') || die();

/**
 * Static methods for managing the tracking of read posts and discussions.
 *
 * @package   mod_moodleforum
 * @copyright 2017 Kennet Winter <k_wint10@uni-muenster.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class readtracking {

    /**
     * Determine if a user can track moodleforums and optionally a particular moodleforum instance.
     * Checks the site settings and the moodleforum settings (if requested).
     *
     * @param object $moodleforum
     *
     * @return boolean
     * */
    public static function moodleforum_can_track_moodleforums($moodleforum = null) {
        global $USER;

        // Check if readtracking is disabled for the module.
        if (!get_config('moodleforum', 'trackreadposts')) {
            return false;
        }

        // Guests are not allowed to track moodleforums.
        if (isguestuser($USER) OR empty($USER->id)) {
            return false;
        }

        // If no specific moodleforum is submitted, check the modules basic settings.
        if (is_null($moodleforum)) {
            if (get_config('moodleforum', 'allowforcedreadtracking')) {
                // Since we can force tracking, assume yes without a specific forum.
                return true;
            } else {
                // User tracks moodleforums by default.
                return true;
            }
        }
        // Check the settings of the moodleforum instance.
        $allowed = ($moodleforum->trackingtype == moodleforum_TRACKING_OPTIONAL);
        $forced  = ($moodleforum->trackingtype == moodleforum_TRACKING_FORCED);

        return ($allowed || $forced);
    }

    /**
     * Tells whether a specific moodleforum is tracked by the user.
     *
     * @param object      $moodleforum
     * @param object|null $user
     *
     * @return bool
     */
    public static function moodleforum_is_tracked($moodleforum, $user = null) {
        global $USER, $DB;

        // Get the user.
        if (is_null($user)) {
            $user = $USER;
        }

        // Guests cannot track a moodleforum.
        if (isguestuser($USER) OR empty($USER->id)) {
            return false;
        }

        // Check if the moodleforum can be generally tracked.
        if (!self::moodleforum_can_track_moodleforums($moodleforum)) {
            return false;
        }

        // Check the settings of the moodleforum instance.
        $allowed = ($moodleforum->trackingtype == moodleforum_TRACKING_OPTIONAL);
        $forced  = ($moodleforum->trackingtype == moodleforum_TRACKING_FORCED);

        // Check the preferences of the user.
        $userpreference = $DB->get_record('moodleforum_tracking',
            array('userid' => $user->id, 'moodleforumid' => $moodleforum->id));

        // Return the boolean.
        if (get_config('moodleforum', 'allowforcedreadtracking')) {
            return ($forced || ($allowed && $userpreference === false));
        } else {
            return (($allowed || $forced) && $userpreference === false);
        }
    }

    /**
     * Marks a specific moodleforum instance as read by a specific user.
     *
     * @param object $cm
     * @param null   $userid
     */
    public static function moodleforum_mark_moodleforum_read($cm, $userid = null) {
        global $USER;

        // If no user is submitted, use the current one.
        if (!isset($userid)) {
            $userid = $USER->id;
        }

        // Get all the discussions with unread messages in this moodleforum instance.
        $discussions = moodleforum_get_discussions_unread($cm);

        // Iterate through all of this discussions.
        foreach ($discussions as $discussionid => $amount) {

            // Mark the discussion as read.
            if (!self::moodleforum_mark_discussion_read($discussionid, $userid)) {
                print_error('markreadfailed', 'moodleforum');

                return false;
            }
        }

        return true;
    }

    /**
     * Marks a specific discussion as read by a specific user.
     *
     * @param int  $discussionid
     * @param null $userid
     */
    public static function moodleforum_mark_discussion_read($discussionid, $userid = null) {
        global $USER;

        // Get all posts.
        $posts = moodleforum_get_all_discussion_posts($discussionid, true);

        // If no user is submitted, use the current one.
        if (!isset($userid)) {
            $userid = $USER->id;
        }

        // Iterate through all posts of the discussion.
        foreach ($posts as $post) {

            // Ignore already read posts.
            if (!is_null($post->postread)) {
                continue;
            }

            // Mark the post as read.
            if (!self::moodleforum_mark_post_read($userid, $post)) {
                print_error('markreadfailed', 'moodleforum');

                return false;
            }
        }

        // The discussion has been marked as read.
        return true;
    }

    /**
     * Marks a specific post as read by a specific user.
     *
     * @param int    $userid
     * @param object $post
     *
     * @return bool
     */
    public static function moodleforum_mark_post_read($userid, $post) {

        // If the post is older than the limit.
        if (self::moodleforum_is_old_post($post)) {
            return true;
        }

        // Create a new read record.
        return self::moodleforum_add_read_record($userid, $post->id);
    }

    /**
     * Checks if a post is older than the limit.
     *
     * @param object $post
     *
     * @return bool
     */
    public static function moodleforum_is_old_post($post) {

        // Transform objects into arrays.
        $post = (array) $post;

        // Get the current time.
        $currenttimestamp = time();

        // Calculate the time, where older posts are considered read.
        $oldposttimestamp = $currenttimestamp - (get_config('moodleforum', 'oldpostdays') * 24 * 3600);

        // Return if the post is newer than that time.
        return ($post['modified'] < $oldposttimestamp);
    }

    /**
     * Mark a post as read by a user.
     *
     * @param int $userid
     * @param int $postid
     *
     * @return bool
     */
    public static function moodleforum_add_read_record($userid, $postid) {
        global $DB;

        // Get the current time and the cutoffdate.
        $now        = time();
        $cutoffdate = $now - (get_config('moodleforum', 'oldpostdays') * 24 * 3600);

        // Check for read records for this user an this post.
        $oldrecord = $DB->get_record('moodleforum_read', array('postid' => $postid, 'userid' => $userid));
        if (!$oldrecord) {

            // If there are no old records, create a new one.
            $sql = "INSERT INTO {moodleforum_read} (userid, postid, discussionid, moodleforumid, firstread, lastread)
                 SELECT ?, p.id, p.discussion, d.moodleforum, ?, ?
                   FROM {moodleforum_posts} p
                        JOIN {moodleforum_discussions} d ON d.id = p.discussion
                  WHERE p.id = ? AND p.modified >= ?";

            return $DB->execute($sql, array($userid, $now, $now, $postid, $cutoffdate));
        }

        // Else update the existing one.
        $sql = "UPDATE {moodleforum_read}
                   SET lastread = ?
                 WHERE userid = ? AND postid = ?";

        return $DB->execute($sql, array($now, $userid, $userid));
    }

    /**
     * Deletes read record for the specified index.
     * At least one parameter must be specified.
     *
     * @param int $userid
     * @param int $postid
     * @param int $discussionid
     * @param int $overflowid
     *
     * @return bool
     */
    public static function moodleforum_delete_read_records($userid = -1, $postid = -1, $discussionid = -1, $overflowid = -1) {
        global $DB;

        // Initiate variables.
        $params = array();
        $select = '';

        // Create the sql-Statement depending on the submitted parameters.
        if ($userid > -1) {
            if ($select != '') {
                $select .= ' AND ';
            }
            $select   .= 'userid = ?';
            $params[] = $userid;
        }
        if ($postid > -1) {
            if ($select != '') {
                $select .= ' AND ';
            }
            $select   .= 'postid = ?';
            $params[] = $postid;
        }
        if ($discussionid > -1) {
            if ($select != '') {
                $select .= ' AND ';
            }
            $select   .= 'discussionid = ?';
            $params[] = $discussionid;
        }
        if ($overflowid > -1) {
            if ($select != '') {
                $select .= ' AND ';
            }
            $select   .= 'moodleforumid = ?';
            $params[] = $overflowid;
        }

        // Check if at least one parameter was specified.
        if ($select == '') {
            return false;
        } else {
            return $DB->delete_records_select('moodleforum_read', $select, $params);
        }
    }

    /**
     * Deletes all read records that are related to posts that are older than the cutoffdate.
     * This function is only called by the modules cronjob.
     */
    public static function moodleforum_clean_read_records() {
        global $DB;

        // Stop if there cannot be old posts.
        if (!get_config('moodleforum', 'oldpostdays')) {
            return;
        }

        // Find the timestamp for records older than allowed.
        $cutoffdate = time() - (get_config('moodleforum', 'oldpostdays') * 24 * 60 * 60);

        // Find the timestamp of the oldest read record.
        // This will speedup the delete query.
        $sql = "SELECT MIN(p.modified) AS first
                FROM {moodleforum_posts} p
                JOIN {moodleforum_read} r ON r.postid = p.id";

        // If there is no old read record, end this method.
        if (!$first = $DB->get_field_sql($sql)) {
            return;
        }

        // Delete the old read tracking information between that timestamp and the cutoffdate.
        $sql = "DELETE
                FROM {moodleforum_read}
                WHERE postid IN (SELECT p.id
                                 FROM {moodleforum_posts} p
                                 WHERE p.modified >= ? AND p.modified < ?)";
        $DB->execute($sql, array($first, $cutoffdate));
    }

    /**
     * Stop to track a moodleforum instance.
     *
     * @param int $moodleforumid The moodleforum ID
     * @param int $userid           The user ID
     *
     * @return bool Whether the deletion was successful
     */
    public static function moodleforum_stop_tracking($moodleforumid, $userid = null) {
        global $USER, $DB;

        // Set the user.
        if (is_null($userid)) {
            $userid = $USER->id;
        }

        // Check if the user already stopped to track the moodleforum.
        $params    = array('userid' => $userid, 'moodleforumid' => $moodleforumid);
        $isstopped = $DB->record_exists('moodleforum_tracking', $params);

        // Stop tracking the moodleforum if not already stopped.
        if (!$isstopped) {

            // Create the tracking object.
            $tracking                   = new \stdClass();
            $tracking->userid           = $userid;
            $tracking->moodleforumid = $moodleforumid;

            // Insert into the database.
            $DB->insert_record('moodleforum_tracking', $params);
        }

        // Delete all connected read records.
        $deletion = self::moodleforum_delete_read_records($userid, -1, -1, $moodleforumid);

        // Return whether the deletion was successful.
        return $deletion;
    }

    /**
     * Start to track a moodleforum instance.
     *
     * @param int $moodleforumid The moodleforum ID
     * @param int $userid           The user ID
     *
     * @return bool Whether the deletion was successful
     */
    public static function moodleforum_start_tracking($moodleforumid, $userid = null) {
        global $USER, $DB;

        // Get the current user.
        if (is_null($userid)) {
            $userid = $USER->id;
        }

        // Delete the tracking setting of this user for this moodleforum.
        return $DB->delete_records('moodleforum_tracking', array('userid' => $userid, 'moodleforumid' => $moodleforumid));
    }

    /**
     * Get a list of forums not tracked by the user.
     *
     * @param int $userid   The user ID
     * @param int $courseid The course ID
     *
     * @return array Array with untracked moodleforums
     */
    public static function get_untracked_moodleforums($userid, $courseid) {
        global $DB;

        // Check whether readtracking may be forced.
        if (get_config('moodleforum', 'allowforcedreadtracking')) {

            // Create a part of a sql-statement.
            $trackingsql = "AND (m.trackingtype = " . moodleforum_TRACKING_OFF . "
                            OR (m.trackingtype = " . moodleforum_TRACKING_OPTIONAL . " AND mt.id IS NOT NULL))";
        } else {
            // Readtracking may be forced.

            // Create another sql-statement.
            $trackingsql = "AND (m.trackingtype = " . moodleforum_TRACKING_OFF .
                " OR ((m.trackingtype = " . moodleforum_TRACKING_OPTIONAL .
                " OR m.trackingtype = " . moodleforum_TRACKING_FORCED . ") AND mt.id IS NOT NULL))";
        }

        // Create the sql-queryx.
        $sql = "SELECT m.id
                  FROM {moodleforum} m
             LEFT JOIN {moodleforum_tracking} mt ON (mt.moodleforumid = m.id AND mt.userid = ?)
                 WHERE m.course = ? $trackingsql";

        // Get all untracked moodleforums from the database.
        $moodleforums = $DB->get_records_sql($sql, array($userid, $courseid, $userid));

        // Check whether there are no untracked moodleforums.
        if (!$moodleforums) {
            return array();
        }

        // Loop through all moodleforums.
        foreach ($moodleforums as $moodleforum) {
            $moodleforums[$moodleforum->id] = $moodleforum;
        }

        // Return all untracked moodleforums.
        return $moodleforums;
    }

    /**
     * Get number of unread posts in a moodleforum instance.
     *
     * @param object    $cm
     * @param \stdClass $course The course the moodleforum is in
     *
     * @return int|mixed
     */
    public static function moodleforum_count_unread_posts_moodleforum($cm, $course) {
        global $CFG, $DB, $USER;

        // Create a cache.
        static $readcache = array();

        // Get the moodleforum ids.
        $moodleforumid = $cm->instance;

        // Check whether the cache is already set.
        if (!isset($readcache[$course->id])) {

            // Create a cache for the course.
            $readcache[$course->id] = array();

            // Count the unread posts in the course.
            $counts = self::moodleforum_count_unread_posts_course($USER->id, $course->id);
            if ($counts) {

                // Loop through all unread posts.
                foreach ($counts as $count) {
                    $readcache[$course->id][$count->id] = $count->unread;
                }
            }
        }

        // Check whether there are no unread post for this moodleforum.
        if (empty($readcache[$course->id][$moodleforumid])) {
            return 0;
        }

        // Require the course library.
        require_once($CFG->dirroot . '/course/lib.php');

        // Get the current timestamp and the cutoffdate.
        $now        = round(time(), -2);
        $cutoffdate = $now - (get_config('moodleforum', 'oldpostdays') * 24 * 60 * 60);

        // Define a sql-query.
        $params = array($USER->id, $moodleforumid, $cutoffdate);
        $sql    = "SELECT COUNT(p.id)
                  FROM {moodleforum_posts} p
                  JOIN {moodleforum_discussions} d ON p.discussion = d.id
             LEFT JOIN {moodleforum_read} r ON (r.postid = p.id AND r.userid = ?)
                 WHERE d.moodleforum = ? AND p.modified >= ? AND r.id IS NULL";

        // Return the number of unread posts per moodleforum.
        return $DB->get_field_sql($sql, $params);
    }

    /**
     * Get an array of unread posts within a course.
     *
     * @param int $userid   The user ID
     * @param int $courseid The course ID
     *
     * @return array Array of unread posts within a course
     */
    public static function moodleforum_count_unread_posts_course($userid, $courseid) {
        global $DB;

        // Get the current timestamp and calculate the cutoffdate.
        $now        = round(time(), -2);
        $cutoffdate = $now - (get_config('moodleforum', 'oldpostdays') * 24 * 60 * 60);

        // Set parameters for the sql-query.
        $params = array($userid, $userid, $courseid, $cutoffdate, $userid);

        // Check if forced readtracking is allowed.
        if (get_config('moodleforum', 'allowforcedreadtracking')) {
            $trackingsql = "AND (m.trackingtype = " . moodleforum_TRACKING_FORCED .
                " OR (m.trackingtype = " . moodleforum_TRACKING_OPTIONAL . " AND tm.id IS NULL))";
        } else {
            $trackingsql = "AND ((m.trackingtype = " . moodleforum_TRACKING_OPTIONAL . " OR m.trackingtype = " .
                moodleforum_TRACKING_FORCED . ") AND tm.id IS NULL)";
        }

        // Define the sql-query.
        $sql = "SELECT m.id, COUNT(p.id) AS unread
                  FROM {moodleforum_posts} p
                  JOIN {moodleforum_discussions} d ON d.id = p.discussion
                  JOIN {moodleforum} m ON m.id = d.moodleforum
                  JOIN {course} c ON c.id = m.course
             LEFT JOIN {moodleforum_read} r ON (r.postid = p.id AND r.userid = ?)
             LEFT JOIN {moodleforum_tracking} tm ON (tm.userid = ? AND tm.moodleforumid = m.id)
                 WHERE m.course = ? AND p.modified >= ? AND r.id IS NULL $trackingsql
              GROUP BY m.id";

        // Get the amount of unread post within a course.
        $return = $DB->get_records_sql($sql, $params);
        if ($return) {
            return $return;
        }

        // Else return nothing.
        return array();
    }
}