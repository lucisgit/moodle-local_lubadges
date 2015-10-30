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
 * English language strings for LU Badges.
 *
 * @package    local_lubadges
 * @copyright  2015 Lancaster University (http://www.lancaster.ac.uk/)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Tony Butler <a.butler4@lancaster.ac.uk>
 */

$string['add'] = 'Add an existing LU Badge instance';
$string['addbadge'] = 'Add an LU badge instance';
$string['addbutton'] = 'Add badge instance';
$string['addtext'] = 'You may add a Moodle instance of an existing LU Badge prototype by selecting one from the menu above.';
$string['apiendpoint'] = 'API endpoint';
$string['apikey'] = 'API key';
$string['badgedetails'] = 'Badge prototype details';
$string['bronze'] = 'Bronze';
$string['collection'] = 'Collection';
$string['collection_help'] = 'The collection that the new badge should belong to.';
$string['collections'] = 'Allowed collections';
$string['collections_desc'] = 'Comma-separated list of collection IDs containing badges which could be awarded via Moodle (leave blank to allow badges from any collection to be awarded).';
$string['create'] = 'Create a new LU Badge prototype';
$string['createbadge'] = 'Create an LU badge prototype';
$string['createbutton'] = 'Create badge prototype';
$string['createsuccess'] = 'New LU Badge "{$a}" created successfully. Generating prototype ...';
$string['createtext'] = 'If you can\'t find a suitable prototype above you can create a completely new badge, which will be created in the external LU Badges system before being made available here as a prototype: {$a}';
$string['description'] = 'Description';
$string['description_help'] = 'The message displayed to the user after a badge is earned. This should describe what they had to do to earn it, e.g. "You borrowed 25 books from the library!".';
$string['draft'] = 'Draft';
$string['error:dupinstance'] = 'Another instance of this LU badge already exists within this course or site context.';
$string['error:dupproto'] = 'An LU badge with this name already exists (although it may not be available here as a prototype).';
$string['errorwhiledownload'] = 'An error occurred while downloading the badge image: {$a}';
$string['gold'] = 'Gold';
$string['hint'] = 'Hint';
$string['hint_help'] = 'A hint displayed to potential earners suggesting how they could earn the badge.';
$string['issuername'] = 'Lancaster University';
$string['level'] = 'Level';
$string['level_help'] = 'The level of difficulty required to achieve the badge.';
$string['live'] = 'Live';
$string['loadbadgedetails'] = 'Load badge details';
$string['lubadges:addbadge'] = 'Add an instance of an LU badge';
$string['lubadges:createbadge'] = 'Create a new LU badge prototype';
$string['managebadges'] = 'Manage LU badges';
$string['name'] = 'Name';
$string['name_help'] = 'The name of an LU badge prototype must be unique. Please choose it carefully.';
$string['nobadges'] = 'No LU badges available';
$string['points'] = 'Points';
$string['platinum'] = 'Platinum';
$string['pluginname'] = 'Lancaster University Badges';
$string['requirements'] = 'Requirements';
$string['requirements_help'] = 'A summary of the criteria required to earn the badge.';
$string['selectbadge'] = 'Select an LU Badge prototype';
$string['silver'] = 'Silver';
$string['status'] = 'Status';
$string['status_help'] = 'Leave this set to the default status of \'live\' if you wish to add an instance of this badge immediately after creating it. Moodle badge instances can only be created from LU prototypes with a \'live\' status (but only \'draft\' prototypes can be edited).<br><br>Note: Currently Moodle can only create \'live\' badges in the external LU Badges system, so further editing will not be possible.';
$string['taskissue'] = 'Issue pending LU badges';
$string['taskupdate'] = 'Update list of available LU badges';
