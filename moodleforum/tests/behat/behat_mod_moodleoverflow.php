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
 * Steps definitions related with the moodleforum activity.
 *
 * @package    mod_moodleforum
 * @category   test
 * @copyright  2017 KennetWinter
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// NOTE: no MOODLE_INTERNAL test here, this file may be required by behat before including /config.php.

require_once(__DIR__ . '/../../../../lib/behat/behat_base.php');

use Behat\Gherkin\Node\TableNode as TableNode;

/**
 * moodleforum-related steps definitions.
 *
 * @package    mod_moodleforum
 * @category   test
 * @copyright  2017 KennetWinter
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class behat_mod_moodleforum extends behat_base {

    /**
     * Adds a topic to the moodleforum specified by it's name. Useful for the Announcements and blog-style moodleforum.
     *
     * @Given /^I add a new topic to "(?P<moodleforum_name_string>(?:[^"]|\\")*)" moodleforum with:$/
     * @param string    $moodleforumname
     * @param TableNode $table
     */
    public function i_add_a_new_topic_to_moodleforum_with($moodleforumname, TableNode $table) {
        $this->add_new_discussion($moodleforumname, $table, get_string('addanewtopic', 'moodleforum'));
    }

    /**
     * Adds a discussion to the moodleforum specified by it's name with the provided table data
     * (usually Subject and Message). The step begins from the moodleforum's course page.
     *
     * @Given /^I add a new discussion to "(?P<moodleforum_name_string>(?:[^"]|\\")*)" moodleforum with:$/
     * @param string    $moodleforumname
     * @param TableNode $table
     */
    public function i_add_a_moodleforum_discussion_to_moodleforum_with($moodleforumname, TableNode $table) {
        $this->add_new_discussion($moodleforumname, $table, get_string('addanewdiscussion', 'moodleforum'));
    }

    /**
     * Adds a reply to the specified post of the specified moodleforum.
     * The step begins from the moodleforum's page or from the moodleforum's course page.
     *
     * @Given /^I reply "(?P<post_subject_string>(?:[^"]|\\")*)" post
     * from "(?P<moodleforum_name_string>(?:[^"]|\\")*)" moodleforum with:$/
     *
     * @param string    $postsubject        The subject of the post
     * @param string    $moodleforumname The moodleforum name
     * @param TableNode $table
     */
    public function i_reply_post_from_moodleforum_with($postsubject, $moodleforumname, TableNode $table) {

        // Navigate to moodleforum.
        $this->execute('behat_general::click_link', $this->escape($moodleforumname));
        $this->execute('behat_general::click_link', $this->escape($postsubject));
        $this->execute('behat_general::click_link', get_string('reply', 'moodleforum'));

        // Fill form and post.
        $this->execute('behat_forms::i_set_the_following_fields_to_these_values', $table);

        $this->execute('behat_forms::press_button', get_string('posttomoodleforum', 'moodleforum'));
        $this->execute('behat_general::i_wait_to_be_redirected');
    }

    /**
     * Returns the steps list to add a new discussion to a moodleforum.
     *
     * Abstracts add a new topic and add a new discussion, as depending
     * on the moodleforum type the button string changes.
     *
     * @param string    $moodleforumname
     * @param TableNode $table
     * @param string    $buttonstr
     */
    protected function add_new_discussion($moodleforumname, TableNode $table, $buttonstr) {

        // Navigate to moodleforum.
        $this->execute('behat_general::click_link', $this->escape($moodleforumname));
        $this->execute('behat_forms::press_button', $buttonstr);

        // Fill form and post.
        $this->execute('behat_forms::i_set_the_following_fields_to_these_values', $table);
        $this->execute('behat_forms::press_button', get_string('posttomoodleforum', 'moodleforum'));
        $this->execute('behat_general::i_wait_to_be_redirected');
    }
}
