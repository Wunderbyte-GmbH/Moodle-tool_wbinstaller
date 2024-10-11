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
$string['plugincomponentdetectfailed'] = 'Component is unknown.';
$string['classnotfound'] = 'Class for {$a} was not found.';
$string['jsonfaildecoding'] = 'Error decoding JSON: {$a}.';
$string['jsonfailinsufficientpermission'] = 'Error: Insufficient permission to write {$a}.';
$string['jsonfailalreadyexist'] = 'Error: Directory {$a} already exists.';
$string['coursesnoshortname'] = 'Could not get the short name of course: {$a}';
$string['coursesduplicateshortname'] = 'Skipped: Course with short name {$a} already exists.';
$string['coursessuccess'] = 'Successfully installed the course: {$a->courseshortname} on the category level {$a->category}';
$string['coursesfailextract'] = 'Failed to copy extracted files to the Moodle backup directory.';
$string['coursesfailprecheck'] = 'Precheck failed for course restore: {$a}.';
$string['customfieldfailupload'] = 'Category could not be uploaded!';
$string['customfieldduplicate'] = 'Customfield {$a} shortname already exists!';
$string['customcategoryduplicate'] = 'Customcategory name already exists!';
$string['customfieldsuccesss'] = 'Customfield {$a} was installed successfully.';
$string['customfieldnewfield'] = 'Found the new customfield: {$a}.';
$string['pluginduplicate'] = 'Component {$a->name} is already installed with version {$a->installedversion}. Plugin will be updated to version {$a->componentversion}.';
$string['pluginolder'] = 'Component {$a->name} is already installed with newer version {$a->installedversion}. Your version will not be installed {$a->componentversion}';
$string['pluginnotinstalled'] = 'Component {$a} is not installed.';
$string['plugininstalled'] = 'Component {$a} is installed.';
$string['pluginfailedinformation'] = 'Failed to retrieve component information.';
$string['upgradeplugincompleted'] = 'Component {$a} was installed successfully.';
$string['questionfilefound'] = 'Found the question file.';
$string['questionsuccesinstall'] = 'Successfully uploaded the questions.';
$string['simulationfilefound'] = 'Found the simulation file.';
$string['simulationinstallerfilefound'] = 'Found simulation installer {$a}.';
$string['simulationnoinstallerfilefound'] = 'The installer {$a} was not found. If the installer is inside this recipe you need to install the recipe again as the installe is available the second time!';
$string['simulationinstallersuccess'] = 'The given installer {$a} was found and used';
$string['installervalidbase'] = 'The base64 string is not valid.';
$string['installerdecodebase'] = 'Failed to decode base64 content or the content is empty.';
$string['installerwritezip'] = 'Failed to write the ZIP file to the plugin directory.';
$string['installerfilenotfound'] = 'The file was not found: {$a}';
$string['installerfilenotreadable'] = 'The file is not readable: {$a}';
$string['installerfailopen'] = 'Failed to open the ZIP file.';
$string['installerfailfinddir'] = 'Failed to find extracted directory for {$a}';
$string['installersuccessinstalled'] = 'Successfully installed {$a}';
$string['installerfailextract'] = 'Failed to extract {$a}';
$string['customfielderror'] = 'Failed to upload fieldset: {$a}';
$string['translatorsuccess'] = 'The translation of {$a->changingcolumn} from the table {$a->table} was successfull';
$string['translatorerror'] = 'The translation of {$a->changingcolumn} from the table {$a->table} could not be executed';
$string['newcoursefound'] = 'Found the new course: {$a}';
$string['newlocaldatafilefound'] = 'Found new local data: {$a}';
$string['csvnotreadable'] = 'Csv file {$a} not readable';
$string['localdatauploadsuccess'] = 'Csv file {$a} successfully uploaded';
$string['localdatauploadduplicate'] = 'Csv file {$a} was already uploaded and was not uploaded again';
$string['localdatauploadmissingcourse'] = 'Csv file {$a} could not be uploaded as referecned courses were not found';
$string['scalemismatchlocaldata'] = 'The cat scales did not match';
$string['courseidmismatchlocaldata'] = 'The course id did not match';
$string['noadaptivequizfound'] = 'No matching adaptivequiz was found';
$string['oldermoodlebackupversion'] = 'The course-backup is older than the current moodle version {$a}';

// Vue strings.
$string['vueexportselect'] = 'Export Selected';
$string['vueexport'] = 'Export';
$string['vueinstall'] = 'Install';
$string['vuenotfound'] = 'Not found';
$string['vueinstallbtn'] = 'Install Recipe';
$string['vuequestionszip'] = 'Questions in the ZIP:';
$string['vuesimulationzip'] = 'Simulations in the ZIP:';
$string['vuecourseszip'] = 'Courses in the ZIP:';
$string['vuelocaldata'] = 'Local data insiode the ZIP:';
$string['vuecategories'] = 'Category: ';
$string['vuecustomfieldzip'] = 'Customfield in the ZIP:';
$string['vuewaitingtext'] = 'Please wait while the installation is in progress...';
$string['vuechooserecipe'] = 'Choose Recipe File';
$string['vuewarining'] = 'Warning: ';
$string['vueerror'] = 'Error: ';
$string['vuesuccess'] = 'Success: ';
$string['vuestepcountersetp'] = 'Step ';
$string['vuestepcounterof'] = ' of ';
$string['vuenextstep'] = 'All plugins have been installed yet. Before you continue, you can adjust the plugins configuration if needed. Click on next step or if you reenter the page drag and drop the recipe again to continue the installation process';
$string['vuenextstepbtn'] = 'Next step';
$string['vuefinishedrecipe'] = 'The recipe was installed completely.';
