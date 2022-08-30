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
 * Event observers of local_o365 plugin.
 *
 * @package local_o365
 * @author James McQuillan <james.mcquillan@remote-learner.net>
 * @author Lai Wei <lai.wei@enovation.ie>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright (C) 2014 onwards Microsoft, Inc. (http://microsoft.com/)
 */

namespace local_o365;

use auth_oidc\event\user_authed;
use auth_oidc\event\user_connected;
use auth_oidc\event\user_disconnected;
use auth_oidc\event\user_loggedin;
use auth_oidc\jwt;
use backup;
use context;
use context_course;
use context_system;
use core\event\capability_assigned;
use core\event\capability_unassigned;
use core\event\config_log_created;
use core\event\course_created;
use core\event\course_deleted;
use core\event\course_restored;
use core\event\course_updated;
use core\event\enrol_instance_updated;
use core\event\notification_sent;
use core\event\role_assigned;
use core\event\role_capabilities_updated;
use core\event\role_deleted;
use core\event\role_unassigned;
use core\event\user_created;
use core\event\user_deleted;
use core\event\user_enrolment_created;
use core\event\user_enrolment_deleted;
use core\event\user_enrolment_updated;
use core\task\manager;
use core_user;
use Exception;
use local_o365\feature\coursesync\main;
use local_o365\oauth2\clientdata;
use local_o365\oauth2\token;
use local_o365\obj\o365user;
use local_o365\rest\azuread;
use local_o365\rest\botframework;
use local_o365\rest\discovery;
use local_o365\rest\sharepoint;
use local_o365\rest\unified;
use local_o365\task\groupmembershipsync;
use local_o365\task\sharepointaccesssync;
use moodle_url;

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot.'/lib/filelib.php');

/**
 * Handles events.
 */
class observers {
    /**
     * Handle an authentication-only OIDC event.
     *
     * Does the following:
     *  - Set the system API user, so store the received token appropriately.
     *
     * @param user_authed $event The triggered event.
     * @return bool Success/Failure.
     */
    public static function handle_oidc_user_authed(user_authed $event) : bool {
        require_login();
        require_capability('moodle/site:config', context_system::instance());

        $eventdata = $event->get_data();

        $action = (!empty($eventdata['other']['statedata']['action'])) ? $eventdata['other']['statedata']['action'] : null;

        switch ($action) {
            case 'setsystemapiuser':
                $tokendata = [
                    'idtoken' => $eventdata['other']['tokenparams']['id_token'],
                    $eventdata['other']['tokenparams']['resource'] => [
                        'token' => $eventdata['other']['tokenparams']['access_token'],
                        'scope' => $eventdata['other']['tokenparams']['scope'],
                        'refreshtoken' => $eventdata['other']['tokenparams']['refresh_token'],
                        'tokenresource' => $eventdata['other']['tokenparams']['resource'],
                        'expiry' => $eventdata['other']['tokenparams']['expires_on'],
                    ]
                ];

                set_config('systemtokens', serialize($tokendata), 'local_o365');
                set_config('sharepoint_initialized', '0', 'local_o365');
                redirect(new moodle_url('/admin/settings.php?section=local_o365'));
                break;

            case 'adminconsent':
                // Get tenant if using app-only access.
                if (utils::is_enabled_apponlyaccess() === true) {
                    if (isset($eventdata['other']['tokenparams']['id_token'])) {
                        $idtoken = $eventdata['other']['tokenparams']['id_token'];
                        $idtoken = jwt::instance_from_encoded($idtoken);
                        if (!empty($idtoken)) {
                            $tenant = utils::get_tenant_from_idtoken($idtoken);
                            if (!empty($tenant)) {
                                set_config('aadtenantid', $tenant, 'local_o365');
                            }
                        }
                    }
                }
                redirect(new moodle_url('/admin/settings.php?section=local_o365'));
                break;

            case 'addtenant':
                $clientdata = clientdata::instance_from_oidc();
                $httpclient = new httpclient();
                $token = $eventdata['other']['tokenparams']['access_token'];
                $expiry = $eventdata['other']['tokenparams']['expires_on'];
                $rtoken = $eventdata['other']['tokenparams']['refresh_token'];
                $scope = $eventdata['other']['tokenparams']['scope'];
                $res = $eventdata['other']['tokenparams']['resource'];
                $token = new token($token, $expiry, $rtoken, $scope, $res, null, $clientdata, $httpclient);
                if (unified::is_enabled() === true) {
                    $tokenresource = unified::get_tokenresource();
                } else {
                    $tokenresource = discovery::get_tokenresource();
                }
                $token = token::jump_tokenresource($token, $tokenresource, $clientdata, $httpclient);
                if (unified::is_enabled() === true) {
                    $apiclient = new unified($token, $httpclient);
                } else {
                    $apiclient = new discovery($token, $httpclient);
                }
                $domainsfetched = false;
                $domainnames = [];
                try {
                    $domainnames = $apiclient->get_all_domain_names_in_tenant();
                    if ($domainnames) {
                        $domainsfetched = true;

                    }
                } catch (Exception $e) {
                    // Do nothing.
                }

                if (!$domainsfetched) {
                    $domainnames[] = $apiclient->get_default_domain_name_in_tenant();
                }

                $idtoken = jwt::instance_from_encoded($token->get_token());
                $tenantid = utils::get_tenant_from_idtoken($idtoken);
                utils::enableadditionaltenant($tenantid, $domainnames);

                redirect(new moodle_url('/local/o365/acp.php', ['mode' => 'tenants']));
                break;

            default:
                return true;
        }

        return false;
    }

