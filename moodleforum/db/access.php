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
 * Capability definitions for the moodleforum module
 *
 * The capabilities are loaded into the database table when the module is
 * installed or updated. Whenever the capability definitions are updated,
 * the module version number should be bumped up.
 *
 * The system has four possible values for a capability:
 * CAP_ALLOW, CAP_PREVENT, CAP_PROHIBIT, and inherit (not set).
 *
 * It is important that capability names are unique. The naming convention
 * for capabilities that are specific to modules and blocks is as follows:
 *   [mod/block]/<plugin_name>:<capabilityname>
 *
 * component_name should be the same as the directory name of the mod or block.
 *
 * Core moodle capabilities are defined thus:
 *    moodle/<capabilityclass>:<capabilityname>
 *
 * Examples: mod/forum:viewpost
 *           block/recent_activity:view
 *           moodle/site:deleteuser
 *
 * The variable name for the capability definitions array is $capabilities
 *
 * @package   mod_moodleforum
 * @copyright 2017 Kennet Winter <k_wint10@uni-muenster.de>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// Modify capabilities as needed and remove this comment.
$capabilities = array(
    'mod/moodleforum:addinstance' => array(
        'riskbitmask'          => RISK_XSS,
        'captype'              => 'write',
        'contextlevel'         => CONTEXT_COURSE,
        'archetypes'           => array(
            'editingteacher' => CAP_ALLOW,
            'manager'        => CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'moodle/course:manageactivities'
    ),

    'mod/moodleforum:view' => array(
        'captype'      => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes'   => array(
            'guest' => CAP_ALLOW,
            'user'  => CAP_ALLOW,
        )
    ),

    'mod/moodleforum:viewdiscussion' => array(
        'captype'              => 'read',
        'contextlevel'         => CONTEXT_MODULE,
        'archetypes'           => array(
            'frontpage'      => CAP_ALLOW,
            'guest'          => CAP_ALLOW,
            'student'        => CAP_ALLOW,
            'teacher'        => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager'        => CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'mod/forum:viewdiscussion'
    ),

    'mod/moodleforum:replypost' => array(

        'riskbitmask' => RISK_SPAM,

        'captype'              => 'write',
        'contextlevel'         => CONTEXT_MODULE,
        'archetypes'           => array(
            'student'        => CAP_ALLOW,
            'teacher'        => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager'        => CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'mod/forum:replypost'
    ),

    'mod/moodleforum:startdiscussion' => array(

        'riskbitmask' => RISK_SPAM,

        'captype'              => 'write',
        'contextlevel'         => CONTEXT_MODULE,
        'archetypes'           => array(
            'student'        => CAP_ALLOW,
            'teacher'        => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager'        => CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'mod/forum:startdiscussion'
    ),

    'mod/moodleforum:editanypost' => array(

        'riskbitmask' => RISK_SPAM,

        'captype'              => 'write',
        'contextlevel'         => CONTEXT_MODULE,
        'archetypes'           => array(
            'teacher'        => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager'        => CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'mod/forum:editanypost'
    ),

    'mod/moodleforum:deleteownpost' => array(

        'captype'              => 'read',
        'contextlevel'         => CONTEXT_MODULE,
        'archetypes'           => array(
            'student'        => CAP_ALLOW,
            'teacher'        => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager'        => CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'mod/forum:deleteownpost'
    ),

    'mod/moodleforum:deleteanypost' => array(

        'captype'              => 'read',
        'contextlevel'         => CONTEXT_MODULE,
        'archetypes'           => array(
            'teacher'        => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager'        => CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'mod/forum:deleteanypost'
    ),

    'mod/moodleforum:viewanyrating' => array(

        'riskbitmask'          => RISK_PERSONAL,
        'captype'              => 'read',
        'contextlevel'         => CONTEXT_MODULE,
        'archetypes'           => array(
            'teacher'        => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager'        => CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'mod/forum:viewanyrating'
    ),

    'mod/moodleforum:ratepost' => array(

        'riskbitmask' => RISK_SPAM,

        'captype'      => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes'   => array(
            'student'        => CAP_ALLOW,
            'teacher'        => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager'        => CAP_ALLOW
        )
    ),

    'mod/moodleforum:marksolved' => array(

        'riskbitmask' => RISK_SPAM,

        'captype'      => 'write',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes'   => array(
            'student'        => CAP_PROHIBIT,
            'teacher'        => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager'        => CAP_ALLOW
        )
    ),

    'mod/moodleforum:managesubscriptions' => array(

        'riskbitmask' => RISK_SPAM,

        'captype'              => 'read',
        'contextlevel'         => CONTEXT_MODULE,
        'archetypes'           => array(
            'teacher'        => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager'        => CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'mod/forum:managesubscriptions'
    ),

    'mod/moodleforum:allowforcesubscribe' => array(

        'captype'              => 'read',
        'contextlevel'         => CONTEXT_MODULE,
        'archetypes'           => array(
            'student'        => CAP_ALLOW,
            'teacher'        => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'frontpage'      => CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'mod/forum:allowforcesubscribe'
    ),

    'mod/moodleforum:createattachment' => array(

        'riskbitmask' => RISK_SPAM,

        'captype'              => 'write',
        'contextlevel'         => CONTEXT_MODULE,
        'archetypes'           => array(
            'student'        => CAP_ALLOW,
            'teacher'        => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager'        => CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'mod/forum:createattachment'
    ),
);
