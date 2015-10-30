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
 * Form classes for editing LU Badge instances and prototypes.
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
require_once($CFG->dirroot . '/local/lubadges/lib.php');

/**
 * Form to select LU Badge prototype.
 *
 */
class select_prototype_form extends moodleform {

    /**
     * Defines the form
     */
    public function definition() {
        $mform = $this->_form;
        $mform->disable_form_change_checker();
        $type = $this->_customdata['type'];
        $prototype = $this->_customdata['prototype'];

        $mform->addElement('header', 'lubadges', get_string('pluginname', 'local_lubadges'));

        $label = get_string('selectbadge', 'local_lubadges');
        if ($options = local_lubadges_get_menu_options($type)) {
            $options = array('' => get_string('choosedots')) + $options;
            $mform->addElement('select', 'prototype', $label, $options, array('onchange' => 'this.form.submit()'));
            $mform->setDefault('prototype', $prototype);

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
class edit_details_form extends moodleform {

    /**
     * Defines the form
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;
        $badge = (isset($this->_customdata['badge'])) ? $this->_customdata['badge'] : false;
        $action = $this->_customdata['action'];

        if ($action == 'create') {
            $mform->addElement('header', 'badgedetails', get_string('badgedetails', 'local_lubadges'));
        } else {
            $mform->addElement('header', 'badgedetails', get_string('badgedetails', 'badges'));
        }

        $mform->addElement('text', 'name', get_string('name', 'local_lubadges'), array('size' => '70'));
        // Using PARAM_FILE to avoid problems later when downloading badge files.
        $mform->setType('name', PARAM_FILE);
        $mform->addRule('name', null, 'required');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        if ($action == 'create') {
            $mform->addHelpButton('name', 'name', 'local_lubadges');
        }

        $mform->addElement('textarea', 'description', get_string('description', 'local_lubadges'),
                'wrap="virtual" rows="8" cols="70"');
        $mform->setType('description', PARAM_NOTAGS);
        $mform->addRule('description', null, 'required');
        if ($action == 'create') {
            $mform->addHelpButton('description', 'description', 'local_lubadges');
        }

        $mform->addElement('textarea', 'requirements', get_string('requirements', 'local_lubadges'),
                'wrap="virtual" rows="8" cols="70"');
        $mform->setType('requirements', PARAM_NOTAGS);
        $mform->addRule('requirements', null, 'required');
        if ($action == 'create') {
            $mform->addHelpButton('requirements', 'requirements', 'local_lubadges');
        }

        $mform->addElement('textarea', 'hint', get_string('hint', 'local_lubadges'),
                'wrap="virtual" rows="8" cols="70"');
        $mform->setType('hint', PARAM_NOTAGS);
        $mform->addRule('hint', null, 'required');
        if ($action == 'create') {
            $mform->addHelpButton('hint', 'hint', 'local_lubadges');
        }

        if ($action == 'add') {
            $image = html_writer::img($badge->imageurl, $badge->name, array('class' => 'activatebadge lubadgeimage'));
            $mform->addElement('static', 'image', get_string('badgeimage', 'badges'), $image);
            $mform->addElement('hidden', 'imageurl', $badge->imageurl);
            $mform->setType('imageurl', PARAM_URL);
        } else if ($action == 'details') {
            $mform->addElement('static', 'currentimage', get_string('badgeimage', 'badges'));
            $mform->addElement('hidden', 'image', null);
            $mform->setType('image', PARAM_FILE);
        }

        if (($config = local_lubadges_get_config(false)) && !empty($config->collections)) {
            $collections = array_map('trim', explode(',', $config->collections));
        } else {
            $collections = array();
            if ($prototypes = local_lubadges_get_badges('', '', false, false)) {
                foreach ($prototypes as $prototype) {
                    $collections[] = $prototype->collection;
                }
                array_unique($collections);
            }
        }
        sort($collections);
        $options = array('' => get_string('choosedots'));
        foreach ($collections as $collection) {
            $options[$collection] = $collection;
        }
        $mform->addElement('select', 'collection', get_string('collection', 'local_lubadges'), $options);
        $mform->addRule('collection', null, 'required');
        if ($action == 'create') {
            $mform->addHelpButton('collection', 'collection', 'local_lubadges');
        }

        $levels = array(
            LUBADGES_LEVEL_BRONZE => get_string(LUBADGES_LEVEL_BRONZE, 'local_lubadges'),
            LUBADGES_LEVEL_SILVER => get_string(LUBADGES_LEVEL_SILVER, 'local_lubadges'),
            LUBADGES_LEVEL_GOLD => get_string(LUBADGES_LEVEL_GOLD, 'local_lubadges'),
            LUBADGES_LEVEL_PLATINUM => get_string(LUBADGES_LEVEL_PLATINUM, 'local_lubadges')
        );
        $mform->addElement('select', 'level', get_string('level', 'local_lubadges'), $levels);
        $mform->addRule('level', null, 'required');
        if ($action == 'create') {
            $mform->addHelpButton('level', 'level', 'local_lubadges');
        }

        if ($action != 'create') {
            $mform->addElement('static', 'points', get_string('points', 'local_lubadges'));
        } else {
            $statuses = array(
                LUBADGES_PROTO_LIVE => get_string(LUBADGES_PROTO_LIVE, 'local_lubadges'),
                LUBADGES_PROTO_DRAFT => get_string(LUBADGES_PROTO_DRAFT, 'local_lubadges')
            );
            // TODO: Enable editing of users' own draft prototypes. Until then, status is frozen on 'live'.
            $mform->addElement('select', 'status', get_string('status', 'local_lubadges'), $statuses, array('disabled'));
            $mform->addHelpButton('status', 'status', 'local_lubadges');
        }

        if ($action != 'create') {
            $mform->freeze('name, description');
            $mform->hardFreeze('requirements, hint, collection, level');

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
        }

        $mform->addElement('hidden', 'action', $action);
        $mform->setType('action', PARAM_TEXT);

        if ($action == 'add') {
            // Add hidden fields.
            $mform->addElement('hidden', 'prototype', $badge->prototype);
            $mform->setType('prototype', PARAM_INT);

            $this->add_action_buttons(true, get_string('addbutton', 'local_lubadges'));
        } else if ($action == 'create') {
            $this->add_action_buttons(true, get_string('createbutton', 'local_lubadges'));
        } else if ($action == 'details') {
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
        $instancename = $data['name'] . ' [' . $COURSE->shortname . ']';

        // Check for duplicate badge names.
        if ($data['action'] == 'add') {
            $dupinstance = $DB->record_exists_select('badge', 'name = :name AND status != :deleted',
                    array('name' => $instancename, 'deleted' => BADGE_STATUS_ARCHIVED));
        } else if ($data['action'] == 'details') {
            $dupinstance = $DB->record_exists_select('badge', 'name = :name AND id != :badgeid AND status <> :deleted',
                    array('name' => $instancename, 'badgeid' => $data['id'], 'deleted' => BADGE_STATUS_ARCHIVED));
        } else if ($data['action'] == 'create') {
            // For prototypes, need to check both existing prototypes (deleted or otherwise) and external badge names.
            if ($names = $DB->get_fieldset_select('local_lubadges_prototypes', 'name', '')) {
                foreach ($names as $name) {
                    if (trim(strtolower($name)) == trim(strtolower($data['name']))) {
                        $dupprototype = true;
                    }
                }
            }
            // If no duplicate has been found yet, call the external API and check LU badge names.
            if (empty($dupprototype) && ($names = local_lubadges_get_badges('', '', false, true))) {
                if (in_array(trim(strtolower($data['name'])), $names)) {
                    $dupprototype = true;
                }
            }
        }
        if (!empty($dupinstance)) {
            $errors['name'] = get_string('error:dupinstance', 'local_lubadges');
        } else if (!empty($dupprototype)) {
            $errors['name'] = get_string('error:dupproto', 'local_lubadges');
        }

        return $errors;
    }
}

/**
 * Form to edit badge message.
 *
 */
class edit_message_form extends moodleform {
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