    /**
     * Handle an existing Moodle user connecting to OpenID Connect.
     *
     * @param user_connected $event The triggered event.
     * @return bool Success/Failure.
     */
    public static function handle_oidc_user_connected(user_connected $event) : bool {
        global $DB;

        if (utils::is_connected() !== true) {
            return false;
        }

        // Get additional tokens for the user.
        $eventdata = $event->get_data();
        if (!empty($eventdata['userid'])) {
            try {
                $userid = $eventdata['userid'];
                // Create local_o365_objects record.
                if (!empty($eventdata['other']['oidcuniqid'])) {
                    $userobject = $DB->get_record('local_o365_objects', ['type' => 'user', 'moodleid' => $userid]);
                    $userrecord = core_user::get_user($userid);
                    $isguestuser = false;
                    if (stripos($userrecord->username, '_ext_') !== false) {
                        $isguestuser = true;
                    }
                    if (empty($userobject)) {
                        try {
                            $apiclient = utils::get_api();
                            $userdata = $apiclient->get_user($eventdata['other']['oidcuniqid'], $isguestuser);
                        } catch (Exception $e) {
                            utils::debug('Exception: '.$e->getMessage(), __METHOD__, $e);
                            return true;
                        }

                        $tenant = utils::get_tenant_for_user($eventdata['userid']);
                        $metadata = '';
                        if (!empty($tenant)) {
                            // Additional tenant - get ODB url.
                            $odburl = utils::get_odburl_for_user($eventdata['userid']);
                            if (!empty($odburl)) {
                                $metadata = json_encode(['odburl' => $odburl]);
                            }
                        }

                        // Create userobject if it does not exist.
                        $now = time();
                        $userobjectdata = (object)[
                            'type' => 'user',
                            'subtype' => '',
                            'objectid' => $userdata['objectId'],
                            'o365name' => $userdata['userPrincipalName'],
                            'moodleid' => $userid,
                            'tenant' => $tenant,
                            'metadata' => $metadata,
                            'timecreated' => $now,
                            'timemodified' => $now,
                        ];
                        $userobjectdata->id = $DB->insert_record('local_o365_objects', $userobjectdata);

                        // Enrol user to all courses he was enrolled prior to connecting.
                        if ($userobjectdata->id && \local_o365\feature\coursesync\utils::is_enabled() === true) {
                            $courses = enrol_get_users_courses($userid, true);

                            foreach ($courses as $courseid => $course) {
                                if (\local_o365\feature\coursesync\utils::is_course_sync_enabled($courseid) == true) {
                                    \local_o365\feature\coursesync\utils::sync_user_role_in_course_group($userid, $courseid,
                                        $userobjectdata->id);
                                }
                            }
                        }
                    }
                } else {
                    utils::debug('no oidcuniqid received', __METHOD__, $eventdata);
                }

                return true;
            } catch (Exception $e) {
                utils::debug($e->getMessage(), __METHOD__, $e);
                return false;
            }
        }
        return false;
    }

    /**
     * Handle a user being created.
     *
     * Does the following:
     *  - Check if user is using OpenID Connect auth plugin.
     *  - If so, gets additional information from Azure AD and updates the user.
     *
     * @param user_created $event The triggered event.
     * @return bool Success/Failure.
     */
    public static function handle_user_created(user_created $event) : bool {
        global $DB;

        if (utils::is_connected() !== true) {
            return false;
        }

        $eventdata = $event->get_data();

        if (empty($eventdata['objectid'])) {
            return false;
        }
        $createduserid = $eventdata['objectid'];

        $user = $DB->get_record('user', ['id' => $createduserid]);
        if (!empty($user) && isset($user->auth) && $user->auth === 'oidc') {
            static::get_additional_user_info($createduserid);
        }

        return true;
    }

