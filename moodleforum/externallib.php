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
 * External moodleforum API
 *
 * @package    mod_moodleforum
 * @copyright  2017 Kennet Winter <k_wint10@uni-muenster.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");

/**
 * Class implementing the external API, esp. for AJAX functions.
 *
 * @package    mod_moodleforum
 * @copyright  2017 Kennet Winter <k_wint10@uni-muenster.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_moodleforum_external extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function record_vote_parameters() {
        return new external_function_parameters(
            array(
                'discussionid' => new external_value(PARAM_INT, 'id of discussion'),
                'postid'       => new external_value(PARAM_INT, 'id of post'),
                'ratingid'     => new external_value(PARAM_INT, 'rating'),
                'sesskey'      => new external_value(PARAM_TEXT, 'session key'),
            )
        );
    }

    /**
     * Returns the result of the vote (new rating and reputations).
     * @return external_multiple_structure
     */
    public static function record_vote_returns() {
        return new external_single_structure(
            array(
                'postrating'      => new external_value(PARAM_INT, 'new post rating'),
                'ownerreputation' => new external_value(PARAM_INT, 'new reputation of post owner'),
                'raterreputation' => new external_value(PARAM_INT, 'new reputation of rater'),
                'ownerid'         => new external_value(PARAM_INT, 'user id of post owner'),
            )
        );
    }

    /**
     * Records upvotes and downvotes.
     *
     * @param int $discussionid ID of discussion
     * @param int $postid ID of post
     * @param int $ratingid Rating value
     * @param int $sesskey Session key
     * @return array with updated information about rating /reputation
     */
    public static function record_vote($discussionid, $postid, $ratingid, $sesskey) {
        global $DB, $USER;

        // Parameter validation.
        $params = self::validate_parameters(self::record_vote_parameters(), array(
            'discussionid' => $discussionid,
            'postid'       => $postid,
            'ratingid'     => $ratingid,
            'sesskey'      => $sesskey,
        ));

        $transaction = $DB->start_delegated_transaction();

        // Check if the discussion is valid.
        if (!$discussion = $DB->get_record('moodleforum_discussions', array('id' => $params['discussionid']))) {
            print_error('invaliddiscussionid', 'moodleforum');
        }

        // Check if the related moodleforum instance is valid.
        if (!$moodleforum = $DB->get_record('moodleforum', array('id' => $discussion->moodleforum))) {
            print_error('invalidmoodleforumid', 'moodleforum');
        }

        // Check if the related moodleforum instance is valid.
        if (!$course = $DB->get_record('course', array('id' => $discussion->course))) {
            print_error('invalidcourseid');
        }

        // Get the related coursemodule and its context.
        if (!$cm = get_coursemodule_from_instance('moodleforum', $moodleforum->id, $course->id)) {
            print_error('invalidcoursemodule');
        }

        // Security checks.
        $context = context_module::instance($cm->id);
        self::validate_context($context);
        require_capability('mod/moodleforum:ratepost', $context);
        if (!confirm_sesskey($sesskey)) {
            print_error('invalidsesskey');
        }
        $postownerid = moodleforum_get_post_full($params['postid'])->userid;
        if ($postownerid == $USER->id) {
            print_error('rateownpost', 'moodleforum');
        }

        // Rate the post.
        if (!\mod_moodleforum\ratings::moodleforum_add_rating($moodleforum,
            $params['postid'], $params['ratingid'], $cm)) {
            print_error('ratingfailed', 'moodleforum');
        }
        $rating      = \mod_moodleforum\ratings::moodleforum_get_ratings_by_discussion($discussion->id,
            $params['postid']);
        $ownerrating = \mod_moodleforum\ratings::moodleforum_get_reputation($moodleforum->id, $postownerid);
        $raterrating = \mod_moodleforum\ratings::moodleforum_get_reputation($moodleforum->id, $USER->id);

        $params['postrating']      = $rating->upvotes - $rating->downvotes;
        $params['ownerreputation'] = $ownerrating;
        $params['raterreputation'] = $raterrating;
        $params['ownerid']         = $postownerid;

        $transaction->allow_commit();

        return $params;
    }
}
