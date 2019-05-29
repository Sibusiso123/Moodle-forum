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
 * Provides the restore activity task class
 *
 * @package   mod_moodleforum
 * @category  backup
 * @copyright 2017 Kennet Winter <k_wint10@uni-muenster.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/moodleforum/backup/moodle2/restore_moodleforum_stepslib.php');

/**
 * Restore task for the moodleforum activity module
 *
 * Provides all the settings and steps to perform complete restore of the activity.
 *
 * @package   mod_moodleforum
 * @category  backup
 * @copyright 2016 Your Name <your@email.address>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_moodleforum_activity_task extends restore_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // We have just one structure step here.
        $this->add_step(new restore_moodleforum_activity_structure_step('moodleforum_structure', 'moodleforum.xml'));
    }

    /**
     * Define the contents in the activity that must be
     * processed by the link decoder
     */
    static public function define_decode_contents() {
        $contents = array();

        $contents[] = new restore_decode_content('moodleforum', array('intro'), 'moodleforum');
        $contents[] = new restore_decode_content('moodleforum_posts', array('message'), 'moodleforum_post');

        return $contents;
    }

    /**
     * Define the decoding rules for links belonging
     * to the activity to be executed by the link decoder
     */
    static public function define_decode_rules() {
        $rules = array();

        $rules[] = new restore_decode_rule('moodleforumVIEWBYID', '/mod/moodleforum/view.php?id=$1', 'course_module');
        $rules[] = new restore_decode_rule('moodleforumINDEX', '/mod/moodleforum/index.php?id=$1', 'course');

        $rules[] = new restore_decode_rule('moodleforumVIEWBYF', '/mod/moodleforum/view.php?f=$1', 'moodleforum');
        // Link to forum discussion.
        $rules[] = new restore_decode_rule('moodleforumDISCUSSIONVIEW',
            '/mod/moodleforum/discussion.php?d=$1',
            'moodleforum_discussion');
        // Link to discussion with parent and with anchor posts.
        $rules[] = new restore_decode_rule('moodleforumDISCUSSIONVIEWPARENT',
            '/mod/moodleforum/discussion.php?d=$1&parent=$2',
            array('moodleforum_discussion', 'moodleforum_post'));
        $rules[] = new restore_decode_rule('moodleforumDISCUSSIONVIEWINSIDE', '/mod/moodleforum/discussion.php?d=$1#$2',
            array('moodleforum_discussion', 'moodleforum_post'));

        return $rules;

    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * moodleforum logs. It must return one array
     * of {@link restore_log_rule} objects
     */
    static public function define_restore_log_rules() {
        $rules = array();

        $rules[] = new restore_log_rule('moodleforum', 'add',
            'view.php?id={course_module}', '{moodleforum}');
        $rules[] = new restore_log_rule('moodleforum', 'update',
            'view.php?id={course_module}', '{moodleforum}');
        $rules[] = new restore_log_rule('moodleforum', 'view',
            'view.php?id={course_module}', '{moodleforum}');
        $rules[] = new restore_log_rule('moodleforum', 'view moodleforum',
            'view.php?id={course_module}', '{moodleforum}');
        $rules[] = new restore_log_rule('moodleforum', 'mark read',
            'view.php?f={moodleforum}', '{moodleforum}');
        $rules[] = new restore_log_rule('moodleforum', 'start tracking',
            'view.php?f={moodleforum}', '{moodleforum}');
        $rules[] = new restore_log_rule('moodleforum', 'stop tracking',
            'view.php?f={moodloeoverflow}', '{moodleforum}');
        $rules[] = new restore_log_rule('moodleforum', 'subscribe',
            'view.php?f={moodleforum}', '{moodleforum}');
        $rules[] = new restore_log_rule('moodleforum', 'unsubscribe',
            'view.php?f={moodleforum}', '{moodleforum}');
        $rules[] = new restore_log_rule('moodleforum', 'subscriber',
            'subscribers.php?id={moodleforum}', '{moodleforum}');
        $rules[] = new restore_log_rule('moodleforum', 'subscribers',
            'subscribers.php?id={moodleforum}', '{moodleforum}');
        $rules[] = new restore_log_rule('moodleforum', 'view subscribers',
            'subscribers.php?id={moodleforum}', '{moodleforum}');
        $rules[] = new restore_log_rule('moodleforum', 'add discussion',
            'discussion.php?d={moodleforum_discussion}', '{moodleforum_discussion}');
        $rules[] = new restore_log_rule('moodleforum', 'view discussion',
            'discussion.php?d={moodleforum_discussion}', '{moodleforum_discussion}');
        $rules[] = new restore_log_rule('moodleforum', 'move discussion',
            'discussion.php?d={moodleforum_discussion}', '{moodleforum_discussion}');
        $rules[] = new restore_log_rule('moodleforum', 'delete discussi',
            'view.php?id={course_module}', '{moodleforum}',
            null, 'delete discussion');
        $rules[] = new restore_log_rule('moodleforum', 'delete discussion',
            'view.php?id={course_module}', '{moodleforum}');
        $rules[] = new restore_log_rule('moodleforum', 'add post',
            'discussion.php?d={moodleforum_discussion}&parent={moodleforum_post}', '{moodleforum_post}');
        $rules[] = new restore_log_rule('moodleforum', 'update post',
            'discussion.php?d={moodleforum_discussion}&parent={moodleforum_post}', '{moodleforum_post}');
        $rules[] = new restore_log_rule('moodleforum', 'prune post',
            'discussion.php?d={moodleforum_discussion}', '{moodleforum_post}');
        $rules[] = new restore_log_rule('moodleforum', 'delete post',
            'discussion.php?d={moodleforum_discussion}', '[post]');

        return $rules;
    }

    /**
     * Define the restore log rules that will be applied
     * by the {@link restore_logs_processor} when restoring
     * course logs. It must return one array
     * of {@link restore_log_rule} objects
     *
     * Note this rules are applied when restoring course logs
     * by the restore final task, but are defined here at
     * activity level. All them are rules not linked to any module instance (cmid = 0)
     */
    static public function define_restore_log_rules_for_course() {
        $rules = array();

        $rules[] = new restore_log_rule('moodleforum', 'view all', 'index.php?id={course}', null);

        return $rules;
    }
}