    /**
     * Handle an existing Moodle user disconnecting from OpenID Connect.
     *
     * @param user_disconnected $event The triggered event.
     * @return bool Success/Failure.
     */
    public static function handle_oidc_user_disconnected(user_disconnected $event) : bool {
        global $DB;

        $eventdata = $event->get_data();
        if (!empty($eventdata['userid'])) {
            $DB->delete_records('local_o365_token', ['user_id' => $eventdata['userid']]);
            $DB->delete_records('local_o365_objects', ['type' => 'user', 'moodleid' => $eventdata['userid']]);
            $DB->delete_records('local_o365_connections', ['muserid' => $eventdata['userid']]);
            $DB->delete_records('local_o365_appassign', ['muserid' => $eventdata['userid']]);
        }

        return true;
    }

    /**
     * Handle user logins.
     *
     * Does the following:
     *  - If the user uses auth_oidc, uses the received auth code to get tokens for the other resources we use.
     *  - If the user is connected to Microsoft 365, sync user profiles.
     *
     * @param user_loggedin $event The triggered event.
     * @return bool Success/Failure.
     */
    public static function handle_oidc_user_loggedin(user_loggedin $event) : bool {
        if (utils::is_connected() !== true) {
            return false;
        }

        // Get additional tokens for the user.
        $eventdata = $event->get_data();
        if (!empty($eventdata['other']['username']) && !empty($eventdata['userid'])) {
            static::get_additional_user_info($eventdata['userid']);
        }

        return true;
    }

    /**
     * Get additional information about a user from Azure AD.
     *
     * @param int $userid The ID of the user we want more information about..
     * @return bool Success/Failure.
     */
    public static function get_additional_user_info(int $userid) : bool {
        global $DB;

        try {
            // Azure AD or Graph API must be configured for us to fetch data.
            if (azuread::is_configured() !== true && unified::is_configured() !== true) {
                return true;
            }

            $o365user = o365user::instance_from_muserid($userid);
            if (empty($o365user)) {
                // No OIDC token for this user and resource - maybe not an Azure AD user.
                return false;
            }

            if (!$existinguserdata = core_user::get_user($userid)) {
                // Moodle user doesn't exist, nothing to do.
                return false;
            }

            $userobject = $DB->get_record('local_o365_objects', ['type' => 'user', 'moodleid' => $userid]);

            if (empty($userobject)) {
                // Skip field mapping if the user uses auth_oidc, and matching record is stored in local_o365_objects table.
                if ($existinguserdata->auth != 'oidc') {
                    // Create o365_object record if it does not exist.
                    $tenant = utils::get_tenant_for_user($userid);
                    $metadata = '';
                    if (!empty($tenant)) {
                        // Additional tenant - get ODB url.
                        $odburl = utils::get_odburl_for_user($userid);
                        if (!empty($odburl)) {
                            $metadata = json_encode(['odburl' => $odburl]);
                        }
                    }
                    $now = time();
                    $userobjectdata = (object)[
                        'type' => 'user',
                        'subtype' => '',
                        'objectid' => $o365user->objectid,
                        'o365name' => str_replace('#ext#', '#EXT#', $o365user->upn),
                        'moodleid' => $userid,
                        'tenant' => $tenant,
                        'metadata' => $metadata,
                        'timecreated' => $now,
                        'timemodified' => $now,
                    ];
                    $userobjectdata->id = $DB->insert_record('local_o365_objects', $userobjectdata);
                }
            }

            // Sync profile photo and timezone.
            $aadsync = get_config('local_o365', 'aadsync');
            $aadsync = array_flip(explode(',', $aadsync));
            $usersync = new feature\usersync\main();
            if (isset($aadsync['photosynconlogin'])) {
                $usersync->assign_photo($userid);
            }
            if (isset($aadsync['tzsynconlogin'])) {
                $usersync->sync_timezone($userid);
            }

            return true;
        } catch (Exception $e) {
            utils::debug($e->getMessage(), __METHOD__, $e);
        }
        return false;
    }

