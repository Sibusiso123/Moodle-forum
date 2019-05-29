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
 * The module moodleforum tests.
 *
 * @package   mod_moodleforum
 * @copyright 2017 Kennet Winter <k_wint10@uni-muenster.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/moodleforum/lib.php');

/**
 * Class mod_moodleforum_subscriptions_testcase.
 *
 * @package   mod_moodleforum
 * @copyright 2017 Kennet Winter <k_wint10@uni-muenster.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_moodleforum_subscriptions_testcase extends advanced_testcase {

    /**
     * Test setUp.
     */
    public function setUp() {
        // Clear all caches.
        \mod_moodleforum\subscriptions::reset_moodleforum_cache();
        \mod_moodleforum\subscriptions::reset_discussion_cache();
    }

    /**
     * Test tearDown.
     */
    public function tearDown() {
        // Clear all caches.
        \mod_moodleforum\subscriptions::reset_moodleforum_cache();
        \mod_moodleforum\subscriptions::reset_discussion_cache();
    }

    /**
     * Helper to create the required number of users in the specified course.
     * Users are enrolled as students.
     *
     * @param stdClass $course The course object
     * @param int      $count  The number of users to create
     *
     * @return array The users created
     */
    protected function helper_create_users($course, $count) {
        $users = array();

        for ($i = 0; $i < $count; $i++) {
            $user = $this->getDataGenerator()->create_user();
            $this->getDataGenerator()->enrol_user($user->id, $course->id);
            $users[] = $user;
        }

        return $users;
    }

    /**
     * Crate a new discussion and post within the moodleforum.
     *
     * @param stdClass $moodleforum The moodleforum to post in
     * @param stdClass $author         The author to post as
     *
     * @return array Array containing the discussion object and the post object.
     */
    protected function helper_post_to_moodleforum($moodleforum, $author) {
        global $DB;

        // Retrieve the generator.
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_moodleforum');

        // Create a discussion in the moodleforum, add a post to that discussion.
        $record = new stdClass();
        $record->course = $moodleforum->course;
        $record->userid = $author->id;
        $record->moodleforum = $moodleforum->id;
        $discussion = $generator->create_discussion($record, $moodleforum);

        // Retrieve the post which was created.
        $post = $DB->get_record('moodleforum_posts', array('discussion' => $discussion->id));

        // Return the discussion and the post.
        return array($discussion->id, $post);
    }

    /**
     * Test to set subscription modes.
     */
    public function test_subscription_modes() {
        global $DB;

        // Reset the database after testing.
        $this->resetAfterTest(true);

        // Create a course with a moodleforum.
        $course = $this->getDataGenerator()->create_course();
        $options = array('course' => $course->id);
        $moodleforum = $this->getDataGenerator()->create_module('moodleforum', $options);

        // Create a user enrolled in the course as a student.
        list ($user) = $this->helper_create_users($course, 1);

        // Must be logged in as the current user.
        $this->setUser($user);

        // Test the forced subscription.
        \mod_moodleforum\subscriptions::set_subscription_mode($moodleforum->id, moodleforum_FORCESUBSCRIBE);
        $moodleforum = $DB->get_record('moodleforum', array('id' => $moodleforum->id));
        $this->assertEquals(moodleforum_FORCESUBSCRIBE,
            \mod_moodleforum\subscriptions::get_subscription_mode($moodleforum));
        $this->assertTrue(\mod_moodleforum\subscriptions::is_forcesubscribed($moodleforum));
        $this->assertFalse(\mod_moodleforum\subscriptions::is_subscribable($moodleforum));
        $this->assertFalse(\mod_moodleforum\subscriptions::subscription_disabled($moodleforum));

        // Test the disallowed subscription.
        \mod_moodleforum\subscriptions::set_subscription_mode($moodleforum->id, moodleforum_DISALLOWSUBSCRIBE);
        $moodleforum = $DB->get_record('moodleforum', array('id' => $moodleforum->id));
        $this->assertTrue(\mod_moodleforum\subscriptions::subscription_disabled($moodleforum));
        $this->assertFalse(\mod_moodleforum\subscriptions::is_subscribable($moodleforum));
        $this->assertFalse(\mod_moodleforum\subscriptions::is_forcesubscribed($moodleforum));

        // Test the initial subscription.
        \mod_moodleforum\subscriptions::set_subscription_mode($moodleforum->id, moodleforum_INITIALSUBSCRIBE);
        $moodleforum = $DB->get_record('moodleforum', array('id' => $moodleforum->id));
        $this->assertTrue(\mod_moodleforum\subscriptions::is_subscribable($moodleforum));
        $this->assertFalse(\mod_moodleforum\subscriptions::subscription_disabled($moodleforum));
        $this->assertFalse(\mod_moodleforum\subscriptions::is_forcesubscribed($moodleforum));

        // Test the choose subscription.
        \mod_moodleforum\subscriptions::set_subscription_mode($moodleforum->id, moodleforum_CHOOSESUBSCRIBE);
        $moodleforum = $DB->get_record('moodleforum', array('id' => $moodleforum->id));
        $this->assertTrue(\mod_moodleforum\subscriptions::is_subscribable($moodleforum));
        $this->assertFalse(\mod_moodleforum\subscriptions::subscription_disabled($moodleforum));
        $this->assertFalse(\mod_moodleforum\subscriptions::is_forcesubscribed($moodleforum));
    }

    /**
     * Test fetching unsubscribable moodleforums.
     */
    public function test_unsubscribable_moodleforums() {
        // Reset the database after testing.
        $this->resetAfterTest(true);

        // Create a course with a moodleforum.
        $course = $this->getDataGenerator()->create_course();
        $options = array('course' => $course->id);
        $mof = $this->getDataGenerator()->create_module('moodleforum', $options);

        // Get the module context.
        $cm = get_coursemodule_from_instance('moodleforum', $mof->id);
        $modulecontext = \context_module::instance($cm->id);

        // Create a user enrolled in the course as a student.
        list ($user) = $this->helper_create_users($course, 1);

        // Must be logged in as the current user.
        $this->setUser($user);

        // Without any subscriptions, there should be nothing returned.
        $result = \mod_moodleforum\subscriptions::get_unsubscribable_moodleforums();
        $this->assertEquals(0, count($result));

        // Create the moodleforums.
        $options = array('course' => $course->id, 'forcesubscribe' => moodleforum_FORCESUBSCRIBE);
        $this->getDataGenerator()->create_module('moodleforum', $options);
        $options = array('course' => $course->id, 'forcesubscribe' => moodleforum_DISALLOWSUBSCRIBE);
        $disallow = $this->getDataGenerator()->create_module('moodleforum', $options);
        $options = array('course' => $course->id, 'forcesubscribe' => moodleforum_CHOOSESUBSCRIBE);
        $choose = $this->getDataGenerator()->create_module('moodleforum', $options);
        $options = array('course' => $course->id, 'forcesubscribe' => moodleforum_INITIALSUBSCRIBE);
        $this->getDataGenerator()->create_module('moodleforum', $options);

        // At present the user is only subscribed to the initial moodleforum.
        $result = \mod_moodleforum\subscriptions::get_unsubscribable_moodleforums();
        $this->assertEquals(1, count($result));

        // Ensure that the user is enrolled in all of the moodleforums execpt force subscribe.
        \mod_moodleforum\subscriptions::subscribe_user($user->id, $disallow, $modulecontext);
        \mod_moodleforum\subscriptions::subscribe_user($user->id, $choose, $modulecontext);

        // At present the user  is subscribed to all three moodleforums.
        $result = \mod_moodleforum\subscriptions::get_unsubscribable_moodleforums();
        $this->assertEquals(3, count($result));
    }

    /**
     * Test that toggeling the moodleforum-level subscription for a different user does not affect their discussion-level.
     */
    public function test_moodleforum_toggle_as_other() {
        global $DB;

        // Reset the database after testing.
        $this->resetAfterTest(true);

        // Create a course with a moodleforum.
        $course = $this->getDataGenerator()->create_course();
        $options = array('course' => $course->id);
        $moodleforum = $this->getDataGenerator()->create_module('moodleforum', $options);

        // Get the module context.
        $cm = get_coursemodule_from_instance('moodleforum', $moodleforum->id);
        $modulecontext = \context_module::instance($cm->id);

        // Create a user enrolled in the course as a student.
        list ($author) = $this->helper_create_users($course, 1);

        // Post a discussion to the moodleforum.
        $discussion = new \stdClass();
        list($discussion->id, $post) = $this->helper_post_to_moodleforum($moodleforum, $author);
        unset($post);
        $discussion->moodleforum = $moodleforum->id;

        // Check that the user is currently not subscribed to the moodleforum.
        $this->assertFalse(\mod_moodleforum\subscriptions::is_subscribed($author->id, $moodleforum));

        // Check that the user is unsubscribed from the discussion too.
        $this->assertFalse(\mod_moodleforum\subscriptions::is_subscribed($author->id, $moodleforum, $discussion->id));

        // Check thast we have no records in either on the subscription tables.
        $options = array('userid' => $author->id, 'moodleforum' => $moodleforum->id);
        $count = $DB->count_records('moodleforum_subscriptions', $options);
        $this->assertEquals(0, $count);
        $options = array('userid' => $author->id, 'discussion' => $discussion->id);
        $count = $DB->count_records('moodleforum_discuss_subs', $options);
        $this->assertEquals(0, $count);

        // Subscribing to the moodleforum should create a record in the subscription table,
        // but the moodleforum discussion subscriptions table.
        \mod_moodleforum\subscriptions::subscribe_user($author->id, $moodleforum, $modulecontext);
        $options = array('userid' => $author->id, 'moodleforum' => $moodleforum->id);
        $count = $DB->count_records('moodleforum_subscriptions', $options);
        $this->assertEquals(1, $count);
        $options = array('userid' => $author->id, 'discussion' => $discussion->id);
        $count = $DB->count_records('moodleforum_discuss_subs', $options);
        $this->assertEquals(0, $count);

        // Unsubscribing should remove the record from the moodleforum subscription table.
        // Do not modify the moodleforum discussion subscriptions table.
        \mod_moodleforum\subscriptions::unsubscribe_user($author->id, $moodleforum, $modulecontext);
        $options = array('userid' => $author->id, 'moodleforum' => $moodleforum->id);
        $count = $DB->count_records('moodleforum_subscriptions', $options);
        $this->assertEquals(0, $count);
        $options = array('userid' => $author->id, 'discussion' => $discussion->id);
        $count = $DB->count_records('moodleforum_discuss_subs', $options);
        $this->assertEquals(0, $count);

        // Enroling the user in the discussion should add one record to the
        // moodleforum discussion table without modifying the form subscription.
        \mod_moodleforum\subscriptions::subscribe_user_to_discussion($author->id, $discussion, $modulecontext);
        $options = array('userid' => $author->id, 'moodleforum' => $moodleforum->id);
        $count = $DB->count_records('moodleforum_subscriptions', $options);
        $this->assertEquals(0, $count);
        $options = array('userid' => $author->id, 'discussion' => $discussion->id);
        $count = $DB->count_records('moodleforum_discuss_subs', $options);
        $this->assertEquals(1, $count);

        // Unsubscribing should remove the record from the moodleforum subscriptions
        // table and not modify the moodleforum discussion subscription table.
        \mod_moodleforum\subscriptions::unsubscribe_user_from_discussion($author->id, $discussion, $modulecontext);
        $options = array('userid' => $author->id, 'discussion' => $discussion->id);
        $count = $DB->count_records('moodleforum_discuss_subs', $options);
        $this->assertEquals(0, $count);
        $options = array('userid' => $author->id, 'moodleforum' => $moodleforum->id);
        $count = $DB->count_records('moodleforum_subscriptions', $options);
        $this->assertEquals(0, $count);

        // Resubscribe to the discussion so that we can check the effect of moodleforum-level subscriptions.
        \mod_moodleforum\subscriptions::subscribe_user_to_discussion($author->id, $discussion, $modulecontext);
        $options = array('userid' => $author->id, 'discussion' => $discussion->id);
        $count = $DB->count_records('moodleforum_discuss_subs', $options);
        $this->assertEquals(1, $count);
        $options = array('userid' => $author->id, 'moodleforum' => $moodleforum->id);
        $count = $DB->count_records('moodleforum_subscriptions', $options);
        $this->assertEquals(0, $count);

        // Subscribing to the moodleforum should have no effect on the moodleforum discussion
        // subscription table if the user did not request the change himself.
        \mod_moodleforum\subscriptions::subscribe_user($author->id, $moodleforum, $modulecontext);
        $options = array('userid' => $author->id, 'moodleforum' => $moodleforum->id);
        $count = $DB->count_records('moodleforum_subscriptions', $options);
        $this->assertEquals(1, $count);
        $options = array('userid' => $author->id, 'discussion' => $discussion->id);
        $count = $DB->count_records('moodleforum_discuss_subs', $options);
        $this->assertEquals(1, $count);

        // Unsubbing from the moodleforum should have no effect on the moodleforum
        // discussion subscription table if the user did not request the change themself.
        \mod_moodleforum\subscriptions::unsubscribe_user($author->id, $moodleforum, $modulecontext);
        $options = array('userid' => $author->id, 'moodleforum' => $moodleforum->id);
        $count = $DB->count_records('moodleforum_subscriptions', $options);
        $this->assertEquals(0, $count);
        $options = array('userid' => $author->id, 'discussion' => $discussion->id);
        $count = $DB->count_records('moodleforum_discuss_subs', $options);
        $this->assertEquals(1, $count);

        // Subscribing to the moodleforum should remove the per-discussion
        // subscription preference if the user requested the change themself.
        \mod_moodleforum\subscriptions::subscribe_user($author->id, $moodleforum, $modulecontext, true);
        $options = array('userid' => $author->id, 'moodleforum' => $moodleforum->id);
        $count = $DB->count_records('moodleforum_subscriptions', $options);
        $this->assertEquals(1, $count);
        $options = array('userid' => $author->id, 'discussion' => $discussion->id);
        $count = $DB->count_records('moodleforum_discuss_subs', $options);
        $this->assertEquals(0, $count);

        // Now unsubscribe from the current discussion whilst being subscribed to the moodleforum as a whole.
        \mod_moodleforum\subscriptions::unsubscribe_user_from_discussion($author->id, $discussion, $modulecontext);
        $options = array('userid' => $author->id, 'moodleforum' => $moodleforum->id);
        $count = $DB->count_records('moodleforum_subscriptions', $options);
        $this->assertEquals(1, $count);
        $options = array('userid' => $author->id, 'discussion' => $discussion->id);
        $count = $DB->count_records('moodleforum_discuss_subs', $options);
        $this->assertEquals(1, $count);

        // Unsubscribing from the moodleforum should remove the per-discussion
        // subscription preference if the user requested the change himself.
        \mod_moodleforum\subscriptions::unsubscribe_user($author->id, $moodleforum, $modulecontext, true);
        $options = array('userid' => $author->id, 'moodleforum' => $moodleforum->id);
        $count = $DB->count_records('moodleforum_subscriptions', $options);
        $this->assertEquals(0, $count);
        $count = $DB->count_records('moodleforum_discuss_subs', array(
            'userid'     => $author->id,
            'discussion' => $discussion->id,
        ));
        $this->assertEquals(0, $count);

        // Subscribe to the discussion.
        \mod_moodleforum\subscriptions::subscribe_user_to_discussion($author->id, $discussion, $modulecontext);
        $options = array('userid' => $author->id, 'moodleforum' => $moodleforum->id);
        $count = $DB->count_records('moodleforum_subscriptions', $options);
        $this->assertEquals(0, $count);
        $count = $DB->count_records('moodleforum_discuss_subs', array(
            'userid'     => $author->id,
            'discussion' => $discussion->id,
        ));
        $this->assertEquals(1, $count);

        // Subscribe to the moodleforum without removing the discussion preferences.
        \mod_moodleforum\subscriptions::subscribe_user($author->id, $moodleforum, $modulecontext);
        $options = array('userid' => $author->id, 'moodleforum' => $moodleforum->id);
        $count = $DB->count_records('moodleforum_subscriptions', $options);
        $this->assertEquals(1, $count);
        $options = array('userid' => $author->id, 'discussion' => $discussion->id);
        $count = $DB->count_records('moodleforum_discuss_subs', $options);
        $this->assertEquals(1, $count);

        // Unsubscribe from the discussion should result in a change.
        \mod_moodleforum\subscriptions::unsubscribe_user_from_discussion($author->id, $discussion, $modulecontext);
        $options = array('userid' => $author->id, 'moodleforum' => $moodleforum->id);
        $count = $DB->count_records('moodleforum_subscriptions', $options);
        $this->assertEquals(1, $count);
        $options = array('userid' => $author->id, 'discussion' => $discussion->id);
        $count = $DB->count_records('moodleforum_discuss_subs', $options);
        $this->assertEquals(1, $count);
    }

    /**
     * Test that a user unsubscribed from a moodleforum is not subscribed to it's discussions by default.
     */
    public function test_moodleforum_discussion_subscription_moodleforum_unsubscribed() {
        // Reset the database after the test.
        $this->resetAfterTest(true);

        // Create a course with a moodleforum.
        $course = $this->getDataGenerator()->create_course();
        $options = array('course' => $course->id, 'forcesubscribe' => moodleforum_CHOOSESUBSCRIBE);
        $moodleforum = $this->getDataGenerator()->create_module('moodleforum', $options);

        // Create users enrolled in the course as students.
        list($author) = $this->helper_create_users($course, 1);

        // Check that the user is currently not subscribed to the moodleforum.
        $this->assertFalse(\mod_moodleforum\subscriptions::is_subscribed($author->id, $moodleforum));

        // Post a discussion to the moodleforum.
        list($discussion, $post) = $this->helper_post_to_moodleforum($moodleforum, $author);
        unset($post);

        // Check that the user is unsubscribed from the discussion too.
        $this->assertFalse(\mod_moodleforum\subscriptions::is_subscribed($author->id, $moodleforum, $discussion));
    }

    /**
     * Test that the act of subscribing to a moodleforum subscribes the user to it's discussions by default.
     */
    public function test_moodleforum_discussion_subscription_moodleforum_subscribed() {
        // Reset the database after testing.
        $this->resetAfterTest(true);

        // Create a course with a moodleforum.
        $course = $this->getDataGenerator()->create_course();
        $options = array('course' => $course->id, 'forcesubscribe' => moodleforum_CHOOSESUBSCRIBE);
        $moodleforum = $this->getDataGenerator()->create_module('moodleforum', $options);

        // Get the module context.
        $cm = get_coursemodule_from_instance('moodleforum', $moodleforum->id);
        $modulecontext = \context_module::instance($cm->id);

        // Create users enrolled in the course as students.
        list($author) = $this->helper_create_users($course, 1);

        // Enrol the user in the moodleforum.
        // If a subscription was added, we get the record ID.
        $this->assertInternalType('int', \mod_moodleforum\subscriptions::subscribe_user($author->id,
            $moodleforum, $modulecontext));

        // If we already have a subscription when subscribing the user, we get a boolean (true).
        $this->assertTrue(\mod_moodleforum\subscriptions::subscribe_user($author->id, $moodleforum, $modulecontext));

        // Check that the user is currently subscribed to the moodleforum.
        $this->assertTrue(\mod_moodleforum\subscriptions::is_subscribed($author->id, $moodleforum), $modulecontext);

        // Post a discussion to the moodleforum.
        list($discussion, $post) = $this->helper_post_to_moodleforum($moodleforum, $author);
        unset($post);

        // Check that the user is subscribed to the discussion too.
        $this->assertTrue(\mod_moodleforum\subscriptions::is_subscribed($author->id, $moodleforum, $discussion));
    }

    /**
     * Test that a user unsubscribed from a moodleforum can be subscribed to a discussion.
     */
    public function test_moodleforum_discussion_subscription_moodleforum_unsubscribed_discussion_subscribed() {
        // Reset the database after testing.
        $this->resetAfterTest(true);

        // Create a course and a new moodleforum.
        $course = $this->getDataGenerator()->create_course();
        $options = array('course' => $course->id, 'forcesubscribe' => moodleforum_CHOOSESUBSCRIBE);
        $moodleforum = $this->getDataGenerator()->create_module('moodleforum', $options);

        // Get the module context.
        $cm = get_coursemodule_from_instance('moodleforum', $moodleforum->id);
        $modulecontext = \context_module::instance($cm->id);

        // Create a user enrolled in the course as a student.
        list($author) = $this->helper_create_users($course, 1);

        // Check that the user is currently not subscribed to the moodleforum.
        $this->assertFalse(\mod_moodleforum\subscriptions::is_subscribed($author->id, $moodleforum));

        // Post a discussion to the moodleforum.
        $discussion = new \stdClass();
        list($discussion->id, $post) = $this->helper_post_to_moodleforum($moodleforum, $author);
        unset($post);
        $discussion->moodleforum = $moodleforum->id;

        // Attempting to unsubscribe from the discussion should not make a change.
        $this->assertFalse(\mod_moodleforum\subscriptions::unsubscribe_user_from_discussion($author->id,
            $discussion, $modulecontext));

        // Then subscribe them to the discussion.
        $this->assertTrue(\mod_moodleforum\subscriptions::subscribe_user_to_discussion($author->id,
            $discussion, $modulecontext));

        // Check that the user is still unsubscribed from the moodleforum.
        $this->assertFalse(\mod_moodleforum\subscriptions::is_subscribed($author->id, $moodleforum));

        // But subscribed to the discussion.
        $this->assertTrue(\mod_moodleforum\subscriptions::is_subscribed($author->id, $moodleforum, $discussion->id));
    }

    /**
     * Test that a user subscribed to a moodleforum can be unsubscribed from a discussion.
     */
    public function test_moodleforum_discussion_subscription_moodleforum_subscribed_discussion_unsubscribed() {
        // Reset the database after testing.
        $this->resetAfterTest(true);

        // Create a course, with a moodleforum.
        $course = $this->getDataGenerator()->create_course();
        $options = array('course' => $course->id, 'forcesubscribe' => moodleforum_CHOOSESUBSCRIBE);
        $moodleforum = $this->getDataGenerator()->create_module('moodleforum', $options);

        // Get the module context.
        $cm = get_coursemodule_from_instance('moodleforum', $moodleforum->id);
        $modulecontext = \context_module::instance($cm->id);

        // Create two users enrolled in the course as students.
        list($author) = $this->helper_create_users($course, 2);

        // Enrol the student in the moodleforum.
        \mod_moodleforum\subscriptions::subscribe_user($author->id, $moodleforum, $modulecontext);

        // Check that the user is currently subscribed to the moodleforum.
        $this->assertTrue(\mod_moodleforum\subscriptions::is_subscribed($author->id, $moodleforum));

        // Post a discussion to the moodleforum.
        $discussion = new \stdClass();
        list($discussion->id, $post) = $this->helper_post_to_moodleforum($moodleforum, $author);
        unset($post);
        $discussion->moodleforum = $moodleforum->id;

        // Then unsubscribe them from the discussion.
        \mod_moodleforum\subscriptions::unsubscribe_user_from_discussion($author->id, $discussion, $modulecontext);

        // Check that the user is still subscribed to the moodleforum.
        $this->assertTrue(\mod_moodleforum\subscriptions::is_subscribed($author->id, $moodleforum));

        // But unsubscribed from the discussion.
        $this->assertFalse(\mod_moodleforum\subscriptions::is_subscribed($author->id, $moodleforum, $discussion->id));
    }

    /**
     * Test the effect of toggling the discussion subscription status when subscribed to the moodleforum.
     */
    public function test_moodleforum_discussion_toggle_moodleforum_subscribed() {
        global $DB;

        // Reset the database after testing.
        $this->resetAfterTest(true);

        // Create a course with a moodleforum.
        $course = $this->getDataGenerator()->create_course();
        $options = array('course' => $course->id, 'forcesubscribe' => moodleforum_CHOOSESUBSCRIBE);
        $moodleforum = $this->getDataGenerator()->create_module('moodleforum', $options);

        $cm = get_coursemodule_from_instance('moodleforum', $moodleforum->id);
        $modulecontext = \context_module::instance($cm->id);

        // Create two users enrolled in the course as students.
        list($author) = $this->helper_create_users($course, 2);

        // Enrol the student in the moodleforum.
        \mod_moodleforum\subscriptions::subscribe_user($author->id, $moodleforum, $modulecontext);

        // Post a discussion to the moodleforum.
        $discussion = new \stdClass();
        list($discussion->id, $post) = $this->helper_post_to_moodleforum($moodleforum, $author);
        unset($post);
        $discussion->moodleforum = $moodleforum->id;

        // Check that the user is currently subscribed to the moodleforum.
        $this->assertTrue(\mod_moodleforum\subscriptions::is_subscribed($author->id, $moodleforum));

        // Check that the user is initially subscribed to that discussion.
        $this->assertTrue(\mod_moodleforum\subscriptions::is_subscribed($author->id, $moodleforum, $discussion->id));

        // An attempt to subscribe again should result in a falsey return to indicate that no change was made.
        $this->assertFalse(\mod_moodleforum\subscriptions::subscribe_user_to_discussion($author->id,
            $discussion, $modulecontext));

        // And there should be no discussion subscriptions (and one moodleforum subscription).
        $options = array('userid' => $author->id, 'discussion' => $discussion->id);
        $count = $DB->count_records('moodleforum_discuss_subs', $options);
        $this->assertEquals(0, $count);
        $options = array('userid' => $author->id, 'moodleforum' => $moodleforum->id);
        $count = $DB->count_records('moodleforum_subscriptions', $options);
        $this->assertEquals(1, $count);

        // Then unsubscribe them from the discussion.
        \mod_moodleforum\subscriptions::unsubscribe_user_from_discussion($author->id, $discussion, $modulecontext);

        // Check that the user is still subscribed to the moodleforum.
        $this->assertTrue(\mod_moodleforum\subscriptions::is_subscribed($author->id, $moodleforum));

        // An attempt to unsubscribe again should result in a falsey return to indicate that no change was made.
        $this->assertFalse(\mod_moodleforum\subscriptions::unsubscribe_user_from_discussion($author->id,
            $discussion, $modulecontext));

        // And there should be a discussion subscriptions (and one moodleforum subscription).
        $options = array('userid' => $author->id, 'discussion' => $discussion->id);
        $count = $DB->count_records('moodleforum_discuss_subs', $options);
        $this->assertEquals(1, $count);
        $options = array('userid' => $author->id, 'moodleforum' => $moodleforum->id);
        $count = $DB->count_records('moodleforum_subscriptions', $options);
        $this->assertEquals(1, $count);

        // But unsubscribed from the discussion.
        $this->assertFalse(\mod_moodleforum\subscriptions::is_subscribed($author->id, $moodleforum, $discussion->id));

        // There should be a record in the discussion subscription tracking table.
        $options = array('userid' => $author->id, 'discussion' => $discussion->id);
        $count = $DB->count_records('moodleforum_discuss_subs', $options);
        $this->assertEquals(1, $count);

        // And one in the moodleforum subscription tracking table.
        $options = array('userid' => $author->id, 'moodleforum' => $moodleforum->id);
        $count = $DB->count_records('moodleforum_subscriptions', $options);
        $this->assertEquals(1, $count);

        // Now subscribe the user again to the discussion.
        \mod_moodleforum\subscriptions::subscribe_user_to_discussion($author->id, $discussion, $modulecontext);

        // Check that the user is still subscribed to the moodleforum.
        $this->assertTrue(\mod_moodleforum\subscriptions::is_subscribed($author->id, $moodleforum));

        // And is subscribed to the discussion again.
        $this->assertTrue(\mod_moodleforum\subscriptions::is_subscribed($author->id, $moodleforum, $discussion->id));

        // And one in the moodleforum subscription tracking table.
        $options = array('userid' => $author->id, 'moodleforum' => $moodleforum->id);
        $count = $DB->count_records('moodleforum_subscriptions', $options);
        $this->assertEquals(1, $count);

        // There should be no record in the discussion subscription tracking table.
        $options = array('userid' => $author->id, 'discussion' => $discussion->id);
        $count = $DB->count_records('moodleforum_discuss_subs', $options);
        $this->assertEquals(0, $count);

        // And unsubscribe again.
        \mod_moodleforum\subscriptions::unsubscribe_user_from_discussion($author->id, $discussion, $modulecontext);

        // Check that the user is still subscribed to the moodleforum.
        $this->assertTrue(\mod_moodleforum\subscriptions::is_subscribed($author->id, $moodleforum));

        // But unsubscribed from the discussion.
        $this->assertFalse(\mod_moodleforum\subscriptions::is_subscribed($author->id, $moodleforum, $discussion->id));

        // And one in the moodleforum subscription tracking table.
        $options = array('userid' => $author->id, 'moodleforum' => $moodleforum->id);
        $count = $DB->count_records('moodleforum_subscriptions', $options);
        $this->assertEquals(1, $count);

        // There should be a record in the discussion subscription tracking table.
        $options = array('userid' => $author->id, 'discussion' => $discussion->id);
        $count = $DB->count_records('moodleforum_discuss_subs', $options);
        $this->assertEquals(1, $count);

        // And subscribe the user again to the discussion.
        \mod_moodleforum\subscriptions::subscribe_user_to_discussion($author->id, $discussion, $modulecontext);

        // Check that the user is still subscribed to the moodleforum.
        $this->assertTrue(\mod_moodleforum\subscriptions::is_subscribed($author->id, $moodleforum));
        $this->assertTrue(\mod_moodleforum\subscriptions::is_subscribed($author->id, $moodleforum));

        // And is subscribed to the discussion again.
        $this->assertTrue(\mod_moodleforum\subscriptions::is_subscribed($author->id, $moodleforum, $discussion->id));

        // There should be no record in the discussion subscription tracking table.
        $options = array('userid' => $author->id, 'discussion' => $discussion->id);
        $count = $DB->count_records('moodleforum_discuss_subs', $options);
        $this->assertEquals(0, $count);

        // And one in the forum subscription tracking table.
        $options = array('userid' => $author->id, 'moodleforum' => $moodleforum->id);
        $count = $DB->count_records('moodleforum_subscriptions', $options);
        $this->assertEquals(1, $count);

        // And unsubscribe again.
        \mod_moodleforum\subscriptions::unsubscribe_user_from_discussion($author->id, $discussion, $modulecontext);

        // But unsubscribed from the discussion.
        $this->assertFalse(\mod_moodleforum\subscriptions::is_subscribed($author->id, $moodleforum, $discussion->id));

        // Check that the user is still subscribed to the moodleforum.
        $this->assertTrue(\mod_moodleforum\subscriptions::is_subscribed($author->id, $moodleforum));

        // There should be a record in the discussion subscription tracking table.
        $options = array('userid' => $author->id, 'discussion' => $discussion->id);
        $count = $DB->count_records('moodleforum_discuss_subs', $options);
        $this->assertEquals(1, $count);

        // And one in the moodleforum subscription tracking table.
        $options = array('userid' => $author->id, 'moodleforum' => $moodleforum->id);
        $count = $DB->count_records('moodleforum_subscriptions', $options);
        $this->assertEquals(1, $count);

        // Now unsubscribe the user from the moodleforum.
        $this->assertTrue(\mod_moodleforum\subscriptions::unsubscribe_user($author->id, $moodleforum, $modulecontext, true));

        // This removes both the moodleforum, and the moodleforum records.
        $options = array('userid' => $author->id, 'discussion' => $discussion->id);
        $count = $DB->count_records('moodleforum_discuss_subs', $options);
        $this->assertEquals(0, $count);
        $options = array('userid' => $author->id, 'moodleforum' => $moodleforum->id);
        $count = $DB->count_records('moodleforum_subscriptions', $options);
        $this->assertEquals(0, $count);

        // And should have reset the discussion cache value.
        $result = \mod_moodleforum\subscriptions::fetch_discussion_subscription($moodleforum->id, $author->id);
        $this->assertInternalType('array', $result);
        $this->assertFalse(isset($result[$discussion->id]));
    }

    /**
     * Test the effect of toggling the discussion subscription status when unsubscribed from the moodleforum.
     */
    public function test_moodleforum_discussion_toggle_moodleforum_unsubscribed() {
        global $DB;

        // Reset the database after testing.
        $this->resetAfterTest(true);

        // Create a course, with a moodleforum.
        $course = $this->getDataGenerator()->create_course();
        $options = array('course' => $course->id, 'forcesubscribe' => moodleforum_CHOOSESUBSCRIBE);
        $moodleforum = $this->getDataGenerator()->create_module('moodleforum', $options);

        // Get the module context.
        $cm = get_coursemodule_from_instance('moodleforum', $moodleforum->id);
        $modulecontext = \context_module::instance($cm->id);

        // Create two users enrolled in the course as students.
        list($author) = $this->helper_create_users($course, 2);

        // Check that the user is currently unsubscribed to the moodleforum.
        $this->assertFalse(\mod_moodleforum\subscriptions::is_subscribed($author->id, $moodleforum));

        // Post a discussion to the moodleforum.
        $discussion = new \stdClass();
        list($discussion->id, $post) = $this->helper_post_to_moodleforum($moodleforum, $author);
        unset($post);
        $discussion->moodleforum = $moodleforum->id;

        // Check that the user is initially unsubscribed to that discussion.
        $this->assertFalse(\mod_moodleforum\subscriptions::is_subscribed($author->id, $moodleforum, $discussion->id));

        // Then subscribe them to the discussion.
        $this->assertTrue(\mod_moodleforum\subscriptions::subscribe_user_to_discussion($author->id,
            $discussion, $modulecontext));

        // An attempt to subscribe again should result in a falsey return to indicate that no change was made.
        $this->assertFalse(\mod_moodleforum\subscriptions::subscribe_user_to_discussion($author->id,
            $discussion, $modulecontext));

        // Check that the user is still unsubscribed from the moodleforum.
        $this->assertFalse(\mod_moodleforum\subscriptions::is_subscribed($author->id, $moodleforum));

        // But subscribed to the discussion.
        $this->assertTrue(\mod_moodleforum\subscriptions::is_subscribed($author->id, $moodleforum, $discussion->id));

        // There should be a record in the discussion subscription tracking table.
        $options = array('userid' => $author->id, 'discussion' => $discussion->id);
        $count = $DB->count_records('moodleforum_discuss_subs', $options);
        $this->assertEquals(1, $count);

        // Now unsubscribe the user again from the discussion.
        \mod_moodleforum\subscriptions::unsubscribe_user_from_discussion($author->id, $discussion, $modulecontext);

        // Check that the user is still unsubscribed from the moodleforum.
        $this->assertFalse(\mod_moodleforum\subscriptions::is_subscribed($author->id, $moodleforum));

        // And is unsubscribed from the discussion again.
        $this->assertFalse(\mod_moodleforum\subscriptions::is_subscribed($author->id, $moodleforum, $discussion->id));

        // There should be no record in the discussion subscription tracking table.
        $options = array('userid' => $author->id, 'discussion' => $discussion->id);
        $count = $DB->count_records('moodleforum_discuss_subs', $options);
        $this->assertEquals(0, $count);

        // And subscribe the user again to the discussion.
        \mod_moodleforum\subscriptions::subscribe_user_to_discussion($author->id, $discussion, $modulecontext);

        // And is subscribed to the discussion again.
        $this->assertTrue(\mod_moodleforum\subscriptions::is_subscribed($author->id, $moodleforum, $discussion->id));

        // Check that the user is still unsubscribed from the moodleforum.
        $this->assertFalse(\mod_moodleforum\subscriptions::is_subscribed($author->id, $moodleforum));

        // There should be a record in the discussion subscription tracking table.
        $options = array('userid' => $author->id, 'discussion' => $discussion->id);
        $count = $DB->count_records('moodleforum_discuss_subs', $options);
        $this->assertEquals(1, $count);

        // And unsubscribe again.
        \mod_moodleforum\subscriptions::unsubscribe_user_from_discussion($author->id, $discussion, $modulecontext);

        // But unsubscribed from the discussion.
        $this->assertFalse(\mod_moodleforum\subscriptions::is_subscribed($author->id, $moodleforum, $discussion->id));

        // Check that the user is still unsubscribed from the moodleforum.
        $this->assertFalse(\mod_moodleforum\subscriptions::is_subscribed($author->id, $moodleforum));

        // There should be no record in the discussion subscription tracking table.
        $options = array('userid' => $author->id, 'discussion' => $discussion->id);
        $count = $DB->count_records('moodleforum_discuss_subs', $options);
        $this->assertEquals(0, $count);
    }

    /**
     * Test that the correct users are returned when fetching subscribed users
     * from a moodleforum where users can choose to subscribe and unsubscribe.
     */
    public function test_fetch_subscribed_users_subscriptions() {
        global $CFG;

        // Reset the database after testing.
        $this->resetAfterTest(true);

        // Create a course, with a moodleforum. where users are initially subscribed.
        $course = $this->getDataGenerator()->create_course();
        $options = array('course' => $course->id, 'forcesubscribe' => moodleforum_INITIALSUBSCRIBE);
        $moodleforum = $this->getDataGenerator()->create_module('moodleforum', $options);

        // Get the module context.
        $cm = get_coursemodule_from_instance('moodleforum', $moodleforum->id);
        $modulecontext = \context_module::instance($cm->id);

        // Create some user enrolled in the course as a student.
        $usercount = 5;
        $users = $this->helper_create_users($course, $usercount);

        // All users should be subscribed.
        $subscribers = \mod_moodleforum\subscriptions::get_subscribed_users($moodleforum, $modulecontext);
        $this->assertEquals($usercount, count($subscribers));

        // Subscribe the guest user too to the moodleforum - they should never be returned by this function.
        $this->getDataGenerator()->enrol_user($CFG->siteguest, $course->id);
        $subscribers = \mod_moodleforum\subscriptions::get_subscribed_users($moodleforum, $modulecontext);
        $this->assertEquals($usercount, count($subscribers));

        // Unsubscribe 2 users.
        $unsubscribedcount = 2;
        for ($i = 0; $i < $unsubscribedcount; $i++) {
            \mod_moodleforum\subscriptions::unsubscribe_user($users[$i]->id, $moodleforum, $modulecontext);
        }

        // The subscription count should now take into account those users who have been unsubscribed.
        $subscribers = \mod_moodleforum\subscriptions::get_subscribed_users($moodleforum, $modulecontext);
        $this->assertEquals($usercount - $unsubscribedcount, count($subscribers));
    }

    /**
     * Test that the correct users are returned hwen fetching subscribed users from a moodleforum where users are forcibly
     * subscribed.
     */
    public function test_fetch_subscribed_users_forced() {
        // Reset the database after testing.
        $this->resetAfterTest(true);

        // Create a course, with a moodleforum. where users are initially subscribed.
        $course = $this->getDataGenerator()->create_course();
        $options = array('course' => $course->id, 'forcesubscribe' => moodleforum_FORCESUBSCRIBE);
        $moodleforum = $this->getDataGenerator()->create_module('moodleforum', $options);

        // Get the module context.
        $cm = get_coursemodule_from_instance('moodleforum', $moodleforum->id);
        $modulecontext = \context_module::instance($cm->id);

        // Create some user enrolled in the course as a student.
        $usercount = 5;
        $this->helper_create_users($course, $usercount);

        // All users should be subscribed.
        $subscribers = \mod_moodleforum\subscriptions::get_subscribed_users($moodleforum, $modulecontext);
        $this->assertEquals($usercount, count($subscribers));
    }

    /**
     * Test that unusual combinations of discussion subscriptions do not affect the subscribed user list.
     */
    public function test_fetch_subscribed_users_discussion_subscriptions() {
        global $DB;

        // Reset after testing.
        $this->resetAfterTest(true);

        // Create a course, with a moodleforum. where users are initially subscribed.
        $course = $this->getDataGenerator()->create_course();
        $options = array('course' => $course->id, 'forcesubscribe' => moodleforum_INITIALSUBSCRIBE);
        $moodleforum = $this->getDataGenerator()->create_module('moodleforum', $options);

        // Get the module context.
        $cm = get_coursemodule_from_instance('moodleforum', $moodleforum->id);
        $modulecontext = \context_module::instance($cm->id);

        // Create some user enrolled in the course as a student.
        $usercount = 5;
        $users = $this->helper_create_users($course, $usercount);

        // Create the discussion.
        $discussion = new \stdClass();
        list($discussion->id, $post) = $this->helper_post_to_moodleforum($moodleforum, $users[0]);
        unset($post);
        $discussion->moodleforum = $moodleforum->id;

        // All users should be subscribed.
        $subscribers = \mod_moodleforum\subscriptions::get_subscribed_users($moodleforum, $modulecontext);
        $this->assertEquals($usercount, count($subscribers));
        $subscribers = \mod_moodleforum\subscriptions::get_subscribed_users($moodleforum, $modulecontext, null, true);
        $this->assertEquals($usercount, count($subscribers));

        \mod_moodleforum\subscriptions::unsubscribe_user_from_discussion($users[0]->id, $discussion, $modulecontext);

        // All users should be subscribed.
        $subscribers = \mod_moodleforum\subscriptions::get_subscribed_users($moodleforum, $modulecontext);
        $this->assertEquals($usercount, count($subscribers));

        // All users should be subscribed.
        $subscribers = \mod_moodleforum\subscriptions::get_subscribed_users($moodleforum, $modulecontext, null, true);
        $this->assertEquals($usercount, count($subscribers));

        // Manually insert an extra subscription for one of the users.
        $record = new stdClass();
        $record->userid = $users[2]->id;
        $record->moodleforum = $moodleforum->id;
        $record->discussion = $discussion->id;
        $record->preference = time();
        $DB->insert_record('moodleforum_discuss_subs', $record);

        // The discussion count should not have changed.
        $subscribers = \mod_moodleforum\subscriptions::get_subscribed_users($moodleforum, $modulecontext);
        $this->assertEquals($usercount, count($subscribers));
        $subscribers = \mod_moodleforum\subscriptions::get_subscribed_users($moodleforum, $modulecontext, null, true);
        $this->assertEquals($usercount, count($subscribers));

        // Unsubscribe 2 users.
        $unsubscribedcount = 2;
        for ($i = 0; $i < $unsubscribedcount; $i++) {
            \mod_moodleforum\subscriptions::unsubscribe_user($users[$i]->id, $moodleforum, $modulecontext);
        }

        // The subscription count should now take into account those users who have been unsubscribed.
        $subscribers = \mod_moodleforum\subscriptions::get_subscribed_users($moodleforum, $modulecontext);
        $this->assertEquals($usercount - $unsubscribedcount, count($subscribers));
        $subscribers = \mod_moodleforum\subscriptions::get_subscribed_users($moodleforum, $modulecontext, null, true);
        $this->assertEquals($usercount - $unsubscribedcount, count($subscribers));

        // Now subscribe one of those users back to the discussion.
        $subedusers = 1;
        for ($i = 0; $i < $subedusers; $i++) {
            \mod_moodleforum\subscriptions::subscribe_user_to_discussion($users[$i]->id, $discussion, $modulecontext);
        }
        $subscribers = \mod_moodleforum\subscriptions::get_subscribed_users($moodleforum, $modulecontext);
        $this->assertEquals($usercount - $unsubscribedcount, count($subscribers));
        $subscribers = \mod_moodleforum\subscriptions::get_subscribed_users($moodleforum, $modulecontext, null, true);
        $this->assertEquals($usercount - $unsubscribedcount + $subedusers, count($subscribers));
    }

    /**
     * Test whether a user is force-subscribed to a moodleforum.
     */
    public function test_force_subscribed_to_moodleforum() {
        global $DB;

        // Reset database after testing.
        $this->resetAfterTest(true);

        // Create a course, with a moodleforum.
        $course = $this->getDataGenerator()->create_course();

        $options = array('course' => $course->id, 'forcesubscribe' => moodleforum_FORCESUBSCRIBE);
        $moodleforum = $this->getDataGenerator()->create_module('moodleforum', $options);

        // Create a user enrolled in the course as a student.
        $roleids = $DB->get_records_menu('role', null, '', 'shortname, id');
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, $roleids['student']);

        // Check that the user is currently subscribed to the moodleforum.
        $this->assertTrue(\mod_moodleforum\subscriptions::is_subscribed($user->id, $moodleforum));

        // Remove the allowforcesubscribe capability from the user.
        $cm = get_coursemodule_from_instance('moodleforum', $moodleforum->id);
        $context = \context_module::instance($cm->id);
        assign_capability('mod/moodleforum:allowforcesubscribe', CAP_PROHIBIT, $roleids['student'], $context);
        $context->mark_dirty();
        $this->assertFalse(has_capability('mod/moodleforum:allowforcesubscribe', $context, $user->id));
    }

    /**
     * Test that the subscription cache can be pre-filled.
     */
    public function test_subscription_cache_prefill() {
        global $DB;

        // Reset the database after testing.
        $this->resetAfterTest(true);

        // Create a course, with a moodleforum.
        $course = $this->getDataGenerator()->create_course();
        $options = array('course' => $course->id, 'forcesubscribe' => moodleforum_INITIALSUBSCRIBE);
        $moodleforum = $this->getDataGenerator()->create_module('moodleforum', $options);

        // Create some users.
        $users = $this->helper_create_users($course, 20);

        // Reset the subscription cache.
        \mod_moodleforum\subscriptions::reset_moodleforum_cache();

        // Filling the subscription cache should only use a single query, except for Postgres, which delegates actual reading
        // to Cursors, thus tripling the amount of queries. We intend to test the cache, though, so no worries.
        // $startcount = $DB->perf_get_reads();
        $this->assertNull(\mod_moodleforum\subscriptions::fill_subscription_cache($moodleforum->id));
        $postfillcount = $DB->perf_get_reads();
        // $this->assertEquals(1, $postfillcount - $startcount); Fails since M35+Postgres because cursors are used.

        // Now fetch some subscriptions from that moodleforum - these should use
        // the cache and not perform additional queries.
        foreach ($users as $user) {
            $this->assertTrue(\mod_moodleforum\subscriptions::fetch_subscription_cache($moodleforum->id, $user->id));
        }
        $finalcount = $DB->perf_get_reads();
        $this->assertEquals($finalcount, $postfillcount);
    }

    /**
     * Test that the subscription cache can filled user-at-a-time.
     */
    public function test_subscription_cache_fill() {
        global $DB;

        // Reset the database after testing.
        $this->resetAfterTest(true);

        // Create a course, with a moodleforum.
        $course = $this->getDataGenerator()->create_course();

        $options = array('course' => $course->id, 'forcesubscribe' => moodleforum_INITIALSUBSCRIBE);
        $moodleforum = $this->getDataGenerator()->create_module('moodleforum', $options);

        // Create some users.
        $users = $this->helper_create_users($course, 20);

        // Reset the subscription cache.
        \mod_moodleforum\subscriptions::reset_moodleforum_cache();

        // Filling the subscription cache should only use a single query.
        $startcount = $DB->perf_get_reads();

        // Fetch some subscriptions from that moodleforum - these should not use the cache and will perform additional queries.
        foreach ($users as $user) {
            $this->assertTrue(\mod_moodleforum\subscriptions::fetch_subscription_cache($moodleforum->id, $user->id));
        }
        $finalcount = $DB->perf_get_reads();
        $this->assertEquals(20, $finalcount - $startcount);
    }

    /**
     * Test that the discussion subscription cache can filled course-at-a-time.
     */
    public function test_discussion_subscription_cache_fill_for_course() {
        global $DB;

        // Reset the database after testing.
        $this->resetAfterTest(true);

        // Create a course, with a moodleforum.
        $course = $this->getDataGenerator()->create_course();

        // Create the moodleforums.
        $options = array('course' => $course->id, 'forcesubscribe' => moodleforum_DISALLOWSUBSCRIBE);
        $disallowmoodleforum = $this->getDataGenerator()->create_module('moodleforum', $options);
        $options = array('course' => $course->id, 'forcesubscribe' => moodleforum_CHOOSESUBSCRIBE);
        $choosemoodleforum = $this->getDataGenerator()->create_module('moodleforum', $options);
        $options = array('course' => $course->id, 'forcesubscribe' => moodleforum_INITIALSUBSCRIBE);
        $initialmoodleforum = $this->getDataGenerator()->create_module('moodleforum', $options);

        // Create some users and keep a reference to the first user.
        $users = $this->helper_create_users($course, 20);
        $user = reset($users);

        // Reset the subscription caches.
        \mod_moodleforum\subscriptions::reset_moodleforum_cache();

        // $startcount = $DB->perf_get_reads();
        $result = \mod_moodleforum\subscriptions::fill_subscription_cache_for_course($course->id, $user->id);
        $this->assertNull($result);
        $postfillcount = $DB->perf_get_reads();
        // $this->assertEquals(1, $postfillcount - $startcount); Fails since M35+Postgres because cursors are used.
        $this->assertFalse(\mod_moodleforum\subscriptions::fetch_subscription_cache($disallowmoodleforum->id, $user->id));
        $this->assertFalse(\mod_moodleforum\subscriptions::fetch_subscription_cache($choosemoodleforum->id, $user->id));
        $this->assertTrue(\mod_moodleforum\subscriptions::fetch_subscription_cache($initialmoodleforum->id, $user->id));
        $finalcount = $DB->perf_get_reads();
        $this->assertEquals(0, $finalcount - $postfillcount);

        // Test for all users.
        foreach ($users as $user) {
            $result = \mod_moodleforum\subscriptions::fill_subscription_cache_for_course($course->id, $user->id);
            $this->assertFalse(\mod_moodleforum\subscriptions::fetch_subscription_cache($disallowmoodleforum->id, $user->id));
            $this->assertFalse(\mod_moodleforum\subscriptions::fetch_subscription_cache($choosemoodleforum->id, $user->id));
            $this->assertTrue(\mod_moodleforum\subscriptions::fetch_subscription_cache($initialmoodleforum->id, $user->id));
        }
        $finalcount = $DB->perf_get_reads();
        // $this->assertEquals(count($users), $finalcount - $postfillcount); Replaced by the following.
        $reads = $finalcount - $postfillcount;
        if ($reads === 20 || $reads === 60) {
            // Postgres uses cursors since M35 and therefore requires triple the amount of reads.
            $this->assertTrue(true);
        } else {
            $this->assertTrue(false, 'Unexpected amount of reads required to fill discussion subscription cache for a course.');
        }
    }

    /**
     * Test that the discussion subscription cache can be forcibly updated for a user.
     */
    public function test_discussion_subscription_cache_prefill() {
        global $DB;

        // Reset the database after testing.
        $this->resetAfterTest(true);

        // Create a course, with a moodleforum.
        $course = $this->getDataGenerator()->create_course();
        $options = array('course' => $course->id, 'forcesubscribe' => moodleforum_INITIALSUBSCRIBE);
        $moodleforum = $this->getDataGenerator()->create_module('moodleforum', $options);

        // Get the module context.
        $cm = get_coursemodule_from_instance('moodleforum', $moodleforum->id);
        $modulecontext = \context_module::instance($cm->id);

        // Create some users.
        $users = $this->helper_create_users($course, 20);

        // Post some discussions to the moodleforum.
        $discussions = array();
        $author = $users[0];
        for ($i = 0; $i < 20; $i++) {
            $discussion = new \stdClass();
            list($discussion->id, $post) = $this->helper_post_to_moodleforum($moodleforum, $author);
            unset($post);
            $discussion->moodleforum = $moodleforum->id;
            $discussions[] = $discussion;
        }

        // Unsubscribe half the users from the half the discussions.
        $moodleforumcount = 0;
        $usercount = 0;
        foreach ($discussions as $data) {
            if ($moodleforumcount % 2) {
                continue;
            }
            foreach ($users as $user) {
                if ($usercount % 2) {
                    continue;
                }
                \mod_moodleforum\subscriptions::unsubscribe_user_from_discussion($user->id, $discussion, $modulecontext);
                $usercount++;
            }
            $moodleforumcount++;
        }

        // Reset the subscription caches.
        \mod_moodleforum\subscriptions::reset_moodleforum_cache();
        \mod_moodleforum\subscriptions::reset_discussion_cache();

        // Filling the discussion subscription cache should only use a single query.
        // $startcount = $DB->perf_get_reads();
        $this->assertNull(\mod_moodleforum\subscriptions::fill_discussion_subscription_cache($moodleforum->id));
        $postfillcount = $DB->perf_get_reads();
        // $this->assertEquals(1, $postfillcount - $startcount); Fails since M35+Postgres because cursors are used.

        // Now fetch some subscriptions from that moodleforum - these should use
        // the cache and not perform additional queries.
        foreach ($users as $user) {
            $result = \mod_moodleforum\subscriptions::fetch_discussion_subscription($moodleforum->id, $user->id);
            $this->assertInternalType('array', $result);
        }
        $finalcount = $DB->perf_get_reads();
        $this->assertEquals(0, $finalcount - $postfillcount);
    }

    /**
     * Test that the discussion subscription cache can filled user-at-a-time.
     */
    public function test_discussion_subscription_cache_fill() {
        global $DB;

        // Reset the database after testing.
        $this->resetAfterTest(true);

        // Create a course, with a moodleforum.
        $course = $this->getDataGenerator()->create_course();
        $options = array('course' => $course->id, 'forcesubscribe' => moodleforum_INITIALSUBSCRIBE);
        $moodleforum = $this->getDataGenerator()->create_module('moodleforum', $options);

        // Create some users.
        $users = $this->helper_create_users($course, 20);

        // Post some discussions to the moodleforum.
        $discussions = array();
        $author = $users[0];
        for ($i = 0; $i < 20; $i++) {
            $discussion = new \stdClass();
            list($discussion->id, $post) = $this->helper_post_to_moodleforum($moodleforum, $author);
            unset($post);
            $discussion->moodleforum = $moodleforum->id;
            $discussions[] = $discussion;
        }

        // Get the module context.
        $cm = get_coursemodule_from_instance('moodleforum', $moodleforum->id);
        $modulecontext = \context_module::instance($cm->id);

        // Unsubscribe half the users from the half the discussions.
        $moodleforumcount = 0;
        $usercount = 0;
        foreach ($discussions as $data) {
            if ($moodleforumcount % 2) {
                continue;
            }
            foreach ($users as $user) {
                if ($usercount % 2) {
                    continue;
                }
                \mod_moodleforum\subscriptions::unsubscribe_user_from_discussion($user->id, $discussion, $modulecontext);
                $usercount++;
            }
            $moodleforumcount++;
        }

        // Reset the subscription caches.
        \mod_moodleforum\subscriptions::reset_moodleforum_cache();
        \mod_moodleforum\subscriptions::reset_discussion_cache();

        $startcount = $DB->perf_get_reads();

        // Now fetch some subscriptions from that moodleforum - these should use
        // the cache and not perform additional queries.
        foreach ($users as $user) {
            $result = \mod_moodleforum\subscriptions::fetch_discussion_subscription($moodleforum->id, $user->id);
            $this->assertInternalType('array', $result);
        }
        $finalcount = $DB->perf_get_reads();
        // $this->assertEquals(20, $finalcount - $startcount); Replaced by the following.
        $reads = $finalcount - $startcount;
        if ($reads === 20 || $reads === 60) {
            // Postgres uses cursors since M35 and therefore requires triple the amount of reads.
            $this->assertTrue(true);
        } else {
            $this->assertTrue(false, 'Unexpected amount of reads required to fill discussion subscription cache.');
        }

    }

    /**
     * Test that after toggling the moodleforum subscription as another user,
     * the discussion subscription functionality works as expected.
     */
    public function test_moodleforum_subscribe_toggle_as_other_repeat_subscriptions() {
        global $DB;

        // Reset the database after testing.
        $this->resetAfterTest(true);

        // Create a course, with a moodleforum.
        $course = $this->getDataGenerator()->create_course();
        $options = array('course' => $course->id, 'forcesubscribe' => moodleforum_CHOOSESUBSCRIBE);
        $moodleforum = $this->getDataGenerator()->create_module('moodleforum', $options);

        // Get the module context.
        $cm = get_coursemodule_from_instance('moodleforum', $moodleforum->id);
        $modulecontext = \context_module::instance($cm->id);

        // Create a user enrolled in the course as a student.
        list($user) = $this->helper_create_users($course, 1);

        // Post a discussion to the moodleforum.
        $discussion = new \stdClass();
        list($discussion->id, $post) = $this->helper_post_to_moodleforum($moodleforum, $user);
        unset($post);
        $discussion->moodleforum = $moodleforum->id;

        // Confirm that the user is currently not subscribed to the moodleforum.
        $this->assertFalse(\mod_moodleforum\subscriptions::is_subscribed($user->id, $moodleforum));

        // Confirm that the user is unsubscribed from the discussion too.
        $this->assertFalse(\mod_moodleforum\subscriptions::is_subscribed($user->id, $moodleforum, $discussion->id));

        // Confirm that we have no records in either of the subscription tables.
        $this->assertEquals(0, $DB->count_records('moodleforum_subscriptions', array(
            'userid'         => $user->id,
            'moodleforum' => $moodleforum->id,
        )));
        $this->assertEquals(0, $DB->count_records('moodleforum_discuss_subs', array(
            'userid'     => $user->id,
            'discussion' => $discussion->id,
        )));

        // Subscribing to the moodleforum should create a record in the subscriptions table,
        // but not the moodleforum discussion subscriptions table.
        \mod_moodleforum\subscriptions::subscribe_user($user->id, $moodleforum, $modulecontext);
        $this->assertEquals(1, $DB->count_records('moodleforum_subscriptions', array(
            'userid'         => $user->id,
            'moodleforum' => $moodleforum->id,
        )));
        $this->assertEquals(0, $DB->count_records('moodleforum_discuss_subs', array(
            'userid'     => $user->id,
            'discussion' => $discussion->id,
        )));

        // Now unsubscribe from the discussion. This should return true.
        $uid = $user->id;
        $this->assertTrue(\mod_moodleforum\subscriptions::unsubscribe_user_from_discussion($uid, $discussion, $modulecontext));

        // Attempting to unsubscribe again should return false because no change was made.
        $this->assertFalse(\mod_moodleforum\subscriptions::unsubscribe_user_from_discussion($uid, $discussion, $modulecontext));

        // Subscribing to the discussion again should return truthfully as the subscription preference was removed.
        $this->assertTrue(\mod_moodleforum\subscriptions::subscribe_user_to_discussion($user->id, $discussion, $modulecontext));

        // Attempting to subscribe again should return false because no change was made.
        $this->assertFalse(\mod_moodleforum\subscriptions::subscribe_user_to_discussion($user->id, $discussion, $modulecontext));

        // Now unsubscribe from the discussion. This should return true once more.
        $this->assertTrue(\mod_moodleforum\subscriptions::unsubscribe_user_from_discussion($uid, $discussion, $modulecontext));

        // And unsubscribing from the moodleforum but not as a request from the user should maintain their preference.
        \mod_moodleforum\subscriptions::unsubscribe_user($user->id, $moodleforum, $modulecontext);

        $this->assertEquals(0, $DB->count_records('moodleforum_subscriptions', array(
            'userid'         => $user->id,
            'moodleforum' => $moodleforum->id,
        )));
        $this->assertEquals(1, $DB->count_records('moodleforum_discuss_subs', array(
            'userid'     => $user->id,
            'discussion' => $discussion->id,
        )));

        // Subscribing to the discussion should return truthfully because a change was made.
        $this->assertTrue(\mod_moodleforum\subscriptions::subscribe_user_to_discussion($user->id, $discussion, $modulecontext));
        $this->assertEquals(0, $DB->count_records('moodleforum_subscriptions', array(
            'userid'         => $user->id,
            'moodleforum' => $moodleforum->id,
        )));
        $this->assertEquals(1, $DB->count_records('moodleforum_discuss_subs', array(
            'userid'     => $user->id,
            'discussion' => $discussion->id,
        )));
    }

    /**
     * Returns a list of possible states.
     *
     * @return array
     */
    public function is_subscribable_moodleforums() {
        return [
            [
                'forcesubscribe' => moodleforum_DISALLOWSUBSCRIBE,
            ],
            [
                'forcesubscribe' => moodleforum_CHOOSESUBSCRIBE,
            ],
            [
                'forcesubscribe' => moodleforum_INITIALSUBSCRIBE,
            ],
            [
                'forcesubscribe' => moodleforum_FORCESUBSCRIBE,
            ],
        ];
    }

    /**
     * Returns whether a moodleforum is subscribable.
     *
     * @return array
     */
    public function is_subscribable_provider() {
        $data = [];
        foreach ($this->is_subscribable_moodleforums() as $moodleforum) {
            $data[] = [$moodleforum];
        }

        return $data;
    }

    /**
     * Tests if a moodleforum is subscribable when a user is logged out.
     *
     * @param array $options
     *
     * @dataProvider is_subscribable_provider
     */
    public function test_is_subscribable_logged_out($options) {
        $this->resetAfterTest(true);

        // Create a course, with a moodleforum.
        $course = $this->getDataGenerator()->create_course();
        $options['course'] = $course->id;
        $moodleforum = $this->getDataGenerator()->create_module('moodleforum', $options);

        $this->assertFalse(\mod_moodleforum\subscriptions::is_subscribable($moodleforum));
    }

    /**
     * Tests if a moodleforum is subscribable by a guest.
     *
     * @param array $options
     *
     * @dataProvider is_subscribable_provider
     */
    public function test_is_subscribable_is_guest($options) {
        global $DB;
        $this->resetAfterTest(true);

        // Create a guest user.
        $guest = $DB->get_record('user', array('username' => 'guest'));
        $this->setUser($guest);

        // Create a course, with a moodleforum.
        $course = $this->getDataGenerator()->create_course();
        $options['course'] = $course->id;
        $moodleforum = $this->getDataGenerator()->create_module('moodleforum', $options);

        $this->assertFalse(\mod_moodleforum\subscriptions::is_subscribable($moodleforum));
    }

    /**
     * Returns subscription obtions.
     * @return array
     */
    public function is_subscribable_loggedin_provider() {
        return [
            [
                ['forcesubscribe' => moodleforum_DISALLOWSUBSCRIBE],
                false,
            ],
            [
                ['forcesubscribe' => moodleforum_CHOOSESUBSCRIBE],
                true,
            ],
            [
                ['forcesubscribe' => moodleforum_INITIALSUBSCRIBE],
                true,
            ],
            [
                ['forcesubscribe' => moodleforum_FORCESUBSCRIBE],
                false,
            ],
        ];
    }

    /**
     * Tests if a moodleforum is subscribable when a user is logged in.
     *
     * @param array $options
     * @param bool  $expect
     *
     * @dataProvider is_subscribable_loggedin_provider
     */
    public function test_is_subscribable_loggedin($options, $expect) {
        // Reset the database after testing.
        $this->resetAfterTest(true);

        // Create a course, with a moodleforum.
        $course = $this->getDataGenerator()->create_course();
        $options['course'] = $course->id;
        $moodleforum = $this->getDataGenerator()->create_module('moodleforum', $options);

        // Create a new user.
        $user = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($user->id, $course->id);
        $this->setUser($user);

        $this->assertEquals($expect, \mod_moodleforum\subscriptions::is_subscribable($moodleforum));
    }
}