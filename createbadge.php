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
 * Page for creating a new LU Badge prototype via the external API.
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

$type = required_param('type', PARAM_INT);
$courseid = optional_param('id', 0, PARAM_INT);

require_login();

if (empty($CFG->enablebadges)) {
    print_error('badgesdisabled', 'badges');
}

if (empty($CFG->badges_allowcoursebadges) && ($type == BADGE_TYPE_COURSE)) {
    print_error('coursebadgesdisabled', 'badges');
}

$title = get_string('create', 'local_lubadges');

if (($type == BADGE_TYPE_COURSE) && ($course = $DB->get_record('course', array('id' => $courseid)))) {
    require_login($course);
    $coursecontext = context_course::instance($course->id);
    $PAGE->set_context($coursecontext);
    $PAGE->set_pagelayout('incourse');
    $PAGE->set_url('/local/lubadges/createbadge.php', array('type' => $type, 'id' => $course->id));
    $heading = format_string($course->fullname, true, array('context' => $coursecontext)) . ": " . $title;
    $PAGE->set_heading($heading);
    $PAGE->set_title($heading);
} else {
    $PAGE->set_context(context_system::instance());
    $PAGE->set_pagelayout('admin');
    $PAGE->set_url('/local/lubadges/createbadge.php', array('type' => $type));
    $PAGE->set_heading($title);
    $PAGE->set_title($title);
}

require_capability('local/lubadges:createbadge', $PAGE->context);

$PAGE->requires->js('/badges/backpack.js');
$PAGE->requires->js_init_call('check_site_access', null, false);

$form = new edit_details_form($PAGE->url, array('action' => 'create'));

if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/lubadges/index.php', $PAGE->url->params()));
} else if ($data = $form->get_data()) {
    // Creating new LU Badge prototype here.
    $badge = new stdClass();

    $badge->name = $data->name;
    $badge->description = $data->description;
    $badge->requirements = $data->requirements;
    $badge->hint = $data->hint;
    $badge->collection_id = $data->collection;
    $badge->level = $data->level;
    if ($badge->level != 'bronze') {
        $badge->auto_issue = false;
    }
    $badge->status = (!empty($data->status)) ? $data->status : 'live';
    $badge->usercreated = $USER->id;
    $badge->usermodified = $USER->id;

    if (!$protoid = local_lubadges_create_badge($badge)) {
        mtrace('Something went wrong! Prototype not created.', "\n", 5);
        redirect(new moodle_url('/local/lubadges/index.php', $PAGE->url->params()));
    }

    // If badge status is 'live' and user can add an LU Badge instance, redirect to the add instance page.
    if ($badge->status == LUBADGES_PROTO_LIVE && has_capability('local/lubadges:addbadge', $PAGE->context)) {
        redirect(new moodle_url('/local/lubadges/addbadge.php', array_merge($PAGE->url->params(), array('prototype' => $protoid))),
                get_string('createsuccess', 'local_lubadges', $badge->name), 3);
    }
    redirect(new moodle_url('/local/lubadges/index.php', $PAGE->url->params()));
}

echo $OUTPUT->header();
echo $OUTPUT->box('', 'notifyproblem hide', 'check_connection');

$form->display();

echo $OUTPUT->footer();