    /**
     * Handle user_enrolment_updated event.
     *
     * Does the following:
     *  - remove user from Microsoft Teams when they are suspended but still enrolled.
     *  - add user to Microsoft Teams when they are unsuspended.
     *
     * @param user_enrolment_updated $event The triggered event.
     * @return bool Success/Failure.
     */
    public static function handle_user_enrolment_updated(user_enrolment_updated $event) : bool {
        if (utils::is_connected() !== true || \local_o365\feature\coursesync\utils::is_enabled() !== true ||
            \local_o365\feature\coursesync\utils::is_course_sync_enabled($event->courseid) !== true) {
            return false;
        }

        return \local_o365\feature\coursesync\utils::sync_user_role_in_course_group($event->relateduserid, $event->courseid);
    }

    /**
     * Handle enrol_instance_updated event.
     *
     * Does the following:
     *  - check all users enrolled using the updated enrolment method, and update ownership/membership in the Microsoft 365 group
     * connected to the Moodle course.
     *
     * @param enrol_instance_updated $event
     * @return bool
     */
    public static function handle_enrol_instance_updated(enrol_instance_updated $event) : bool {
        global $DB;

        $courseid = $event->courseid;

        if (empty($courseid)) {
            return false;
        }

        if (utils::is_connected() !== true || \local_o365\feature\coursesync\utils::is_enabled() !== true ||
            \local_o365\feature\coursesync\utils::is_course_sync_enabled($courseid) !== true) {
            return false;
        }

        // Ensure course is connected.
        $coursegroupobjectrecordid = \local_o365\feature\coursesync\utils::get_group_object_record_id_by_course_id($courseid);
        if (!$coursegroupobjectrecordid) {
            return false;
        }

        // If the course is an SDS course and the SDS enrolment sync option is off, don't update enrolment.
        if ($DB->record_exists('local_o365_objects', ['type' => 'sdssection', 'moodleid' => $courseid])) {
            // SDS course.
            if (!get_config('local_o365', 'sdsenrolmentenabled') || !get_config('local_o365', 'sdssyncenrolmenttosds')) {
                return false;
            }
        }

        $userenrolments = $DB->get_records('user_enrolments', ['enrolid' => $event->objectid]);

        foreach ($userenrolments as $userenrolment) {
            \local_o365\feature\coursesync\utils::sync_user_role_in_course_group($userenrolment->userid, $courseid, 0,
                $coursegroupobjectrecordid, true);
        }

        return true;
    }

    /**
     * Construct a sharepoint API client using the system API user.
     *
     * @return sharepoint|bool A constructed sharepoint API client, or false if error.
     */
    public static function construct_sharepoint_api_with_system_user() {
        try {
            $sharepointtokenresource = sharepoint::get_tokenresource();
            if (!empty($sharepointtokenresource)) {
                $httpclient = new httpclient();
                $clientdata = clientdata::instance_from_oidc();
                $sharepointtoken = utils::get_app_or_system_token($sharepointtokenresource, $clientdata, $httpclient);
                if (!empty($sharepointtoken)) {
                    return new sharepoint($sharepointtoken, $httpclient);
                }
            }
        } catch (Exception $e) {
            utils::debug($e->getMessage(), __METHOD__, $e);
        }
        return false;
    }

    /**
     * Handle course_created event.
     *
     * Does the following:
     *  - enable sync on new courses if course sync is "custom", and the option to enable sync on new courses by default is set.
     *  - create a sharepoint site and associated groups.
     *
     * @param course_created $event The triggered event.
     * @return bool Success/Failure.
     */
    public static function handle_course_created(course_created $event) : bool {
        if (utils::is_connected() !== true) {
            return false;
        }

        // Enable team sync for newly created courses if the create teams setting is "custom", and the option to enable sync on
        // new courses by default is on.
        $syncnewcoursesetting = get_config('local_o365', 'sync_new_course');
        if ((get_config('local_o365', 'coursesync') === 'oncustom') && $syncnewcoursesetting) {
            \local_o365\feature\coursesync\utils::set_course_sync_enabled($event->objectid, true);
        }

        if (sharepoint::is_configured() === true) {
            $sharepoint = static::construct_sharepoint_api_with_system_user();
            if (!empty($sharepoint)) {
                $sharepoint->create_course_site($event->objectid);
            }
        }

        return true;
    }

