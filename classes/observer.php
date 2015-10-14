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
 * Event observers used in LU Badges.
 *
 * @package    local_lubadges
 * @copyright  2015 Lancaster University (http://www.lancaster.ac.uk/)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Tony Butler <a.butler4@lancaster.ac.uk>
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/local/lubadges/lib.php');

/**
 * Event handler for local_lubadges plugin.
 */
class local_lubadges_observer {

    /**
     * Process badge awarding function called by event trigger (see db/events.php).
     * This queues a scheduled task to issue the badge via the LU Badges API.
     *
     * @param \core\event\badge_awarded $event Event data object passed over by core badge_awarded event
     * @return bool Success status
     */
    public static function badge_awarded(\core\event\badge_awarded $event) {
        global $DB;

        $eventdata = (object) $event->get_data();

        // First check that the badge being awarded is an instance of an LU Badge prototype.
        if ($protoid = $DB->get_field('local_lubadges_instances', 'protoid', array('instanceid' => $eventdata->objectid))) {

            // Make sure another instance of this prototype hasn't already been awarded to the same user.
            $sql = "SELECT li.*
                      FROM {local_lubadges_issued} li
                      JOIN {badge_issued} bi ON bi.id = li.issuedid
                       AND bi.id <> :issuedid
                       AND bi.userid = :userid
                      JOIN {local_lubadges_instances} lb ON lb.instanceid = bi.badgeid
                       AND lb.instanceid <> :instanceid
                       AND lb.protoid = :protoid
                     WHERE li.status = :queued
                        OR li.status = :issued";

            $params = array(
                'issuedid'   => $eventdata->other['badgeissuedid'],
                'userid'     => $eventdata->relateduserid,
                'instanceid' => $eventdata->objectid,
                'protoid'    => $protoid,
                'queued'     => LUBADGES_STATUS_QUEUED,
                'issued'     => LUBADGES_STATUS_ISSUED
            );

            if ($DB->record_exists_sql($sql, $params)) {
                // This LU Badge has already been (or is queued to be) issued to this user.
                return true;
            }

            // Prepare data for recording.
            $badgetoissue = new stdClass();
            $badgetoissue->issuedid = $eventdata->other['badgeissuedid'];
            $badgetoissue->status = LUBADGES_STATUS_QUEUED;
            $badgetoissue->retrycount = 0;
            $badgetoissue->message = '';

            // See if this task is already queued.
            if ($task = $DB->get_record('local_lubadges_issued', array('issuedid' => $badgetoissue->issuedid))) {
                if ($task->status == LUBADGES_STATUS_ISSUED) {
                    // Badge already issued.
                    return true;
                }
                // Update existing task.
                $badgetoissue->id = $task->id;
                $DB->update_record('local_lubadges_issued', $badgetoissue);
            } else {
                // Insert new task.
                $DB->insert_record('local_lubadges_issued', $badgetoissue);
            }

            // Finally, execute the task.
            local_lubadges_issue($badgetoissue->issuedid, false);
        }

        return true;
    }
}
