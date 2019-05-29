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
 * moodleforum subscription manager.
 *
 * This file is created by borrowing code from the mod_forum module.
 *
 * @package   mod_moodleforum
 * @copyright 2017 Kennet Winter <k_wint10@uni-muenster.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_moodleforum;

defined('MOODLE_INTERNAL') || die();

/**
 * moodleforum subscription manager.
 *
 * @package   mod_moodleforum
 * @copyright 2017 Kennet Winter <k_wint10@uni-muenster.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class subscriptions {

    /**
     * The status value for an unsubscribed discussion.
     *
     * @var int
     */
    const moodleforum_DISCUSSION_UNSUBSCRIBED = -1;

    /**
     * The subscription cache for moodleforums.
     *
     * The first level key is the user ID
     * The second level is the moodleforum ID
     * The Value then is bool for subscribed of not.
     *
     * @var array[] An array of arrays.
     */
    protected static $moodleforumcache = array();

    /**
     * The list of moodleforums which have been wholly retrieved for the subscription cache.
     *
     * This allows for prior caching of an entire moodleforum to reduce the
     * number of DB queries in a subscription check loop.
     *
     * @var bool[]
     */
    protected static $fetchedmoodleforums = array();

    /**
     * The subscription cache for moodleforum discussions.
     *
     * The first level key is the user ID
     * The second level is the moodleforum ID
     * The third level key is the discussion ID
     * The value is then the users preference (int)
     *
     * @var array[]
     */
    protected static $discussioncache = array();

    /**
     * The list of moodleforums which have been wholly retrieved for the discussion subscription cache.
     *
     * This allows for prior caching of an entire moodleforums to reduce the
     * number of DB queries in a subscription check loop.
     *
     * @var bool[]
     */
    protected static $fetcheddiscussions = array();

    /**
     * Returns whether a user is subscribed to this moodleforum or a specific discussion within the moodleforum.
     *
     * If a discussion is specified then report whether the user is subscribed to posts to this
     * particular discussion, taking into account the moodleforum preference.
     * If it is not specified then considere only the moodleforums preference.
     *
     * @param int    $userid
     * @param object $moodleforum
     * @param null   $discussionid
     *
     * @return bool
     */
    public static function is_subscribed($userid, $moodleforum, $discussionid = null) {

        // Is the user forced to be subscribed to the moodleforum?
        if (self::is_forcesubscribed($moodleforum)) {
            return true;
        }

        // Check the moodleforum instance if no discussionid is submitted.
        if (is_null($discussionid)) {
            return self::is_subscribed_to_moodleforum($userid, $moodleforum);
        }

        // The subscription details for the discussion needs to be checked.
        $subscriptions = self::fetch_discussion_subscription($moodleforum->id, $userid);

        // Check if there is a record for the discussion.
        if (isset($subscriptions[$discussionid])) {
            return ($subscriptions[$discussionid]) != self::moodleforum_DISCUSSION_UNSUBSCRIBED;
        }

        // Return whether the user is subscribed to the forum.
        return self::is_subscribed_to_moodleforum($userid, $moodleforum);
    }

    /**
     * Helper to determine whether a moodleforum has it's subscription mode set to forced.
     *
     * @param object $moodleforum The record of the moodleforum to test
     *
     * @return bool
     */
    public static function is_forcesubscribed($moodleforum) {
        return ($moodleforum->forcesubscribe == moodleforum_FORCESUBSCRIBE);
    }

    /**
     * Whether a user is subscribed to this moodloverflow.
     *
     * @param int    $userid         The user ID
     * @param object $moodleforum The record of the moodleforum to test
     *
     * @return boolean
     */
    private static function is_subscribed_to_moodleforum($userid, $moodleforum) {
        return self::fetch_subscription_cache($moodleforum->id, $userid);
    }

    /**
     * Fetch the moodleforum subscription data for the specified userid an moodleforum.
     *
     * @param int $moodleforumid The forum to retrieve a cache for
     * @param int $userid           The user ID
     *
     * @return boolean
     */
    public static function fetch_subscription_cache($moodleforumid, $userid) {

        // If the cache is already filled, return the result.
        if (isset(self::$moodleforumcache[$userid]) AND isset(self::$moodleforumcache[$userid][$moodleforumid])) {
            return self::$moodleforumcache[$userid][$moodleforumid];
        }

        // Refill the cache.
        self::fill_subscription_cache($moodleforumid, $userid);

        // Catch empty results.
        if (!isset(self::$moodleforumcache[$userid]) OR !isset(self::$moodleforumcache[$userid][$moodleforumid])) {
            return false;
        }

        // Else return the subscription state.
        return self::$moodleforumcache[$userid][$moodleforumid];
    }

    /**
     * Fill the moodleforum subscription data for the specified userid an moodleforum.
     *
     * If the userid is not specified, then all subscription data for that moodleforum is fetched
     * in a single query and is used for subsequent lookups without requiring further database queries.
     *
     * @param int  $moodleforumid The moodleforum to retrieve a cache for
     * @param null $userid           The user ID
     */
    public static function fill_subscription_cache($moodleforumid, $userid = null) {
        global $DB;

        // Check if the moodleforum has not been fetched as a whole.
        if (!isset(self::$fetchedmoodleforums[$moodleforumid])) {

            // Is a specified user requested?
            if (isset($userid)) {

                // Create the cache for the user.
                if (!isset(self::$moodleforumcache[$userid])) {
                    self::$moodleforumcache[$userid] = array();
                }

                // Check if the user is subscribed to the moodleforum.
                if (!isset(self::$moodleforumcache[$userid][$moodleforumid])) {

                    // Request to the database.
                    $params = array('userid' => $userid, 'moodleforum' => $moodleforumid);
                    if ($DB->record_exists('moodleforum_subscriptions', $params)) {
                        self::$moodleforumcache[$userid][$moodleforumid] = true;
                    } else {
                        self::$moodleforumcache[$userid][$moodleforumid] = false;
                    }
                }

            } else { // The request is not connected to a specific user.

                // Request all records.
                $params        = array('moodleforum' => $moodleforumid);
                $subscriptions = $DB->get_recordset('moodleforum_subscriptions', $params, '', 'id, userid');

                // Loop through the records.
                foreach ($subscriptions as $id => $data) {

                    // Create a new record if necessary.
                    if (!isset(self::$moodleforumcache[$data->userid])) {
                        self::$moodleforumcache[$data->userid] = array();
                    }

                    // Mark the subscription state.
                    self::$moodleforumcache[$data->userid][$moodleforumid] = true;
                }

                // Mark the moodleforum as fetched.
                self::$fetchedmoodleforums[$moodleforumid] = true;
                $subscriptions->close();
            }
        }
    }


    /**
     * This is returned as an array of discussions for that moodleforum which contain the preference in a stdClass.
     *
     * @param int  $moodleforumid The moodleforum ID
     * @param null $userid           The user ID
     *
     * @return array of stClass objects
     */
    public static function fetch_discussion_subscription($moodleforumid, $userid = null) {

        // Fill the discussion cache.
        self::fill_discussion_subscription_cache($moodleforumid, $userid);

        // Create an array, if there is no record.
        if (!isset(self::$discussioncache[$userid]) OR !isset(self::$discussioncache[$userid][$moodleforumid])) {
            return array();
        }

        // Return the cached subscription state.
        return self::$discussioncache[$userid][$moodleforumid];
    }

    /**
     * Fill the discussion subscription data for the specified user ID and moodleforum.
     *
     * If the user ID is not specified, all discussion subscription data for that moodleforum is
     * fetched in a single query and is used for subsequent lookups without requiring further database queries.
     *
     * @param int  $moodleforumid The moodleforum ID
     * @param null $userid           The user ID
     */
    public static function fill_discussion_subscription_cache($moodleforumid, $userid = null) {
        global $DB;

        // Check if the discussions of this moodleforum has been fetched as a whole.
        if (!isset(self::$fetcheddiscussions[$moodleforumid])) {

            // Check if data for a specific user is requested.
            if (isset($userid)) {

                // Create a new record if necessary.
                if (!isset(self::$discussioncache[$userid])) {
                    self::$discussioncache[$userid] = array();
                }

                // Check if the moodleforum instance is already cached.
                if (!isset(self::$discussioncache[$userid][$moodleforumid])) {

                    // Get all records.
                    $params        = array('userid' => $userid, 'moodleforum' => $moodleforumid);
                    $subscriptions = $DB->get_recordset('moodleforum_discuss_subs', $params,
                        null, 'id, discussion, preference');

                    // Loop through all of these and add them to the discussion cache.
                    foreach ($subscriptions as $id => $data) {
                        self::add_to_discussion_cache($moodleforumid, $userid, $data->discussion, $data->preference);
                    }

                    // Close the record set.
                    $subscriptions->close();
                }

            } else {
                // No user ID is submitted.

                // Get all records.
                $params        = array('moodleforum' => $moodleforumid);
                $subscriptions = $DB->get_recordset('moodleforum_discuss_subs', $params,
                    null, 'id, userid, discussion, preference');

                // Loop throuch all of them and add them to the discussion cache.
                foreach ($subscriptions as $id => $data) {
                    self::add_to_discussion_cache($moodleforumid, $data->userid, $data->discussion, $data->preference);
                }

                // Mark the discussions as fetched and close the recordset.
                self::$fetcheddiscussions[$moodleforumid] = true;
                $subscriptions->close();
            }
        }
    }

    /**
     * Add the specified discussion and the users preference to the discussion subscription cache.
     *
     * @param int $moodleforumid The moodleforum ID
     * @param int $userid           The user ID
     * @param int $discussion       The discussion ID
     * @param int $preference       The preference to store
     */
    private static function add_to_discussion_cache($moodleforumid, $userid, $discussion, $preference) {

        // Create a new array for the user if necessary.
        if (!isset(self::$discussioncache[$userid])) {
            self::$discussioncache[$userid] = array();
        }

        // Create a new array for the moodleforum if necessary.
        if (!isset(self::$discussioncache[$userid][$moodleforumid])) {
            self::$discussioncache[$userid][$moodleforumid] = array();
        }

        // Save the users preference for that discussion in this array.
        self::$discussioncache[$userid][$moodleforumid][$discussion] = $preference;
    }

    /**
     * Determines whether a moodleforum has it's subscription mode set to disabled.
     *
     * @param object $moodleforum The moodleforum ID
     *
     * @return bool
     */
    public static function subscription_disabled($moodleforum) {
        return ($moodleforum->forcesubscribe == moodleforum_DISALLOWSUBSCRIBE);
    }

    /**
     * Checks wheter the specified moodleforum can be subscribed to.
     *
     * @param object $moodleforum The moodleforum ID
     *
     * @return boolean
     */
    public static function is_subscribable($moodleforum) {

        // Check if the user is an authenticated user.
        $authenticated = (isloggedin() AND !isguestuser());

        // Check if subscriptions are disabled for the moodleforum.
        $disabled = self::subscription_disabled($moodleforum);

        // Check if the moodleforum forces the user to be subscribed.
        $forced = self::is_forcesubscribed($moodleforum);

        // Return the result.
        return ($authenticated AND !$forced AND !$disabled);
    }

    /**
     * Set the moodleforum subscription mode.
     *
     * By default when called without options, this is set to moodleforum_FORCESUBSCRIBE.
     *
     * @param int $moodleforumid The moodleforum ID
     * @param int $status           The new subscrription status
     *
     * @return bool
     */
    public static function set_subscription_mode($moodleforumid, $status = 1) {
        global $DB;

        // Change the value in the database.
        return $DB->set_field('moodleforum', 'forcesubscribe', $status, array('id' => $moodleforumid));
    }

    /**
     * Returns the current subscription mode for the moodleforum.
     *
     * @param object $moodleforum The moodleforum record
     *
     * @return int The moodleforum subscription mode
     */
    public static function get_subscription_mode($moodleforum) {
        return $moodleforum->forcesubscribe;
    }

    /**
     * Returns an array of moodleforum that the current user is subscribed to and is allowed to unsubscribe from.
     *
     * @return array Array of unsubscribable moodleforums
     */
    public static function get_unsubscribable_moodleforums() {
        global $USER, $DB;

        // Get courses that the current user is enrolled to.
        $courses = enrol_get_my_courses();
        if (empty($courses)) {
            return array();
        }

        // Get the IDs of all that courses.
        $courseids = array();
        foreach ($courses as $course) {
            $courseids[] = $course->id;
        }

        // Get a list of all moodleforums the user is connected to.
        list($coursesql, $courseparams) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'c');

        // Find all moodleforums from the user's courses that they are subscribed to and which are not set to forced.
        // It is possible for users to be subscribed to a moodleoveflow in subscriptions disallowed mode so they must be
        // listed here so that they can be unsubscribed from.
        $sql             = "SELECT m.id, cm.id as cm, m.course
                FROM {moodleforum} m
                JOIN {course_modules} cm ON cm.instance = m.id
                JOIN {modules} mo ON mo.name = :modulename AND mo.id = cm.module
                LEFT JOIN {moodleforum_subscriptions} ms ON (ms.moodleforum = m.id AND ms.userid = :userid)
                WHERE m.forcesubscribe <> :forcesubscribe AND ms.id IS NOT NULL AND cm.course $coursesql";
        $params          = array('modulename' => 'moodleforum',
                                 'userid' => $USER->id,
                                 'forcesubscribe' => moodleforum_FORCESUBSCRIBE);
        $mergedparams    = array_merge($courseparams, $params);
        $moodleforums = $DB->get_recordset_sql($sql, $mergedparams);

        // Loop through all of the results and add them to an array.
        $unsubscribablemoodleforums = array();
        foreach ($moodleforums as $moodleforum) {
            $unsubscribablemoodleforums[] = $moodleforum;
        }
        $moodleforums->close();

        // Return the array.
        return $unsubscribablemoodleforums;
    }

    /**
     * Get the list of potential subscribers to a moodleforum.
     *
     * @param \context_module $context The moodleforum context.
     * @param string          $fields  The list of fields to return for each user.
     * @param string          $sort    Sort order.
     *
     * @return array List of users.
     */
    public static function get_potential_subscribers($context, $fields, $sort = '') {
        global $DB;

        // Only enrolled users can subscribe.
        list($esql, $params) = get_enrolled_sql($context);

        // Default ordering of the list.
        if (!$sort) {
            list($sort, $sortparams) = users_order_by_sql('u');
            $params = array_merge($params, $sortparams);
        }

        // Fetch results from the database.
        $sql = "SELECT $fields
                FROM {user} u
                JOIN ($esql) je ON je.id = u.id
                ORDER BY $sort";

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Fill the moodleforum subscription data for all moodleforum that the user can subscribe to in a spevific course.
     *
     * @param int $courseid The course ID
     * @param int $userid   The user ID
     */
    public static function fill_subscription_cache_for_course($courseid, $userid) {
        global $DB;

        // Create an array for the user if necessary.
        if (!isset(self::$moodleforumcache[$userid])) {
            self::$moodleforumcache[$userid] = array();
        }

        // Fetch a record set for all moodleforumids and their subscription id.
        $sql           = "SELECT m.id AS moodleforumid,s.id AS subscriptionid
                  FROM {moodleforum} m
             LEFT JOIN {moodleforum_subscriptions} s ON (s.moodleforum = m.id AND s.userid = :userid)
                 WHERE m.course = :course AND m.forcesubscribe <> :subscriptionforced";
        $params        = array(
            'userid'             => $userid,
            'course'             => $courseid,
            'subscriptionforced' => moodleforum_FORCESUBSCRIBE,
        );
        $subscriptions = $DB->get_recordset_sql($sql, $params);

        // Loop through all records.
        foreach ($subscriptions as $id => $data) {
            self::$moodleforumcache[$userid][$id] = !empty($data->subscriptionid);
        }

        // Close the recordset.
        $subscriptions->close();
    }

    /**
     * Returns a list of user object who are subscribed to this moodleforum.
     *
     * @param stdClass        $moodleforum     The moodleforum record
     * @param \context_module $context            The moodleforum context
     * @param string          $fields             Requested user fields
     * @param boolean         $includediscussions Whether to take discussion subscriptions into consideration
     *
     * @return array list of users
     */
    public static function get_subscribed_users($moodleforum, $context, $fields = null, $includediscussions = false) {
        global $CFG, $DB;

        // Default fields if none are submitted.
        if (empty($fields)) {
            $allnames = get_all_user_name_fields(true, 'u');
            $fields   = "u.id, u.username, $allnames, u.maildisplay, u.mailformat, u.maildigest,
                u.imagealt, u.email, u.emailstop, u.city, u.country, u.lastaccess, u.lastlogin,
                u.picture, u.timezone, u.theme, u.lang, u.trackforums, u.mnethostid";
        }

        // Check if the user is forced to e subscribed to a moodleforum.
        if (self::is_forcesubscribed($moodleforum)) {

            // Find the list of potential subscribers.
            $results = self::get_potential_subscribers($context, $fields, 'u.email ASC');

        } else {

            // Only enrolled users can subscribe to a moodleforum.
            list($esql, $params) = get_enrolled_sql($context, '', 0, true);
            $params['moodleforumid'] = $moodleforum->id;

            // Check discussion subscriptions as well?
            if ($includediscussions) {

                // Determine more params.
                $params['smoodleforumid']  = $moodleforum->id;
                $params['dsmoodleforumid'] = $moodleforum->id;
                $params['unsubscribed']       = self::moodleforum_DISCUSSION_UNSUBSCRIBED;

                // SQL-statement to fetch all needed fields from the database.
                $sql = "SELECT $fields
                        FROM (
                            SELECT userid FROM {moodleforum_subscriptions} s
                            WHERE s.moodleforum = :smoodleforumid
                            UNION
                            SELECT userid FROM {moodleforum_discuss_subs} ds
                            WHERE ds.moodleforum = :dsmoodleforumid AND ds.preference <> :unsubscribed
                        ) subscriptions
                        JOIN {user} u ON u.id = subscriptions.userid
                        JOIN ($esql) je ON je.id = u.id
                        ORDER BY u.email ASC";

            } else {
                // Dont include the discussion subscriptions.

                // SQL-statement to fetch all needed fields from the database.
                $sql = "SELECT $fields
                        FROM {user} u
                        JOIN ($esql) je ON je.id = u.id
                        JOIN {moodleforum_subscriptions} s ON s.userid = u.id
                        WHERE s.moodleforum = :moodleforumid
                        ORDER BY u.email ASC";
            }

            // Fetch the data.
            $results = $DB->get_records_sql($sql, $params);
        }

        // Remove all guest users from the results. They should never be subscribed to a moodleforum.
        unset($results[$CFG->siteguest]);

        // Apply the activity module avaiability restrictions.
        $cm      = get_coursemodule_from_instance('moodleforum', $moodleforum->id, $moodleforum->course);
        $modinfo = get_fast_modinfo($moodleforum->course);
        $info    = new \core_availability\info_module($modinfo->get_cm($cm->id));
        $results = $info->filter_user_list($results);

        // Return all subscribed users.
        return $results;
    }

    /**
     * Reset the discussion cache.
     *
     * This cache is used to reduce the number of database queries
     * when checking moodleforum discussion subscriptions states.
     */
    public static function reset_discussion_cache() {

        // Reset the discussion cache.
        self::$discussioncache = array();

        // Reset the fetched discussions.
        self::$fetcheddiscussions = array();
    }

    /**
     * Reset the moodleforum cache.
     *
     * This cache is used to reduce the number of database queries
     * when checking moodleforum subscription states.
     */
    public static function reset_moodleforum_cache() {

        // Reset the cache.
        self::$moodleforumcache = array();

        // Reset the fetched moodleforums.
        self::$fetchedmoodleforums = array();
    }

    /**
     * Adds user to the subscriber list.
     *
     * @param int             $userid         The user ID
     * @param \stdClass       $moodleforum The moodleforum record
     * @param \context_module $context        The module context
     * @param bool            $userrequest    Whether the user requested this change themselves.
     *
     * @return bool|int Returns true if the user is already subscribed or the subscription id if successfully subscribed.
     */
    public static function subscribe_user($userid, $moodleforum, $context, $userrequest = false) {
        global $DB;

        // Check if the user is already subscribed.
        if (self::is_subscribed($userid, $moodleforum)) {
            return true;
        }

        // Create a new subscription object.
        $sub                 = new \stdClass();
        $sub->userid         = $userid;
        $sub->moodleforum = $moodleforum->id;

        // Insert the record into the database.
        $result = $DB->insert_record('moodleforum_subscriptions', $sub);

        // If the subscription was requested by the user, remove all records for the discussions within this moodleforum.
        if ($userrequest) {

            // Delete all those discussion subscriptions.
            $params = array(
                'userid'           => $userid,
                'moodleforumid' => $moodleforum->id,
                'preference'       => self::moodleforum_DISCUSSION_UNSUBSCRIBED);
            $where  = 'userid = :userid AND moodleforum = :moodleforumid AND preference <> :preference';
            $DB->delete_records_select('moodleforum_discuss_subs', $where, $params);

            // Reset the subscription caches for this moodleforum.
            // We know that there were previously entries and there aren't any more.
            if (isset(self::$discussioncache[$userid]) AND isset(self::$discussioncache[$userid][$moodleforum->id])) {
                foreach (self::$discussioncache[$userid][$moodleforum->id] as $discussionid => $preference) {
                    if ($preference != self::moodleforum_DISCUSSION_UNSUBSCRIBED) {
                        unset(self::$discussioncache[$userid][$moodleforum->id][$discussionid]);
                    }
                }
            }
        }

        // Reset the cache for this moodleforum.
        self::$moodleforumcache[$userid][$moodleforum->id] = true;

        // Trigger an subscription created event.
        $params = array(
            'context'       => $context,
            'objectid'      => $result,
            'relateduserid' => $userid,
            'other'         => array('moodleforumid' => $moodleforum->id),
        );
        $event  = event\subscription_created::create($params);
        $event->trigger();

        // Return the subscription ID.
        return $result;
    }

    /**
     * Removes user from the subscriber list.
     *
     * @param int             $userid         The user ID.
     * @param \stdClass       $moodleforum The moodleforum record
     * @param \context_module $context        The module context
     * @param boolean         $userrequest    Whether the user requested this change themselves.
     *
     * @return bool Always returns true
     */
    public static function unsubscribe_user($userid, $moodleforum, $context, $userrequest = null) {
        global $DB;

        // Check if there is a subscription record.
        $params = array('userid' => $userid, 'moodleforum' => $moodleforum->id);
        if ($subscription = $DB->get_record('moodleforum_subscriptions', $params)) {

            // Delete this record.
            $DB->delete_records('moodleforum_subscriptions', array('id' => $subscription->id));

            // Was the unsubscription requested by the user?
            if ($userrequest) {

                // Delete the discussion subscriptions as well.
                $params = array(
                    'userid'         => $userid,
                    'moodleforum' => $moodleforum->id,
                    'preference'     => self::moodleforum_DISCUSSION_UNSUBSCRIBED,
                );
                $DB->delete_records('moodleforum_discuss_subs', $params);

                // Update the discussion cache.
                if (isset(self::$discussioncache[$userid]) AND isset(self::$discussioncache[$userid][$moodleforum->id])) {
                    self::$discussioncache[$userid][$moodleforum->id] = array();
                }
            }

            // Reset the cache for this moodleforum.
            self::$moodleforumcache[$userid][$moodleforum->id] = false;

            // Trigger an subscription deletion event.
            $params = array(
                'context'       => $context,
                'objectid'      => $subscription->id,
                'relateduserid' => $userid,
                'other'         => array('moodleforumid' => $moodleforum->id),
            );
            $event  = event\subscription_deleted::create($params);
            $event->add_record_snapshot('moodleforum_subscriptions', $subscription);
            $event->trigger();
        }

        // The unsubscription was successful.
        return true;
    }

    /**
     * Subscribes the user to the specified discussion.
     *
     * @param int             $userid     The user ID
     * @param \stdClass       $discussion The discussion record
     * @param \context_module $context    The module context
     *
     * @return bool Whether a change was made
     */
    public static function subscribe_user_to_discussion($userid, $discussion, $context) {
        global $DB;

        // Check if the user is already subscribed to the discussion.
        $params       = array('userid' => $userid, 'discussion' => $discussion->id);
        $subscription = $DB->get_record('moodleforum_discuss_subs', $params);

        // Dont continue if the user is already subscribed.
        if ($subscription AND $subscription->preference != self::moodleforum_DISCUSSION_UNSUBSCRIBED) {
            return false;
        }

        // Check if the user is already subscribed to the moodleforum.
        $params = array('userid' => $userid, 'moodleforum' => $discussion->moodleforum);
        if ($DB->record_exists('moodleforum_subscriptions', $params)) {

            // Check if the user is unsubscribed from the discussion.
            if ($subscription AND $subscription->preference == self::moodleforum_DISCUSSION_UNSUBSCRIBED) {

                // Delete the discussion preference.
                $DB->delete_records('moodleforum_discuss_subs', array('id' => $subscription->id));
                unset(self::$discussioncache[$userid][$discussion->moodleforum][$discussion->id]);

            } else {
                // The user is already subscribed to the forum.
                return false;
            }

        } else {
            // The user is not subscribed to the moodleforum.

            // Check if there is already a subscription to the discussion.
            if ($subscription) {

                // Update the existing record.
                $subscription->preference = time();
                $DB->update_record('moodleforum_discuss_subs', $subscription);

            } else {
                // Else a new record needs to be created.
                $subscription                 = new \stdClass();
                $subscription->userid         = $userid;
                $subscription->moodleforum = $discussion->moodleforum;
                $subscription->discussion     = $discussion->id;
                $subscription->preference     = time();

                // Insert the subscription record into the database.
                $subscription->id = $DB->insert_record('moodleforum_discuss_subs', $subscription);
                self::$discussioncache[$userid][$discussion->moodleforum][$discussion->id] = $subscription->preference;
            }
        }

        // Create a discussion subscription created event.
        $params = array(
            'context'       => $context,
            'objectid'      => $subscription->id,
            'relateduserid' => $userid,
            'other'         => array('moodleforumid' => $discussion->moodleforum, 'discussion' => $discussion->id),
        );
        $event  = event\discussion_subscription_created::create($params);
        $event->trigger();

        // The subscription was successful.
        return true;
    }

    /**
     * Unsubscribes the user from the specified discussion.
     *
     * @param int             $userid     The user ID
     * @param \stdClass       $discussion The discussion record
     * @param \context_module $context    The context module
     *
     * @return bool Whether a change was made
     */
    public static function unsubscribe_user_from_discussion($userid, $discussion, $context) {
        global $DB;

        // Check the users subscription preference for this discussion.
        $params       = array('userid' => $userid, 'discussion' => $discussion->id);
        $subscription = $DB->get_record('moodleforum_discuss_subs', $params);

        // If the user not already subscribed to the discussion, do not continue.
        if ($subscription AND $subscription->preference == self::moodleforum_DISCUSSION_UNSUBSCRIBED) {
            return false;
        }

        // Check if the user is subscribed to the moodleforum.
        $params = array('userid' => $userid, 'moodleforum' => $discussion->moodleforum);
        if (!$DB->record_exists('moodleforum_subscriptions', $params)) {

            // Check if the user isn't subscribed to the moodleforum.
            if ($subscription AND $subscription->preference != self::moodleforum_DISCUSSION_UNSUBSCRIBED) {

                // Delete the discussion subscription.
                $DB->delete_records('moodleforum_discuss_subs', array('id' => $subscription->id));
                unset(self::$discussioncache[$userid][$discussion->moodleforum][$discussion->id]);

            } else {
                // Else the user is not subscribed to the moodleforum.

                // Nothing has to be done here.
                return false;
            }

        } else {
            // There is an subscription record for this moodleforum.

            // Check whether an subscription record for this discussion.
            if ($subscription) {

                // Update the existing record.
                $subscription->preference = self::moodleforum_DISCUSSION_UNSUBSCRIBED;
                $DB->update_record('moodleforum_discuss_subs', $subscription);

            } else {
                // There is no record.

                // Create a new discussion subscription record.
                $subscription                 = new \stdClass();
                $subscription->userid         = $userid;
                $subscription->moodleforum = $discussion->moodleforum;
                $subscription->discussion     = $discussion->id;
                $subscription->preference     = self::moodleforum_DISCUSSION_UNSUBSCRIBED;

                // Insert the discussion subscription record into the database.
                $subscription->id = $DB->insert_record('moodleforum_discuss_subs', $subscription);
            }

            // Update the cache.
            self::$discussioncache[$userid][$discussion->moodleforum][$discussion->id] = $subscription->preference;
        }

        // Trigger an discussion subscription deletetion event.
        $params = array(
            'context'       => $context,
            'objectid'      => $subscription->id,
            'relateduserid' => $userid,
            'other'         => array('moodleforumid' => $discussion->moodleforum, 'discussion' => $discussion->id),
        );
        $event  = event\discussion_subscription_deleted::create($params);
        $event->trigger();

        // The user was successfully unsubscribed from the discussion.
        return true;
    }

    /**
     * Generate and return the subscribe or unsubscribe link for a moodleforum.
     *
     * @param object $moodleforum the moodleforum. Fields used are $moodleforum->id and $moodleforum->forcesubscribe.
     * @param object $context        the context object for this moodleforum.
     * @param array  $messages       text used for the link in its various states
     *                               (subscribed, unsubscribed, forcesubscribed or cantsubscribe).
     *                               Any strings not passed in are taken from the $defaultmessages array
     *                               at the top of the function.
     *
     * @return string
     */
    public static function moodleforum_get_subscribe_link($moodleforum, $context, $messages = array()) {
        global $USER, $OUTPUT;

        // Define strings.
        $defaultmessages = array(
            'subscribed'      => get_string('unsubscribe', 'moodleforum'),
            'unsubscribed'    => get_string('subscribe', 'moodleforum'),
            'forcesubscribed' => get_string('everyoneissubscribed', 'moodleforum'),
            'cantsubscribe'   => get_string('disallowsubscribe', 'moodleforum'),
        );

        // Combine strings the submitted messages.
        $messages = $messages + $defaultmessages;

        // Check whether the user is forced to be subscribed to the moodleforum.
        $isforced   = self::is_forcesubscribed($moodleforum);
        $isdisabled = self::subscription_disabled($moodleforum);

        // Return messages depending on the subscription state.
        if ($isforced) {
            return $messages['forcesubscribed'];
        } else if ($isdisabled AND !has_capability('mod/moodleforum:managesubscriptions', $context)) {
            return $messages['cantsubscribe'];
        } else {

            // The user needs to be enrolled.
            if (!is_enrolled($context, $USER, '', true)) {
                return '';
            }

            // Check whether the user is subscribed.
            $issubscribed = self::is_subscribed($USER->id, $moodleforum);

            // Define the text of the link depending on the subscription state.
            if ($issubscribed) {
                $linktext  = $messages['subscribed'];
                $linktitle = get_string('subscribestop', 'moodleforum');
            } else {
                $linktext  = $messages['unsubscribed'];
                $linktitle = get_string('subscribestart', 'moodleforum');
            }

            // Create an options array.
            $options                = array();
            $options['id']          = $moodleforum->id;
            $options['sesskey']     = sesskey();
            $options['returnurl']   = 0;
            $options['backtoindex'] = 1;

            // Return the link to subscribe the user.
            $url = new \moodle_url('/mod/moodleforum/subscribe.php', $options);

            return $OUTPUT->single_button($url, $linktext, 'get', array('title' => $linktitle));
        }
    }

    /**
     * Given a new post, subscribes the user to the thread the post was posted in.
     *
     * @param object $fromform       The submitted form
     * @param \stdClass       $moodleforum The moodleforum record
     * @param \stdClass       $discussion     The discussion record
     * @param \context_course $modulecontext  The context of the module
     *
     * @return bool
     */
    public static function moodleforum_post_subscription($fromform, $moodleforum, $discussion, $modulecontext) {
        global $USER;

        // Check for some basic information.
        $force    = self::is_forcesubscribed($moodleforum);
        $disabled = self::subscription_disabled($moodleforum);

        // Do not continue if the user is already forced to be subscribed.
        if ($force) {
            return false;
        }

        // Do not continue if subscriptions are disabled.
        if ($disabled) {

            // If the user is subscribed, unsubscribe him.
            $subscribed    = self::is_subscribed($USER->id, $moodleforum);
            $coursecontext = \context_course::instance($moodleforum->course);
            $canmanage     = has_capability('moodle/course:manageactivities', $coursecontext, $USER->id);
            if ($subscribed AND !$canmanage) {
                self::unsubscribe_user($USER->id, $moodleforum, $modulecontext);
            }

            // Do not continue.
            return false;
        }

        // Subscribe the user to the discussion.
        self::subscribe_user_to_discussion($USER->id, $discussion, $modulecontext);

        return true;
    }

    /**
     * Return the markup for the discussion subscription toggling icon.
     *
     * @param object $moodleforum The forum moodleforum.
     * @param int    $discussionid   The discussion to create an icon for.
     *
     * @return string The generated markup.
     */
    public static function get_discussion_subscription_icon($moodleforum, $discussionid) {
        global $OUTPUT, $PAGE, $USER;

        // Set the url to return to.
        $returnurl = $PAGE->url->out();

        // Check if the discussion is subscrived.
        $status = self::is_subscribed($USER->id, $moodleforum, $discussionid);

        // Create a link to subscribe or unsubscribe to the discussion.
        $array            = array(
            'sesskey'   => sesskey(),
            'id'        => $moodleforum->id,
            'd'         => $discussionid,
            'returnurl' => $returnurl,
        );
        $subscriptionlink = new \moodle_url('/mod/moodleforum/subscribe.php', $array);

        // Create an icon to unsubscribe.
        if ($status) {

            // Create the icon.
            $string = get_string('clicktounsubscribe', 'moodleforum');
            $output = $OUTPUT->pix_icon('subscribed', $string, 'mod_moodleforum');

            // Return the link.
            $array = array(
                'title'                 => get_string('clicktounsubscribe', 'moodleforum'),
                'class'                 => 'discussiontoggle iconsmall',
                'data-moodleforumid' => $moodleforum->id,
                'data-discussionid'     => $discussionid,
                'data-includetext'      => false,
            );

            return \html_writer::link($subscriptionlink, $output, $array);
        }

        // Create an icon to subscribe.
        $string = get_string('clicktosubscribe', 'moodleforum');
        $output = $OUTPUT->pix_icon('unsubscribed', $string, 'mod_moodleforum');

        // Return the link.
        $array = array(
            'title'                 => get_string('clicktosubscribe', 'moodleforum'),
            'class'                 => 'discussiontoggle iconsmall',
            'data-moodleforumid' => $moodleforum->id,
            'data-discussionid'     => $discussionid,
            'data-includetext'      => false,
        );

        return \html_writer::link($subscriptionlink, $output, $array);
    }
}