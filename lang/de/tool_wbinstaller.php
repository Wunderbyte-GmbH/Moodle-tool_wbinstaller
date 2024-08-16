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
$string['wbinstaller:caninstall'] = 'Darf Rezepte installieren.';
$string['wbinstaller:canexport'] = 'Darf Rezepte exportieren.';
$string['wbinstallerroledescription'] = 'Benutzer mit dieser Rolle können Rezepte exportieren und installieren.';
$string['componentdetectfailed'] = 'Benutzer mit dieser Rolle können Rezepte exportieren und installieren.';
$string['installfailed'] = 'Benutzer mit dieser Rolle können Rezepte exportieren und installieren.';
$string['success'] = 'Erfolgreich abgeschlossen';
$string['success_description'] = 'Die Installation wurde ohne Fehler abgeschlossen.';
$string['warning'] = 'Mit Fehlern abgeschlossen';
$string['warning_description'] = 'Während der Installation sind einige Fehler aufgetreten. Weitere Informationen finden Sie im Installationsfeedback.';
$string['error'] = 'Installation abgebrochen';
$string['error_description'] = 'Die Installation war nicht erfolgreich.';
$string['exporttitle'] = 'Wähle aus welche Kurse exportiert werden sollen.';

// Settings.
$string['apitoken'] = 'Github Api-Token';
$string['apitokendesc'] = 'Gebe deinen Github-Token ein um mehr Information über die Plugins zu bekommen.';