    /**
     * Handle course_restored event.
     *
     * Does the following:
     *  - enable sync on new courses if course sync is "custom", and the option to enable sync on new courses by default is set.
     *
     * @param course_restored $event
     *
     * @return bool
     */
    public static function handle_course_restored(course_restored $event) : bool {
        if (utils::is_connected() !== true) {
            return false;
        }

        $eventdata = $event->get_data();

        // Enable team sync for newly restored courses if the create teams setting is "custom", and the option to enable sync on
        // new courses by default is on.
        $syncnewcoursesetting = get_config('local_o365', 'sync_new_course');
        if ((get_config('local_o365', 'coursesync') === 'oncustom') && $syncnewcoursesetting) {
            if (isset($eventdata['other']) && isset($eventdata['other']['target']) &&
                $eventdata['other']['target'] == backup::TARGET_NEW_COURSE) {
                \local_o365\feature\coursesync\utils::set_course_sync_enabled($event->objectid, true);
            }
        }

        return true;
    }

    /**
     * Handle course_updated event.
     *
     * Does the following:
     *  - update Teams name, if the options are enabled.
     *  - update associated sharepoint sites.
     *
     * @param course_updated $event The triggered event.
     * @return bool Success/Failure.
     */
    public static function handle_course_updated(course_updated $event) : bool {
        if (utils::is_connected() !== true) {
            return false;
        }
        $courseid = $event->objectid;
        $eventdata = $event->get_data();
        if (!empty($eventdata['other'])) {
            // Update Teams names.
            $teamsyncenabled = get_config('local_o365', 'team_name_sync');

            if ($teamsyncenabled && \local_o365\feature\coursesync\utils::is_enabled() === true) {
                $apiclient = \local_o365\feature\coursesync\utils::get_graphclient();
                $coursesycnmain = new main($apiclient, true);

                if (\local_o365\feature\coursesync\utils::is_course_sync_enabled($courseid)) {
                    $coursesycnmain->update_team_name($courseid);
                }
            }

            // Update sharepoint sites.
            $shortname = $eventdata['other']['shortname'];
            $fullname = $eventdata['other']['fullname'];
            if (sharepoint::is_configured() === true) {
                $sharepoint = static::construct_sharepoint_api_with_system_user();
                if (!empty($sharepoint)) {
                    $sharepoint->update_course_site($courseid, $shortname, $fullname);
                }
            }
        }

        return true;
    }

    /**
     * Handle course_deleted event.
     *
     * Does the following:
     *  - delete course connection records.
     *  - delete SDS connection records.
     *  - delete connect group if the option is enabled.
     *  - delete sharepoint sites and groups, and local sharepoint site data.
     *
     * @param course_deleted $event The triggered event.
     * @return bool Success/Failure.
     */
    public static function handle_course_deleted(course_deleted $event) : bool {
        global $DB;

        if (utils::is_connected() !== true) {
            return false;
        }
        $courseid = $event->objectid;

        // Delete SDS section record, or delete connected group.
        if ($DB->record_exists('local_o365_objects', ['type' => 'sdssection', 'moodleid' => $courseid])) {
            $DB->delete_records('local_o365_objects', ['type' => 'sdssection', 'moodleid' => $courseid]);
        } else {
            if (get_config('local_o365', 'delete_group_on_course_deletion')) {
                \local_o365\feature\coursesync\utils::delete_microsoft_365_group($courseid);
            }
        }

        // Delete group mapping records.
        $DB->delete_records('local_o365_objects', ['type' => 'group', 'subtype' => 'course', 'moodleid' => $courseid]);
        $DB->delete_records('local_o365_objects', ['type' => 'group', 'subtype' => 'courseteam', 'moodleid' => $courseid]);
        $DB->delete_records('local_o365_objects', ['type' => 'group', 'subtype' => 'teamfromgroup', 'moodleid' => $courseid]);

        if (sharepoint::is_configured() !== true) {
            return false;
        }

        $sharepoint = static::construct_sharepoint_api_with_system_user();
        if (!empty($sharepoint)) {
            $sharepoint->delete_course_site($courseid);
        }
        return true;
    }

