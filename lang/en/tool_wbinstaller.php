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
$string['filedownloadfailed'] = 'filedownloadfailed';

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
