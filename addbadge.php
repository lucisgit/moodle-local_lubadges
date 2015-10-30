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
 * First step page for adding an LU Badge instance.
 *
 * @package    local_lubadges
 * @copyright  2012 onwards Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @copyright  2015 Lancaster University (http://www.lancaster.ac.uk/)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Yuliya Bozhko <yuliya.bozhko@totaralms.com>
 * @author     Tony Butler <a.butler4@lancaster.ac.uk>
 */

require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once($CFG->libdir . '/badgeslib.php');
require_once($CFG->dirroot . '/local/lubadges/lib.php');
require_once($CFG->dirroot . '/local/lubadges/edit_form.php');

// Update list of LU Badge prototypes.
local_lubadges_update(false);

$type = required_param('type', PARAM_INT);
$courseid = optional_param('id', 0, PARAM_INT);
$prototype = optional_param('prototype', 0, PARAM_INT);

require_login();

if (empty($CFG->enablebadges)) {
    print_error('badgesdisabled', 'badges');
}

if (empty($CFG->badges_allowcoursebadges) && ($type == BADGE_TYPE_COURSE)) {
    print_error('coursebadgesdisabled', 'badges');
}

$title = get_string('add', 'local_lubadges');

if (($type == BADGE_TYPE_COURSE) && ($course = $DB->get_record('course', array('id' => $courseid)))) {
    require_login($course);
    $coursecontext = context_course::instance($course->id);
    $PAGE->set_context($coursecontext);
    $PAGE->set_pagelayout('incourse');
    $PAGE->set_url('/local/lubadges/addbadge.php', array('type' => $type, 'id' => $course->id));
    $heading = format_string($course->fullname, true, array('context' => $coursecontext)) . ": " . $title;
    $PAGE->set_heading($heading);
    $PAGE->set_title($heading);
} else {
    $PAGE->set_context(context_system::instance());
    $PAGE->set_pagelayout('admin');
    $PAGE->set_url('/local/lubadges/addbadge.php', array('type' => $type));
    $PAGE->set_heading($title);
    $PAGE->set_title($title);
}

require_capability('local/lubadges:addbadge', $PAGE->context);

$PAGE->requires->js('/badges/backpack.js');
$PAGE->requires->js_init_call('check_site_access', null, false);

$select = new select_prototype_form($PAGE->url,
        array_merge($PAGE->url->params(), array('prototype' => $prototype, 'context' => $PAGE->context)));

if (!empty($prototype)) {
    $badge = $DB->get_record('local_lubadges_prototypes', array('id' => $prototype));
    $badge->prototype = $prototype;
    unset($badge->id);

    $form = new edit_details_form($PAGE->url, array('action' => 'add', 'badge' => $badge));
    $form->set_data($badge);

    if ($form->is_cancelled()) {
        redirect(new moodle_url('/local/lubadges/index.php', $PAGE->url->params()));
    } else if ($data = $form->get_data()) {
        // Adding LU Badge instance here.
        $now = time();

        $fordb = new stdClass();
        $fordb->id = null;

        $fordb->name = $data->name . ' [' . $COURSE->shortname . ']';
        $fordb->description = $data->description;
        $fordb->timecreated = $now;
        $fordb->timemodified = $now;
        $fordb->usercreated = $USER->id;
        $fordb->usermodified = $USER->id;
        $fordb->issuername = $data->issuername;
        $fordb->issuerurl = $data->issuerurl;
        $fordb->issuercontact = $data->issuercontact;
        $fordb->expiredate = ($data->expiry == 1) ? $data->expiredate : null;
        $fordb->expireperiod = ($data->expiry == 2) ? $data->expireperiod : null;
        $fordb->type = $type;
        $fordb->courseid = ($type == BADGE_TYPE_COURSE) ? $courseid : null;
        $fordb->messagesubject = get_string('messagesubject', 'badges');
        $fordb->message = get_string('messagebody', 'badges',
                html_writer::link($CFG->wwwroot . '/badges/mybadges.php', get_string('managebadges', 'badges')));
        $fordb->attachment = 1;
        $fordb->notification = BADGE_MESSAGE_NEVER;
        $fordb->status = BADGE_STATUS_INACTIVE;

        $newid = $DB->insert_record('badge', $fordb, true);

        $instance = new stdClass();
        $instance->protoid = $prototype;
        $instance->instanceid = $newid;
        if (!$DB->insert_record('local_lubadges_instances', $instance, false)) {
            mtrace('Database error: LU badge instance not linked to prototype');
        }

        $newbadge = new badge($newid);

        $curl = new curl();
        $imagepath = $CFG->tempdir . '/badge.png';
        $result = $curl->download_one($data->imageurl, null, array('filepath' => $imagepath, 'timeout' => 5));
        if ($result !== true) {
            throw new moodle_exception('errorwhiledownload', 'local_lubadges',
                    new moodle_url('/local/lubadges/index.php', $PAGE->url->params()), $result);
        }
        badges_process_badge_image($newbadge, $imagepath);
        @unlink($imagepath);

        // If a user can configure badge criteria, they will be redirected to the criteria page.
        if (has_capability('moodle/badges:configurecriteria', $PAGE->context)) {
            redirect(new moodle_url('/local/lubadges/criteria.php', array('id' => $newid)));
        }
        redirect(new moodle_url('/local/lubadges/overview.php', array('id' => $newid)));
    }
}

echo $OUTPUT->header();
echo $OUTPUT->box('', 'notifyproblem hide', 'check_connection');

$select->display();

// Give user the opportunity of creating a new prototype (if they have the capability).
if (empty($prototype)) {
    if (has_capability('local/lubadges:createbadge', $PAGE->context)) {
        $createurl = new moodle_url('/local/lubadges/createbadge.php', $PAGE->url->params());
        $createlink = html_writer::link($createurl, get_string('createbadge', 'local_lubadges'));
        echo $OUTPUT->notify_message(get_string('addtext', 'local_lubadges') . html_writer::empty_tag('br') .
                html_writer::empty_tag('br') . get_string('createtext', 'local_lubadges', $createlink));
    } else {
        echo $OUTPUT->notify_message(get_string('addtext', 'local_lubadges'));
    }
} else {
    $form->display();
}

echo $OUTPUT->footer();
