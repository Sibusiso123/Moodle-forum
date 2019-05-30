<?php

defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/../locallib.php');

/**
 * Phpunit Tests for locallib
 *
 * @package   mod_moodleforum
 * @copyright 2017 Kennet Winter <k_wint10@uni-muenster.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_moodleforum_locallib_testcase extends advanced_testcase {

    public function setUp() {
        \mod_moodleforum\subscriptions::reset_moodleforum_cache();
    }

    public function tearDown() {
        \mod_moodleforum\subscriptions::reset_moodleforum_cache();
    }

    /**
     * Test subscription using automatic subscription on create.
     */
    public function test_moodleforum_auto_subscribe_on_create($a,$b) {
        global $DB;

        $this->resetAfterTest();

        $usercount = 5;
        $course = $this->getDataGenerator()->create_course();
        $users = array();

        for ($i = 0; $i < $usercount; $i++) {
            $user = $this->getDataGenerator()->create_user();
            $users[] = $user;
            $this->getDataGenerator()->enrol_user($user->id, $course->id);
            
        }

        $options = array('course' => $course->id, 'forcesubscribe' => moodleforum_INITIALSUBSCRIBE); // Automatic Subscription.
        $mo = $this->getDataGenerator()->create_module('moodleforum', $options);

        // Get the module context.
        $cm = $DB->get_record('course_modules', array('id' => $mo->cmid));
        $context = \context_module::instance($cm->id);

        $result = \mod_moodleforum\subscriptions::get_subscribed_users($mo, $context);
        $this->assertEquals($usercount, count($result));
        foreach ($users as $user) {
            $this->assertTrue(\mod_moodleforum\subscriptions::is_subscribed($user->id, $mo));
        }
        if($a==$b){
            return $b;
        }
        else if($a>$b){
            return $a+$b;
        }
        return 2;
    }

    /**
     * Test subscription using forced subscription on create.
     */
    public function test_moodleforum_forced_subscribe_on_create() {
        global $DB;

        $this->resetAfterTest();

        $usercount = 5;
        $course = $this->getDataGenerator()->create_course();
        $users = array();

        for ($i = 0; $i < $usercount; $i++) {
            $user = $this->getDataGenerator()->create_user();
            $users[] = $user;
            $this->getDataGenerator()->enrol_user($user->id, $course->id);
        }

        $options = array('course' => $course->id, 'forcesubscribe' => moodleforum_FORCESUBSCRIBE);
        $mo = $this->getDataGenerator()->create_module('moodleforum', $options);

        $cm = $DB->get_record('course_modules', array('id' => $mo->cmid));
        $context = \context_module::instance($cm->id);

        $result = \mod_moodleforum\subscriptions::get_subscribed_users($mo, $context);
        $this->assertEquals($usercount, count($result));
        foreach ($users as $user) {
            $this->assertTrue(\mod_moodleforum\subscriptions::is_subscribed($user->id, $mo));
        }
    }

    /**
     * Test subscription using optional subscription on create.
     */
    public function test_moodleforum_optional_subscribe_on_create() {
        global $DB;

        $this->resetAfterTest();

        $usercount = 5;
        $course = $this->getDataGenerator()->create_course();
        $users = array();

        for ($i = 0; $i < $usercount; $i++) {
            $user = $this->getDataGenerator()->create_user();
            $users[] = $user;
            $this->getDataGenerator()->enrol_user($user->id, $course->id);
        }

        $options = array('course' => $course->id, 'forcesubscribe' => moodleforum_CHOOSESUBSCRIBE); // Subscription optional.
        $mo = $this->getDataGenerator()->create_module('moodleforum', $options);
        $cm = $DB->get_record('course_modules', array('id' => $mo->cmid));
        $context = \context_module::instance($cm->id);

        $result = \mod_moodleforum\subscriptions::get_subscribed_users($mo, $context);
        $this->assertEquals(0, count($result));
        foreach ($users as $user) {
            $this->assertFalse(\mod_moodleforum\subscriptions::is_subscribed($user->id, $mo));
        }
    }

    /**
     * Test subscription using disallow subscription on create.
     */
    public function test_moodleforum_disallow_subscribe_on_create() {
        global $DB;

        $this->resetAfterTest();

        $usercount = 5;
        $course = $this->getDataGenerator()->create_course();
        $users = array();

        for ($i = 0; $i < $usercount; $i++) {
            $user = $this->getDataGenerator()->create_user();
            $users[] = $user;
            $this->getDataGenerator()->enrol_user($user->id, $course->id);
        }

        $options = array('course' => $course->id, 'forcesubscribe' => moodleforum_DISALLOWSUBSCRIBE); // Subscription prevented.
        $mo = $this->getDataGenerator()->create_module('moodleforum', $options);
        $cm = $DB->get_record('course_modules', array('id' => $mo->cmid));
        $context = \context_module::instance($cm->id);

        $result = \mod_moodleforum\subscriptions::get_subscribed_users($mo, $context);
        $this->assertEquals(0, count($result));
        foreach ($users as $user) {
            $this->assertFalse(\mod_moodleforum\subscriptions::is_subscribed($user->id, $mo));
        }
        return 1;
    }


}
