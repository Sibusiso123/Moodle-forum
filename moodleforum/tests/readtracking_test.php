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
 * @package    mod_moodleforum
 * @copyright  2017 Kennet Winter <k_wint10@uni-muenster.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/moodleforum/locallib.php');

/**
 * PHPUnit Tests for testing readtracking.
 *
 * @package   mod_moodleforum
 * @copyright 2017 Kennet Winter <k_wint10@uni-muenster.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_moodleforum_readtracking_testcase extends advanced_testcase {

    /**
     * Test the logic in the moodleforum_can_track_moodleforums() function.
     */
    public function test_moodleforum_can_track_moodleforums() {

        // Reset after testing.
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $options = array('course' => $course->id, 'trackingtype' => moodleforum_TRACKING_OFF); // Off.
        $mooff = $this->getDataGenerator()->create_module('moodleforum', $options);

        $options = array('course' => $course->id, 'trackingtype' => moodleforum_TRACKING_FORCED); // On.
        $moforce = $this->getDataGenerator()->create_module('moodleforum', $options);

        $options = array('course' => $course->id, 'trackingtype' => moodleforum_TRACKING_OPTIONAL); // Optional.
        $mooptional = $this->getDataGenerator()->create_module('moodleforum', $options);

        // Allow force.
        set_config('allowforcedreadtracking', 1, 'moodleforum');

        // Modleoverflow off, should be off.
        $result = \mod_moodleforum\readtracking::moodleforum_can_track_moodleforums($mooff);
        $this->assertEquals(false, $result);

        // moodleforum on, should be off.
        $result = \mod_moodleforum\readtracking::moodleforum_can_track_moodleforums($moforce);
        $this->assertEquals(false, $result);

        // moodleforum optional, should be false.
        $result = \mod_moodleforum\readtracking::moodleforum_can_track_moodleforums($mooptional);
        $this->assertEquals(false, $result);

        // Don't allow force.
        set_config('allowforcedreadtracking', 0, 'moodleforum');

        // moodleforum off, should be off.
        $result = \mod_moodleforum\readtracking::moodleforum_can_track_moodleforums($mooff);
        $this->assertEquals(false, $result);

        // moodleforum on, should be off.
        $result = \mod_moodleforum\readtracking::moodleforum_can_track_moodleforums($moforce);
        $this->assertEquals(false, $result);

        // moodleforum optional, should be off.
        $result = \mod_moodleforum\readtracking::moodleforum_can_track_moodleforums($mooptional);
        $this->assertEquals(false, $result);
    }

    /**
     * Test the logic in the test_forum_tp_is_tracked() function.
     */
    public function test_moodleforum_is_tracked() {

        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();

        $options = array('course' => $course->id, 'trackingtype' => moodleforum_TRACKING_OPTIONAL);
        $mooptional = $this->getDataGenerator()->create_module('moodleforum', $options);

        $options = array('course' => $course->id, 'trackingtype' => moodleforum_TRACKING_FORCED);
        $moforce = $this->getDataGenerator()->create_module('moodleforum', $options);

        $options = array('course' => $course->id, 'trackingtype' => moodleforum_TRACKING_OFF);
        $mooff = $this->getDataGenerator()->create_module('moodleforum', $options);

        // Allow force.
        set_config('allowforcedreadtracking', 1, 'moodleforum');

        // moodleforum off, should be off.
        $result = \mod_moodleforum\readtracking::moodleforum_is_tracked($mooff);
        $this->assertEquals(false, $result);

        // moodleforum force, should be off.
        $result = \mod_moodleforum\readtracking::moodleforum_is_tracked($moforce);
        $this->assertEquals(false, $result);

        // moodleforum optional, should be off.
        $result = \mod_moodleforum\readtracking::moodleforum_is_tracked($mooptional);
        $this->assertEquals(false, $result);

        // Don't allow force.
        set_config('allowforcedreadtracking', 0, 'moodleforum');

        // moodleforum off, should be off.
        $result = \mod_moodleforum\readtracking::moodleforum_is_tracked($mooff);
        $this->assertEquals(false, $result);

        // moodleforum force, should be off.
        $result = \mod_moodleforum\readtracking::moodleforum_is_tracked($moforce);
        $this->assertEquals(false, $result);

        // moodleforum optional, should be off.
        $result = \mod_moodleforum\readtracking::moodleforum_is_tracked($mooptional);
        $this->assertEquals(false, $result);

        // Stop tracking so we can test again.
        \mod_moodleforum\readtracking::moodleforum_stop_tracking($moforce->id);
        \mod_moodleforum\readtracking::moodleforum_stop_tracking($mooptional->id);

        // Allow force.
        set_config('allowforcedreadtracking', 1, 'moodleforum');

        // Preference off, moodleforum force, should be on.
        $result = \mod_moodleforum\readtracking::moodleforum_is_tracked($moforce);
        $this->assertEquals(false, $result);

        // Preference off, moodleforum optional, should be on.
        $result = \mod_moodleforum\readtracking::moodleforum_is_tracked($mooptional);
        $this->assertEquals(false, $result);

        // Don't allow force.
        set_config('allowforcedreadtracking', 0, 'moodleforum');

        // Preference off, moodleforum force, should be on.
        $result = \mod_moodleforum\readtracking::moodleforum_is_tracked($moforce);
        $this->assertEquals(false, $result);

        // Preference off, moodleforum optional, should be on.
        $result = \mod_moodleforum\readtracking::moodleforum_is_tracked($mooptional);
        $this->assertEquals(false, $result);
    }
}