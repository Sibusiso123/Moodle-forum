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
 * Event observers used in moodleforum.
 *
 * @package   mod_moodleforum
 * @copyright 2017 Kennet Winter <k_wint10@uni-muenster.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Event observer for mod_moodleforum.
 * @copyright 2017 Kennet Winter <k_wint10@uni-muenster.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_moodleforum_observer {

    /**
     * Triggered via user_enrolment_deleted event.
     *
     * @param \core\event\user_enrolment_deleted $event
     */
    public static function user_enrolment_deleted(\core\event\user_enrolment_deleted $event) {
        global $DB;

        // Get user enrolment info from event.
        $cp = (object) $event->other['userenrolment'];

        // Check if the user was enrolled.
        if ($cp->lastenrol) {

            // Get the moodleforum instances from which the user was unenrolled from.
            $moodleforums = $DB->get_records('moodleforum', array('course' => $cp->courseid), '', 'id');

            // Do not continue if there are no connected moodleforum instances.
            if (!$moodleforums) {
                return;
            }

            // Get the sql parameters for the moodleforum instances and add the user ID.
            list($select, $params) = $DB->get_in_or_equal(array_keys($moodleforums), SQL_PARAMS_NAMED);
            $params['userid'] = $cp->userid;

            // Delete all records that are connected to those moodleforum instances.
            $DB->delete_records_select('moodleforum_subscriptions', 'userid = :userid AND moodleforum ' . $select, $params);
            $DB->delete_records_select('moodleforum_read', 'userid = :userid AND moodleforumid ' . $select, $params);
        }
    }

    /**
     * Observer for role_assigned event.
     *
     * @param \core\event\role_assigned $event
     *
     * @return void
     */
    public static function role_assigned(\core\event\role_assigned $event) {
        global $CFG, $DB;

        // Get the context level.
        $context = context::instance_by_id($event->contextid, MUST_EXIST);

        // Check whether the context level is at course level.
        // Only at this level the user is enrolled in the course and can subscribe.
        if ($context->contextlevel != CONTEXT_COURSE) {
            return;
        }

        // Require the libriary of the plugin. It is needed for the variable.
        require_once($CFG->dirroot . '/mod/moodleforum/locallib.php');

        // Get the related user.
        $userid = $event->relateduserid;

        // Retrieve all moodleforums in this course.
        $sql             = "SELECT m.id, m.course as course, cm.id AS cmid, m.forcesubscribe
                  FROM {moodleforum} m
                  JOIN {course_modules} cm ON (cm.instance = m.id)
                  JOIN {modules} mo ON (mo.id = cm.module)
             LEFT JOIN {moodleforum_subscriptions} ms ON (ms.moodleforum = m.id AND ms.userid = :userid)
                 WHERE m.course = :courseid AND m.forcesubscribe = :initial AND mo.name = 'moodleforum' AND ms.id IS NULL";
        $params          = array('courseid' => $context->instanceid,
                                 'userid' => $userid,
                                 'initial' => moodleforum_INITIALSUBSCRIBE);
        $moodleforums = $DB->get_records_sql($sql, $params);

        // Loop through all moodleforums.
        foreach ($moodleforums as $moodleforum) {

            // If user doesn't have allowforcesubscribe capability then don't subscribe.

            // Retrieve the context of the module.
            $modulecontext = context_module::instance($moodleforum->cmid);

            // Check if the user is allowed to be forced to be subscribed.
            $allowforce = has_capability('mod/moodleforum:allowforcesubscribe', $modulecontext, $userid);

            // If the user has the right to be forced to be subscribed, subscribe the user.
            if ($allowforce) {
                \mod_moodleforum\subscriptions::subscribe_user($userid, $moodleforum, $modulecontext);
            }
        }
    }

    /**
     * Observer for \core\event\course_module_created event.
     *
     * @param \core\event\course_module_created $event
     *
     * @return void
     */
    public static function course_module_created(\core\event\course_module_created $event) {
        global $DB, $CFG;

        // Check if a moodleforum instance was created.
        if ($event->other['modulename'] === 'moodleforum') {

            // Require the library.
            require_once($CFG->dirroot . '/mod/moodleforum/lib.php');

            // Create a snapshot of the created moodleforum record.
            $moodleforum = $DB->get_record('moodleforum', array('id' => $event->other['instanceid']));

            // Trigger the function for a created moodleforum instance.
            moodleforum_instance_created($event->get_context(), $moodleforum);
        }
    }
}