    /**
     * Sync SharePoint course site access when a role was assigned or unassigned for a user.
     *
     * @param int $roleid The ID of the role that was assigned/unassigned.
     * @param int $userid The ID of the user that it was assigned to or unassigned from.
     * @param int $contextid The ID of the context the role was assigned/unassigned in.
     * @return bool Success/Failure.
     */
    public static function sync_spsite_access_for_roleassign_change(int $roleid, int $userid, int $contextid) : bool {
        global $DB;
        $requiredcap = sharepoint::get_course_site_required_capability();

        // Check if the role affected the required capability.
        $rolecapsql = "SELECT *
                         FROM {role_capabilities}
                        WHERE roleid = ? AND capability = ?";
        $capassignrec = $DB->get_record_sql($rolecapsql, [$roleid, $requiredcap]);

        if (empty($capassignrec) || $capassignrec->permission == CAP_INHERIT) {
            // Role doesn't affect required capability. Doesn't concern us.
            return false;
        }

        $context = context::instance_by_id($contextid, IGNORE_MISSING);
        if (empty($context)) {
            // Invalid context, stop here.
            return false;
        }

        if ($context->contextlevel == CONTEXT_COURSE) {
            $courseid = $context->instanceid;
            $user = $DB->get_record('user', ['id' => $userid]);
            if (empty($user)) {
                // Bad userid.
                return false;
            }

            if (unified::is_configured()) {
                $userupn = unified::get_muser_upn($user);
            } else {
                $userupn = azuread::get_muser_upn($user);
            }
            if (empty($userupn)) {
                // No user UPN, can't continue.
                return false;
            }

            $spgroupsql = 'SELECT *
                             FROM {local_o365_coursespsite} site
                             JOIN {local_o365_spgroupdata} grp ON grp.coursespsiteid = site.id
                            WHERE site.courseid = ? AND grp.permtype = ?';
            $spgrouprec = $DB->get_record_sql($spgroupsql, [$courseid, 'contribute']);
            if (empty($spgrouprec)) {
                // No sharepoint group, can't fix that here.
                return false;
            }

            // If the context is a course context we can change SP access now.
            $sharepoint = static::construct_sharepoint_api_with_system_user();
            if (empty($sharepoint)) {
                // O365 not configured.
                return false;
            }
            $hascap = has_capability($requiredcap, $context, $user);
            if ($hascap === true) {
                // Add to group.
                $sharepoint->add_user_to_group($userupn, $spgrouprec->groupid, $user->id);
            } else {
                // Remove from group.
                $sharepoint->remove_user_from_group($userupn, $spgrouprec->groupid, $user->id);
            }
            return true;
        } else if ($context->get_course_context(false) == false) {
            // If the context is higher than a course, we have to run a sync in cron.
            $spaccesssync = new sharepointaccesssync();
            $spaccesssync->set_custom_data([
                'roleid' => $roleid,
                'userid' => $userid,
                'contextid' => $contextid,
            ]);
            manager::queue_adhoc_task($spaccesssync);
            return true;
        }

        return false;
    }

    /**
     * Handle role_assigned event.
     *
     * Does the following:
     *  - check if the assigned role has the permission needed to access course sharepoint sites. If it does, add
     *    the assigned user to the course sharepoint sites as a contributor.
     *  - sync group ownership/membership if the course is connected to a Microsoft 365 group.
     *
     * @param role_assigned $event The triggered event.
     * @return bool Success/Failure.
     */
    public static function handle_role_assigned(role_assigned $event) : bool {
        if (utils::is_connected() !== true) {
            return false;
        }

        // Update Sharepoint roles.
        if (sharepoint::is_configured() === true) {
            static::sync_spsite_access_for_roleassign_change($event->objectid, $event->relateduserid, $event->contextid);
        }

        // Update group membership.
        if (\local_o365\feature\coursesync\utils::is_enabled() === true &&
            \local_o365\feature\coursesync\utils::is_course_sync_enabled($event->courseid) === true) {
            return \local_o365\feature\coursesync\utils::sync_user_role_in_course_group($event->relateduserid, $event->courseid);
        }

        return true;
    }

    /**
     * Handle role_unassigned event.
     *
     * Does the following:
     *  - check if, by unassigning this role, the related user no longer has the required capability to access course sharepoint
     *    sites. If they don't, remove them from the sharepoint sites' contributor groups.
     *  - check if group sync is enabled for the course. If it does, remove the user as group owner if the user is a teacher.
     *
     * @param role_unassigned $event The triggered event.
     * @return bool Success/Failure.
     */
    public static function handle_role_unassigned(role_unassigned $event) : bool {
        if (utils::is_connected() !== true) {
            return false;
        }

        // Update Sharepoint roles.
        if (sharepoint::is_configured() === true) {
            static::sync_spsite_access_for_roleassign_change($event->objectid, $event->relateduserid, $event->contextid);
        }

        // Update group membership.
        if (\local_o365\feature\coursesync\utils::is_enabled() === true &&
            \local_o365\feature\coursesync\utils::is_course_sync_enabled($event->courseid) === true) {
            return \local_o365\feature\coursesync\utils::sync_user_role_in_course_group($event->relateduserid, $event->courseid, 0,
                0, false, $event->objectid);
        }

        return true;
    }

