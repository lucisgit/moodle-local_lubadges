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
 * LU Badges admin config settings.
 *
 * @package    local_lubadges
 * @copyright  2015 Lancaster University (http://www.lancaster.ac.uk/)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Tony Butler <a.butler4@lancaster.ac.uk>
 */

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_lubadges', get_string('pluginname', 'local_lubadges'));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configtext('local_lubadges/apiendpoint',
            get_string('apiendpoint', 'local_lubadges'), null, null, PARAM_URL));
    $settings->add(new admin_setting_configtext('local_lubadges/apikey',
            get_string('apikey', 'local_lubadges'), null, null, PARAM_TEXT));
    $settings->add(new admin_setting_configtext('local_lubadges/collections',
            get_string('collections', 'local_lubadges'), get_string('collections_desc', 'local_lubadges'), null, PARAM_TEXT));
}
