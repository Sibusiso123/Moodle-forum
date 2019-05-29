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
 * Define all the backup steps that will be used by the backup_moodleforum_activity_task
 *
 * @package   mod_moodleforum
 * @category  backup
 * @copyright 2017 Kennet Winter <k_wint10@uni-muenster.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Define the complete moodleforum structure for backup, with file and id annotations
 *
 * @package   mod_moodleforum
 * @category  backup
 * @copyright 2018 Tamara Gunkel
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_moodleforum_activity_structure_step extends backup_activity_structure_step {

    /**
     * Defines the backup structure of the module
     *
     * @return backup_nested_element
     */
    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define the root element describing the moodleforum instance.
        $moodleforum = new backup_nested_element('moodleforum', array('id'), array(
            'name', 'intro', 'introformat', 'maxbytes', 'maxattachments',
            'forcesubscribe', 'trackingtype', 'timecreated', 'timemodified',
            'ratingpreference', 'coursewidereputation', 'allownegativereputation'));

        // Define each element separated.
        $discussions = new backup_nested_element('discussions');
        $discussion  = new backup_nested_element('discussion', array('id'), array(
            'name', 'firstpost', 'userid', 'timemodified', 'usermodified', 'timestart'));

        $posts = new backup_nested_element('posts');

        $post = new backup_nested_element('post', array('id'), array(
            'parent', 'userid', 'created', 'modified',
            'mailed', 'message', 'messageformat', 'attachment'));

        $ratings = new backup_nested_element('ratings');

        $rating = new backup_nested_element('rating', array('id'), array(
            'userid', 'rating', 'firstrated', 'lastchanged'));

        $discussionsubs = new backup_nested_element('discuss_subs');

        $discussionsub = new backup_nested_element('discuss_sub', array('id'), array(
            'userid',
            'preference',
        ));

        $subscriptions = new backup_nested_element('subscriptions');

        $subscription = new backup_nested_element('subscription', array('id'), array(
            'userid'));

        $readposts = new backup_nested_element('readposts');

        $read = new backup_nested_element('read', array('id'), array(
            'userid', 'discussionid', 'postid', 'firstread',
            'lastread'));

        $tracking = new backup_nested_element('tracking');

        $track = new backup_nested_element('track', array('id'), array(
            'userid'));

        // Build the tree.
        $moodleforum->add_child($discussions);
        $discussions->add_child($discussion);

        $discussion->add_child($posts);
        $posts->add_child($post);

        $post->add_child($ratings);
        $ratings->add_child($rating);

        $discussion->add_child($discussionsubs);
        $discussionsubs->add_child($discussionsub);

        $moodleforum->add_child($subscriptions);
        $subscriptions->add_child($subscription);

        $moodleforum->add_child($readposts);
        $readposts->add_child($read);

        $moodleforum->add_child($tracking);
        $tracking->add_child($track);

        // Define data sources.
        $moodleforum->set_source_table('moodleforum', array('id' => backup::VAR_ACTIVITYID));

        // All these source definitions only happen if we are including user info.
        if ($userinfo) {
            $discussion->set_source_sql('
                SELECT *
                  FROM {moodleforum_discussions}
                 WHERE moodleforum = ?',
                array(backup::VAR_PARENTID));

            // Need posts ordered by id so parents are always before childs on restore.
            $post->set_source_table('moodleforum_posts', array('discussion' => backup::VAR_PARENTID), 'id ASC');
            $rating->set_source_table('moodleforum_ratings', array('postid' => backup::VAR_PARENTID));
            $discussionsub->set_source_table('moodleforum_discuss_subs', array('discussion' => backup::VAR_PARENTID));
            $subscription->set_source_table('moodleforum_subscriptions', array('moodleforum' => backup::VAR_PARENTID));
            $read->set_source_table('moodleforum_read', array('moodleforumid' => backup::VAR_PARENTID));
            $track->set_source_table('moodleforum_tracking', array('moodleforumid' => backup::VAR_PARENTID));
        }

        // Define id annotations.
        $post->annotate_ids('user', 'userid');
        $subscription->annotate_ids('user', 'userid');
        $read->annotate_ids('user', 'userid');
        $rating->annotate_ids('user', 'userid');
        $track->annotate_ids('user', 'userid');

        // Define file annotations (we do not use itemid in this example).
        $moodleforum->annotate_files('mod_moodleforum', 'intro', null);
        $post->annotate_files('mod_moodleforum', 'post', 'id');
        $post->annotate_files('mod_moodleforum', 'attachment', 'id');

        // Return the root element (moodleforum), wrapped into standard activity structure.
        return $this->prepare_activity_structure($moodleforum);
    }
}
