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
 * LU Badges function library.
 *
 * @package    local_lubadges
 * @copyright  2015 Lancaster University (http://www.lancaster.ac.uk/)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Tony Butler <a.butler4@lancaster.ac.uk>
 */

defined('MOODLE_INTERNAL') || die();

/**
 * HTTP methods.
 */
define('LUBADGES_HTTP_GET', 'get');
define('LUBADGES_HTTP_POST', 'post');

/**
 * Badge issuing statuses.
 */
define('LUBADGES_STATUS_QUEUED', 'queued');
define('LUBADGES_STATUS_ISSUED', 'issued');
define('LUBADGES_STATUS_FAILED', 'failed');

/**
 * Other constants
 */
define('LUBADGES_MAX_RETRY_COUNT', 3);

require_once($CFG->libdir . '/filelib.php');

/**
 * Returns the admin config settings for the LU Badges plugin.
 *
 * @param bool $cron Whether or not to output results
 * @return bool|mixed The config settings object or false
 */
function local_lubadges_get_config($cron = true) {
    if (!($config = get_config('local_lubadges')) || empty($config->apiendpoint) || empty($config->apikey)) {
        if ($cron) {
            mtrace('Plugin local_lubadges is not fully configured.');
        }
        return false;
    }

    return $config;
}

/**
 * Returns a list of LU Badge prototypes for a select menu.
 *
 * @param int $type Course or site context type
 * @return array|bool List of options or false
 */
function local_lubadges_get_menu_options($type) {
    global $COURSE, $DB;

    if ($type == BADGE_TYPE_COURSE) {
        $fragment = 'b.courseid = :courseid';
    } else {
        $fragment = 'b.type = :type AND b.courseid IS NULL';
    }

    $sql = "SELECT p.id, p.name
              FROM {local_lubadges_prototypes} p
             WHERE p.id NOT IN (
                SELECT i.protoid
                  FROM {local_lubadges_instances} i
                  JOIN {badge} b ON b.id = i.instanceid
                   AND $fragment)
               AND p.status = :live
          ORDER BY name ASC";

    $params = array(
        'courseid' => $COURSE->id,
        'type'  => $type,
        'live'  => 'live'
    );

    if (!$options = $DB->get_records_sql_menu($sql, $params)) {
        return false;
    }

    return $options;
}

/**
 * Calls the LU Badges external API according to the supplied params.
 *
 * @param string $path The URL path to the required API method
 * @param array $params The URL params to use in the query string
 * @param string $method The HTTP method to use
 * @param bool $cron Whether or not to output results
 * @return bool|mixed|string A decoded JSON object, an error string or false
 */
function local_lubadges_call_api($path, $params, $method = LUBADGES_HTTP_GET, $cron = true) {

    // Get admin config.
    if (!$config = local_lubadges_get_config($cron)) {
        return false;
    }

    // Prepare API request data.
    $url = new moodle_url(rtrim($config->apiendpoint, '/') . $path, $params);

    $curl = new curl();
    $curl->setHeader('Authorization: Token token=' . $config->apikey);

    // Submit API request.
    switch ($method) {
        case LUBADGES_HTTP_GET:
            $response = $curl->get($url->out_omit_querystring(), $params);
            break;
        case LUBADGES_HTTP_POST:
            $response = $curl->post($url->out_omit_querystring(), $url->get_query_string(false));
            break;
    }

    $json = json_decode($response);
    $curlinfo = $curl->get_info();

    // If all is well, return JSON.
    if (!empty($json) && $curlinfo['http_code'] == 200) {
        return $json;
    }

    // Check for invalid JSON and/or HTTP errors.
    if (empty($json)) {
        $error = 'Invalid JSON string. ';
        if (($code = $curlinfo['http_code']) != 200) {
            $error .= 'HTTP error: ' . $code . '; ';
        }
        $error .= 'API response: ' . $response;
    } else if (is_object($json) && isset($json->error)) {
        $error = 'API error. ';
        foreach ($json->error as $key => $value) {
            $error .= $key . ': ' . $value . '; ';
        }
    } else {
        $error = 'Unknown error. API response: ' . $response;
    }
    if ($cron) {
        mtrace($error);
    }

    return $error;
}

/**
 * Retrieve a list of badges from the LU Badges API, optionally restricted
 * to a specific collection or user.
 *
 * @param string $collection Optional collection ID
 * @param string $username Optional username
 * @param bool $cron Whether or not to output results
 * @return array Array of badge objects indexed by ID, or just the IDs
 */
function local_lubadges_get_badges($collection = '', $username = '', $cron = true) {

    // Prepare API request data.
    $path = '/badges';
    $params = array();
    if (!empty($collection)) {
        $params = array('collection_id' => $collection);
    } else if (!empty($username)) {
        $params = array('user' => $username);
    }

    // Attempt API call.
    if (!$response = local_lubadges_call_api($path, $params, LUBADGES_HTTP_GET, $cron)) {
        return array();
    }

    // Make sure response is an array.
    if (!is_array($response)) {
        return array();
    }

    // If this is a user-specific query, just return a list of badge IDs.
    if (!empty($username)) {
        $badgeids = array();
        foreach ($response as $badge) {
            $badgeids[] = $badge->id;
        }
        return $badgeids;
    }

    // We can only award badges with no pre-requisites for other badges.
    $badges = array();
    foreach ($response as $badge) {
        if (empty($badge->required_badges)) {
            // Rename/unset a few keys to match our table.
            $badge->badgeid = $badge->id;
            $badge->imageurl = $badge->image;
            $badge->collection = $badge->collection_id;
            $badge->timecreated = date('U', strtotime($badge->created_at));
            $badge->timemodified = date('U', strtotime($badge->updated_at));
            unset($badge->id, $badge->image, $badge->collection_id, $badge->required_badges, $badge->auto_issue, $badge->object,
                $badge->created_at, $badge->updated_at);
            $badges[$badge->badgeid] = $badge;
        }
    }

    return $badges;
}

/**
 * Function to update the stored list of available LU Badges.
 *
 * @param bool $cron Whether or not to output results
 * @return void
 */
function local_lubadges_update($cron = true) {
    global $DB;

    // Get admin config.
    if (!$config = local_lubadges_get_config($cron)) {
        return;
    }

    if ($cron) {
        mtrace('Updating list of LU Badge prototypes from external API ...');
    }

    // Retrieve badges only from collections specified in config.
    if (!empty($config->collections)) {
        $collections = array_map('trim', explode(',', $config->collections));
        $badges = array();
        foreach ($collections as $collection) {
            $badges = array_merge($badges, local_lubadges_get_badges($collection, '', $cron));
        }
    } else {
        $badges = local_lubadges_get_badges('', '', $cron);
    }

    // Retrieve the list of existing stored badge prototypes.
    $records = $DB->get_records('local_lubadges_prototypes');

    // Work out which need to be updated/deleted.
    $updated = 0;
    $deleted = 0;

    foreach ($records as $record) {
        if (in_array($record->badgeid, array_keys($badges))) {
            // Is an update required (or an un-deletion)?
            if ($record->timemodified < $badges[$record->badgeid]->timemodified || $record->status == 'deleted') {
                $badges[$record->badgeid]->id = $record->id;
                $DB->update_record('local_lubadges_prototypes', $badges[$record->badgeid]);
                $updated++;
            }
            unset($badges[$record->badgeid]);
        } else {
            // We don't actually delete prototypes, to avoid orphaned instances.
            if ($record->status <> 'deleted') {
                $record->status = 'deleted';
                $DB->update_record('local_lubadges_prototypes', $record);
                $deleted++;
            }
        }
    }

    // Any remaining need to be inserted.
    $DB->insert_records('local_lubadges_prototypes', $badges);

    if ($cron) {
        mtrace('Created ' . count($badges) . ' new LU Badge prototypes.');
        mtrace('Updated ' . $updated . ' existing LU Badge prototypes.');
        mtrace('Marked ' . $deleted . ' LU Badge prototypes as deleted.');
    }

}

/**
 * Returns the data required to issue LU Badges via external API.
 *
 * @param int $issuedid Specific badge to be issued
 * @return array Array of objects
 */
function local_lubadges_get_queue($issuedid = 0) {
    global $DB;

    $sql = "SELECT li.*, lp.badgeid, u.username
              FROM {local_lubadges_issued} li
              JOIN {badge_issued} bi ON bi.id = li.issuedid
              JOIN {local_lubadges_instances} lb ON lb.instanceid = bi.badgeid
              JOIN {local_lubadges_prototypes} lp ON lp.id = lb.protoid
              JOIN {user} u ON u.id = bi.userid
             WHERE li.status = :status";

    if (!empty($issuedid)) {
        $sql .= "AND li.issuedid = :issuedid";
    }

    $params = array(
        'status'   => LUBADGES_STATUS_QUEUED,
        'issuedid' => $issuedid
    );

    return $DB->get_records_sql($sql, $params);
}

/**
 * Function to issue LU Badges via the external API.
 *
 * @param int $issuedid Specific badge to be issued
 * @param bool $cron Whether or not to output results
 * @return void
 */
function local_lubadges_issue($issuedid = 0, $cron = true) {
    global $DB;

    // Fetch the necessary data to issue the badges.
    if ($records = local_lubadges_get_queue($issuedid)) {
        if ($cron) {
            mtrace('Issuing ' . count($records) . ' badges via LU Badges API ...');
        }
    }

    foreach ($records as $record) {
        // Initial checks.
        if (empty($record->username)) {
            $error = 'Username for issued badge ' . $record->issuedid . ' does not exist.';
            $record->message = $error;
            $record->status = LUBADGES_STATUS_FAILED;
            $DB->update_record('local_lubadges_issued', $record);
            if ($cron) {
                mtrace($error);
            }
            continue;
        }
        if (empty($record->badgeid)) {
            $error = 'External badge ID for issued badge ' . $record->issuedid . ' is not recorded.';
            $record->message = $error;
            $record->status = LUBADGES_STATUS_FAILED;
            $DB->update_record('local_lubadges_issued', $record);
            if ($cron) {
                mtrace($error);
            }
            continue;
        }

        // Make sure the user hasn't already been issued this badge (possibly outside of Moodle).
        $issuedbadges = local_lubadges_get_badges('', $record->username, $cron);
        if (in_array($record->badgeid, $issuedbadges)) {
            $record->message = 'Badge ' . $record->badgeid . ' already issued to ' . $record->username . ' outside of Moodle.';
            $record->status = LUBADGES_STATUS_ISSUED;
            $DB->update_record('local_lubadges_issued', $record);
            if ($cron) {
                mtrace('LU badge "' . $record->badgeid . '" has already been issued to user "' . $record->username . '".');
            }
            continue;
        }

        // Prepare API request data.
        $path = '/badges/' . $record->badgeid . '/issue';
        $params = array('recipient' => $record->username);

        // Attempt API call.
        if (!$response = local_lubadges_call_api($path, $params, LUBADGES_HTTP_POST, $cron)) {
            continue;
        }

        // Check that badge has been successfully issued.
        if (isset($response->badges)) {
            foreach ($response->badges as $badge) {
                if ($badge->id == $record->badgeid) {
                    $record->message = 'Badge ' . $record->badgeid . ' issued to ' . $record->username . ' on ' . date('r') . '.';
                    $record->status = LUBADGES_STATUS_ISSUED;
                    $DB->update_record('local_lubadges_issued', $record);
                    if ($cron) {
                        mtrace('LU badge "' . $record->badgeid . '" successfully issued to user "' . $record->username . '".');
                    }
                    continue 2;
                }
            }
        }

        // Only increment retry counter for 404 errors.
        $error = 'LU badge not issued. ' . $response;
        $record->message = $error;
        if (strpos($response, 'status: 404') !== false) {
            // If this was the last attempt, declare failure.
            if ($record->retrycount >= LUBADGES_MAX_RETRY_COUNT) {
                $record->status = LUBADGES_STATUS_FAILED;
            } else {
                $record->retrycount++;
            }
        }
        $DB->update_record('local_lubadges_issued', $record);
        if ($cron) {
            mtrace($error);
        }
    }

}