    /**
     * Handle capability_assigned or capability_unassigned events.
     * Does the following:
     *  - check if the required capability to access course sharepoint sites was removed. if it was, check if affected users
     * no longer have the required capability to access course sharepoint sites. If they don't, remove them from the
     * sharepoint sites' contributor groups.
     *  - check if capabilities related to users' team roles in connected teams are made, queue ad-hoc task to update user team
     * roles if needed.
     *
     * @param capability_assigned|capability_unassigned $event
     * @return bool
     */
    public static function handle_capability_change($event) {
        global $DB;

        $roleid = $event->objectid;

        // Role changes can be pretty heavy - run in adhoc task.
        if (sharepoint::is_configured() === true) {
            $spaccesssync = new sharepointaccesssync();
            $spaccesssync->set_custom_data(['roleid' => $roleid, 'userid' => '*', 'contextid' => null]);
            manager::queue_adhoc_task($spaccesssync);
        }

        // Resync owners and members in the groups connected to enabled Moodle courses.
        if (utils::is_connected() === true) {
            $data = $event->get_data();
            if (isset($data['other']['capability']) && in_array($data['other']['capability'],
                    ['local/o365:teammember', 'local/o365:teamowner'])) {
                $existingtasks = manager::get_adhoc_tasks('\local_o365\task\groupmembershipsync');
                if (empty($existingtasks)) {
                    $groupmembershipsync = new groupmembershipsync();
                    manager::queue_adhoc_task($groupmembershipsync);
                }
            }
        }

        return true;
    }

    /**
     * Handle role_deleted event
     *
     * Does the following:
     *  - Unfortunately the role has already been deleted when we hear about it here, and have no way to determine the affected
     *    users. Therefore, we have to do a global sync.
     *
     * @param role_deleted $event The triggered event.
     * @return bool Success/Failure.
     */
    public static function handle_role_deleted(role_deleted $event) : bool {
        if (utils::is_connected() !== true && sharepoint::is_configured() !== true) {
            return false;
        }

        // Role deletions can be heavy - run in cron.
        if (sharepoint::is_configured() === true) {
            $spaccesssync = new sharepointaccesssync();
            $spaccesssync->set_custom_data(['roleid' => '*', 'userid' => '*', 'contextid' => null]);
            manager::queue_adhoc_task($spaccesssync);
        }

        // Resync owners and members in the groups connected to enabled Moodle courses.
        if (utils::is_connected() === true) {
            $existingtasks = manager::get_adhoc_tasks('\local_o365\task\groupmembershipsync');
            if (empty($existingtasks)) {
                $groupmembershipsync = new groupmembershipsync();
                manager::queue_adhoc_task($groupmembershipsync);
            }
        }

        return true;
    }

    /**
     * Handle user_deleted event.
     *
     * @param user_deleted $event The triggered event.
     * @return bool Success/Failure.
     */
    public static function handle_user_deleted(user_deleted $event) : bool {
        global $DB;
        $userid = $event->objectid;
        $DB->delete_records('local_o365_token', ['user_id' => $userid]);
        $DB->delete_records('local_o365_objects', ['type' => 'user', 'moodleid' => $userid]);
        $DB->delete_records('local_o365_connections', ['muserid' => $userid]);
        $DB->delete_records('local_o365_appassign', ['muserid' => $userid]);
        return true;
    }

