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
 * Form classes for editing LU Badge instances.
 *
 * @package    local_lubadges
 * @copyright  2012 onwards Totara Learning Solutions Ltd {@link http://www.totaralms.com/}
 * @copyright  2015 Lancaster University (http://www.lancaster.ac.uk/)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Yuliya Bozhko <yuliya.bozhko@totaralms.com>
 * @author     Tony Butler <a.butler4@lancaster.ac.uk>
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');
require_once($CFG->libdir . '/badgeslib.php');

/**
 * Form to select LU Badge prototype.
 *
 */
class select_lubadge_prototype_form extends moodleform {

    /**
     * Defines the form
     */
    public function definition() {
        $mform = $this->_form;
        $mform->disable_form_change_checker();
        $type = $this->_customdata['type'];

        $mform->addElement('header', 'lubadges', get_string('pluginname', 'local_lubadges'));

        $label = get_string('selectbadge', 'local_lubadges');
        if ($options = local_lubadges_get_menu_options($type)) {
            $options = array('0' => get_string('choosedots')) + $options;
            $mform->addElement('select', 'prototype', $label, $options, array('onchange' => 'this.form.submit()'));

            $mform->addElement('html', html_writer::start_tag('noscript'));
            $this->add_action_buttons(false, get_string('loadbadgedetails', 'local_lubadges'));
            $mform->addElement('html', html_writer::end_tag('noscript'));
        } else {
            $mform->addElement('static', 'prototype', $label, get_string('nobadges', 'local_lubadges'));
        }
    }
}

/**
 * Form to edit badge details.
 *
 */
class edit_lubadge_details_form extends moodleform {

    /**
     * Defines the form
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;
        $badge = (isset($this->_customdata['badge'])) ? $this->_customdata['badge'] : false;
        $action = $this->_customdata['action'];

        $mform->addElement('header', 'badgedetails', get_string('badgedetails', 'badges'));

        $mform->addElement('text', 'name', get_string('name'), array('size' => '70'));
        // Using PARAM_FILE to avoid problems later when downloading badge files.
        $mform->setType('name', PARAM_FILE);
        $mform->addRule('name', null, 'required');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $mform->addElement('textarea', 'description', get_string('description', 'badges'));
        $mform->setType('description', PARAM_NOTAGS);
        $mform->addRule('description', null, 'required');

        $mform->addElement('textarea', 'requirements', get_string('requirements', 'local_lubadges'));
        $mform->setType('requirements', PARAM_NOTAGS);

        $mform->addElement('textarea', 'hint', get_string('hint', 'local_lubadges'));
        $mform->setType('hint', PARAM_NOTAGS);

        if ($action == 'add') {
            $image = html_writer::img($badge->imageurl, $badge->name, array('class' => 'activatebadge lubadgeimage'));
            $mform->addElement('static', 'image', get_string('badgeimage', 'badges'), $image);
            $mform->addElement('hidden', 'imageurl', $badge->imageurl);
            $mform->setType('imageurl', PARAM_URL);
        } else {
            $mform->addElement('static', 'currentimage', get_string('badgeimage', 'badges'));
            $mform->addElement('hidden', 'image', null);
            $mform->setType('image', PARAM_FILE);
        }

        $mform->addElement('text', 'collection', get_string('collection', 'local_lubadges'), array('size' => '70'));
        $mform->setType('collection', PARAM_TEXT);

        $mform->addElement('text', 'level', get_string('level', 'local_lubadges'), array('size' => '70'));
        $mform->setType('level', PARAM_TEXT);

        $mform->addElement('text', 'points', get_string('points', 'local_lubadges'), array('size' => '70'));
        $mform->setType('points', PARAM_INT);

        $mform->freeze('name, description');
        $mform->hardFreeze('requirements, hint, collection, level, points');

        $mform->addElement('header', 'issuerdetails', get_string('issuerdetails', 'badges'));

        $mform->addElement('text', 'issuername', get_string('name'), array('size' => '70'));
        $mform->setType('issuername', PARAM_NOTAGS);
        $mform->addRule('issuername', null, 'required');
        if (!empty($CFG->badges_defaultissuername)) {
            $mform->setDefault('issuername', $CFG->badges_defaultissuername);
        } else {
            $mform->setDefault('issuername', get_string('issuername', 'local_lubadges'));
        }
        $mform->addHelpButton('issuername', 'issuername', 'badges');

        $mform->addElement('text', 'issuercontact', get_string('contact', 'badges'), array('size' => '70'));
        if (isset($CFG->badges_defaultissuercontact)) {
            $mform->setDefault('issuercontact', $CFG->badges_defaultissuercontact);
        }
        $mform->setType('issuercontact', PARAM_RAW);
        $mform->addHelpButton('issuercontact', 'contact', 'badges');

        // Set issuer URL.
        // Have to parse URL because badge issuer origin cannot be a subfolder in wwwroot.
        $url = parse_url($CFG->wwwroot);
        $mform->addElement('hidden', 'issuerurl', $url['scheme'] . '://' . $url['host']);
        $mform->setType('issuerurl', PARAM_URL);

        $mform->addElement('hidden', 'expiry', 0);
        $mform->setType('expiry', PARAM_INT);

        $mform->addElement('hidden', 'action', $action);
        $mform->setType('action', PARAM_TEXT);

        if ($action == 'add') {
            // Add hidden fields.
            $mform->addElement('hidden', 'prototype', $badge->prototype);
            $mform->setType('prototype', PARAM_INT);

            $this->add_action_buttons(true, get_string('addbutton', 'local_lubadges'));
        } else {
            // Add hidden fields.
            $mform->addElement('hidden', 'id', $badge->id);
            $mform->setType('id', PARAM_INT);

            $this->add_action_buttons();
            $this->set_data($badge);

            // Freeze all elements if badge is active or locked.
            if ($badge->is_active() || $badge->is_locked()) {
                $mform->hardFreezeAllVisibleExcept(array());
            }
        }
    }

    /**
     * Load in existing data as form defaults
     *
     * @param stdClass|array $default_values object or array of default values
     */
    public function set_data($badge) {
        $default_values = array();
        parent::set_data($badge);

        if (!empty($badge->id)) {
            $default_values['currentimage'] = print_badge_image($badge, $badge->get_context(), 'large');
        }

        parent::set_data($default_values);
    }

    /**
     * Validates form data
     */
    public function validation($data, $files) {
        global $COURSE, $DB;

        $errors = parent::validation($data, $files);

        if (!empty($data['issuercontact']) && !validate_email($data['issuercontact'])) {
            $errors['issuercontact'] = get_string('invalidemail');
        }

        // Instance name will be made unique on save, based on context.
        $name = $data['name'] . ' [' . $COURSE->shortname . ']';

        // Check for duplicate badge names.
        if ($data['action'] == 'add') {
            $duplicate = $DB->record_exists_select('badge', 'name = :name AND status != :deleted',
                array('name' => $name, 'deleted' => BADGE_STATUS_ARCHIVED));
        } else {
            $duplicate = $DB->record_exists_select('badge', 'name = :name AND id != :badgeid AND status != :deleted',
                array('name' => $name, 'badgeid' => $data['id'], 'deleted' => BADGE_STATUS_ARCHIVED));
        }

        if ($duplicate) {
            $errors['name'] = get_string('error:duplicatename', 'local_lubadges');
        }

        return $errors;
    }
}

/**
 * Form to edit badge message.
 *
 */
class edit_lubadge_message_form extends moodleform {
    public function definition() {
        global $CFG, $OUTPUT;

        $mform = $this->_form;
        $badge = $this->_customdata['badge'];
        $action = $this->_customdata['action'];
        $editoroptions = $this->_customdata['editoroptions'];

        // Add hidden fields.
        $mform->addElement('hidden', 'id', $badge->id);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'action', $action);
        $mform->setType('action', PARAM_TEXT);

        $mform->addElement('header', 'badgemessage', get_string('configuremessage', 'badges'));
        $mform->addHelpButton('badgemessage', 'variablesubstitution', 'badges');

        $mform->addElement('text', 'messagesubject', get_string('subject', 'badges'), array('size' => '70'));
        $mform->setType('messagesubject', PARAM_TEXT);
        $mform->addRule('messagesubject', null, 'required');
        $mform->addRule('messagesubject', get_string('maximumchars', '', 255), 'maxlength', 255);

        $mform->addElement('editor', 'message_editor', get_string('message', 'badges'), null, $editoroptions);
        $mform->setType('message_editor', PARAM_RAW);
        $mform->addRule('message_editor', null, 'required');

        $mform->addElement('advcheckbox', 'attachment', get_string('attachment', 'badges'), '', null, array(0, 1));
        $mform->addHelpButton('attachment', 'attachment', 'badges');
        if (empty($CFG->allowattachments)) {
            $mform->freeze('attachment');
        }

        $options = array(
                BADGE_MESSAGE_NEVER   => get_string('never'),
                BADGE_MESSAGE_ALWAYS  => get_string('notifyevery', 'badges'),
                BADGE_MESSAGE_DAILY   => get_string('notifydaily', 'badges'),
                BADGE_MESSAGE_WEEKLY  => get_string('notifyweekly', 'badges'),
                BADGE_MESSAGE_MONTHLY => get_string('notifymonthly', 'badges'),
                );
        $mform->addElement('select', 'notification', get_string('notification', 'badges'), $options);
        $mform->addHelpButton('notification', 'notification', 'badges');

        $this->add_action_buttons();
        $this->set_data($badge);
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        return $errors;
    }
}
