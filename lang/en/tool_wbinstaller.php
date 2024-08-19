<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin strings are defined here.
 *
 * @package     tool_wbinstaller
 * @category    string
 * @copyright   2024 Wunderbyte GmbH <info@wunderbyte.at>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Wunderbyte Installer';
$string['installfromzip'] = 'Install from zip file';
$string['installplugins'] = 'Install plugins';
$string['filedownloadfailed'] = 'Faild to download the zip with the url: {$a}';

// Capabilities.
$string['wbinstaller:caninstall'] = 'Is allowed to install recipes.';
$string['wbinstaller:canexport'] = 'Is allowed to export recipes.';
$string['wbinstallerroledescription'] = 'Users with this role are able to export and install recipes.';
$string['componentdetectfailed'] = 'Users with this role are able to export and install recipes.';
$string['installfailed'] = 'Users with this role are able to export and install recipes.';
$string['success'] = 'Finished successfully';
$string['success_description'] = 'The installation finished without any errors.';
$string['warning'] = 'Finished with errors';
$string['warning_description'] = 'Some errors were encountered during the installation. More information inside the installation feedback.';
$string['error'] = 'Installation aborted';
$string['error_description'] = 'The installation was not successfull.';
$string['exporttitle'] = 'Choose courses that you want to export.';

// Settings.
$string['apitoken'] = 'Github Api-Token';
$string['apitokendesc'] = 'Insert your Github-Token to receive more detailed information about your plugins.';

// Installer classes.
$string['jsonfaildecoding'] = 'Error decoding JSON: {$a}.';
$string['coursesnoshortname'] = 'Could not get the short name of course: {$a}';
$string['coursesduplicateshortname'] = 'Skipped: Course with short name {$a} already exists.';
$string['coursessuccess'] = 'Installed successfully the course: {$a}.';
$string['coursesfailextract'] = 'Failed to copy extracted files to the Moodle backup directory.';
$string['coursesfailprecheck'] = 'Precheck failed for course restore: {$a}.';
$string['customfieldfailupload'] = 'Category could not be uploaded!';
$string['customfieldduplicate'] = 'Customfield shortname already exists!';
$string['customcategoryduplicate'] = 'Customcategory name already exists!';
$string['customfieldsuccesss'] = 'Customfield {$a} was installed successfully.';
$string['customfieldnewfield'] = 'Found the new customfield: {$a}.';
$string['pluginduplicate'] = 'Component {$a->name} is already installed with version {$a->installedversion}. Plugin will be updated to version {$a->componentversion}.';
$string['pluginolder'] = 'Component {$a->name} is already installed with newer version {$a->installedversion}. Your version will not be installed {$a->componentversion}';
$string['pluginnotinstalled'] = 'Component {$a} is not installed.';
$string['pluginfailedinformation'] = 'Failed to retrieve component information.';
$string['questionfilefound'] = 'Found the question file.';
$string['simulationfilefound'] = 'Found the simulation file.';
$string['simulationinstallerfilefound'] = 'Found simulation installer {$a}.';
$string['simulationnoinstallerfilefound'] = 'No installer was found. The file cannot be installed!';
$string['simulationinstallersuccess'] = 'The given installer {$a} was found and used';
$string['installervalidbase'] = 'The base64 string is not valid.';
$string['installerdecodebase'] = 'Failed to decode base64 content or the content is empty.';
$string['installerwritezip'] = 'Failed to write the ZIP file to the plugin directory.';
$string['installerfilenotfound'] = 'The file was not found: {$a}';
$string['installerfilenotreadable'] = 'The file is not readable: {$a}';
$string['installerfailopen'] = 'Failed to open the ZIP file.';

// Vue strings
$string['vueexportselect'] = 'Export Selected';
$string['vueexport'] = 'Export';
$string['vueinstall'] = 'Install';
$string['vuenotfound'] = 'Not found';
$string['vueinstallbtn'] = 'Install Recipe';
$string['vuequestionszip'] = 'Questions in the ZIP:';
$string['vuesimulationzip'] = 'Simulations in the ZIP:';
$string['vuecourseszip'] = 'Courses in the ZIP:';
$string['vuecategories'] = 'Category: ';
$string['vuecustomfieldzip'] = 'Customfield in the ZIP:';
$string['vuewaitingtext'] = 'Please wait while the installation is in progress...';
$string['vuechooserecipe'] = 'Choose Recipe File';
$string['vuewarining'] = 'Warning: ';
$string['vueerror'] = 'Error: ';
$string['vuesuccess'] = 'Success: ';