    /**
     * Send proactive notifications to o365 users when a notification is sent to the Moodle user.
     *
     * @param notification_sent $event
     *
     * @return bool
     */
    public static function handle_notification_sent(notification_sent $event) : bool {
        global $CFG, $DB, $PAGE;

        // Check if we have the configuration to send proactive notifications.
        $aadtenant = get_config('local_o365', 'aadtenant');
        $botappid = get_config('local_o365', 'bot_app_id');
        $botappsecret = get_config('local_o365', 'bot_app_password');
        $notificationendpoint = get_config('local_o365', 'bot_webhook_endpoint');
        if (empty($aadtenant) || empty($botappid) || empty($botappsecret) || empty($notificationendpoint)) {
            // Incomplete settings, exit.
            if (!PHPUNIT_TEST) {
                debugging('SKIPPED: handle_notification_sent - incomplete settings', DEBUG_DEVELOPER);
            }
            return true;
        }

        $notificationid = $event->objectid;
        $notification = $DB->get_record('notifications', ['id' => $notificationid]);
        if (!$notification) {
            // Notification cannot be found, exit.
            debugging('SKIPPED: handle_notification_sent - notification cannot be found', DEBUG_DEVELOPER);
            return true;
        }

        $user = $DB->get_record('user', ['id' => $notification->useridto]);
        if (!$user) {
            // Recipient user invalid, exit.
            debugging('SKIPPED: handle_notification_sent - recipient user invalid', DEBUG_DEVELOPER);
            return true;
        }

        if ($user->auth != 'oidc') {
            // Recipient user is not Microsoft 365 user, exit.
            debugging('SKIPPED: handle_notification_sent - recipient user is not Microsoft 365 user', DEBUG_DEVELOPER);
            return true;
        }

        // Get user object record.
        $userrecord = $DB->get_record('local_o365_objects', ['type' => 'user', 'moodleid' => $user->id]);
        if (!$userrecord) {
            // Recipient user doesn't have an object ID, exit.
            debugging('SKIPPED: handle_notification_sent - recipient user doesn\'t have an object ID', DEBUG_DEVELOPER);
            return true;
        }

        // Get course object record.
        if (!array_key_exists('courseid', $event->other)) {
            // Course doesn't exist, exit.
            debugging('SKIPPED: handle_notification_sent - course doesn\'t exist', DEBUG_DEVELOPER);
            return true;
        }
        $courseid = $event->other['courseid'];
        if (!$courseid || $courseid == SITEID) {
            // Invalid course id, exit.
            debugging('SKIPPED: handle_notification_sent - invalid course id', DEBUG_DEVELOPER);
            return true;
        }
        $course = $DB->get_record('course', ['id' => $courseid]);
        if (!$course) {
            // Invalid course, exit.
            debugging('SKIPPED: handle_notification_sent - invalid course id', DEBUG_DEVELOPER);
            return true;
        }

        // Get course object record.
        $courserecord = $DB->get_record('local_o365_objects',
            ['type' => 'group', 'subtype' => 'course', 'moodleid' => $courseid]);
        if (!$courserecord) {
            // Course record doesn't have an object ID, exit.
            debugging('SKIPPED: handle_notification_sent - course record doesn\'t have an object ID', DEBUG_DEVELOPER);
            return true;
        } else {
            $courseobjectid = $courserecord->objectid;
        }

        // Passed all tests, need to send notification.
        $botframework = new botframework();
        if (!$botframework->has_token()) {
            // Cannot get token, exit.
            debugging('SKIPPED: handle_notification_sent - cannot get token from bot framework', DEBUG_DEVELOPER);
            return true;
        }

        // Check if we need to add activity details.
        $listitems = [];
        if ((strpos($notification->component, 'mod_') !== false) && !empty($notification->contexturl)) {
            $PAGE->theme->force_svg_use(null);
            $listitems[] = [
                'title' => $notification->contexturlname,
                'subtitle' => '',
                'icon' => $CFG->wwwroot . '/local/o365/pix/moodle.png',
                'action' => $notification->contexturl,
                'actionType' => 'openUrl'
            ];
        }
        $message = (empty($notification->smallmessage) ? $notification->fullmessage : $notification->smallmessage);
        // Send notification.
        $botframework->send_notification($courseobjectid, $userrecord->objectid, $message, $listitems, $notificationendpoint);
        return true;
    }

    /**
     * If "clientid" value of "auth_oidc" is changed, clear all tokens reported to user.
     *
     * @param config_log_created $event
     *
     * @return bool
     */
    public static function handle_config_log_created(config_log_created $event) : bool {
        global $DB;

        $eventdata = $event->get_data();

        if ($eventdata['other']['plugin'] == 'auth_oidc' && $eventdata['other']['name'] == 'clientid') {
            // Clear local_o365_token table.
            $DB->delete_records('local_o365_token');

            // Clear auth_oidc_token table.
            $DB->delete_records('auth_oidc_token');

            // Clear local_o365_connections table.
            $DB->delete_records('local_o365_connections');

            // Clear user records in local_o365_objects table.
            $DB->delete_records('local_o365_objects', ['type' => 'user']);

            // Delete delta user token, and force a user sync task run.
            unset_config('local_o365', 'task_usersync_lastdeltatoken');
            if ($usersynctask = $DB->get_record('task_scheduled',
                ['component' => 'local_o365', 'classname' => '\local_o365\task\usersync'])) {
                $usersynctask->nextruntime = time();
                $DB->update_record('task_scheduled', $usersynctask);
            }
        }

        if ($eventdata['other']['name'] == 'enableapponlyaccess' && $eventdata['other']['oldvalue'] == '0' &&
            $eventdata['other']['value'] == '1') {
            unset_config('systemtokens', 'local_o365');
        }

        purge_all_caches();

        return true;
    }
}
