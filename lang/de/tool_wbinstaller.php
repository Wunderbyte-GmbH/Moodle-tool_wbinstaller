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
$string['installfromzip'] = 'Aus ZIP-Datei installieren';
$string['installplugins'] = 'Plugins installieren';
$string['filedownloadfailed'] = 'Fehler beim Herunterladen der ZIP-Datei mit der URL: {$a}';

// Berechtigungen.
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
$string['exporttitle'] = 'Wählen Sie die Kurse aus, die Sie exportieren möchten.';

// Einstellungen.
$string['apitoken'] = 'Github API-Token';
$string['apitokendesc'] = 'Geben Sie Ihren Github-Token ein, um detailliertere Informationen zu Ihren Plugins zu erhalten.';

// Installer-Klassen.
$string['jsonfaildecoding'] = 'Fehler beim Dekodieren von JSON: {$a}.';
$string['coursesnoshortname'] = 'Der Kurzname des Kurses konnte nicht abgerufen werden: {$a}';
$string['coursesduplicateshortname'] = 'Übersprungen: Ein Kurs mit dem Kurznamen {$a} existiert bereits.';
$string['coursessuccess'] = 'Der Kurs wurde erfolgreich installiert: {$a}.';
$string['coursesfailextract'] = 'Fehler beim Kopieren der extrahierten Dateien in das Moodle-Backup-Verzeichnis.';
$string['coursesfailprecheck'] = 'Precheck für die Kurswiederherstellung fehlgeschlagen: {$a}.';
$string['customfieldfailupload'] = 'Kategorie konnte nicht hochgeladen werden!';
$string['customfieldduplicate'] = 'Customfield-Kurzname existiert bereits!';
$string['customcategoryduplicate'] = 'Customkategorie Name existiert bereit!';
$string['customfieldsuccesss'] = 'Customfield {$a} wurde erfolgreich installiert.';
$string['customfieldnewfield'] = 'Neues Customfield gefunden: {$a}.';
$string['pluginduplicate'] = 'Komponente {$a->name} ist bereits mit Version installiert mit der Version{$a->version}. Das Plugin wird upgedated zur Version {$a->componentversion}.';
$string['pluginolder'] = 'Komponente {$a->name} ist bereits mit der neueren Version {$a->installedversion} installiert. Ihre version {$a->componentversion} wird nicht installiert.';
$string['pluginnotinstalled'] = 'Komponente {$a} ist nicht installiert.';
$string['pluginfailedinformation'] = 'Fehler beim Abrufen der Komponenteninformationen.';
$string['questionfilefound'] = 'Die Frage-Datei wurde gefunden.';
$string['simulationfilefound'] = 'Die Simulationsdatei wurde gefunden.';
$string['simulationinstallerfilefound'] = 'Simulations-Installer {$a} gefunden.';
$string['simulationnoinstallerfilefound'] = 'Es wurde kein Installer gefunden. Die Datei kann nicht installiert werden!';
$string['simulationinstallersuccess'] = 'Der angegebene Installer {$a} wurde gefunden und verwendet.';
$string['installervalidbase'] = 'Der Base64-String ist nicht gültig.';
$string['installerdecodebase'] = 'Fehler beim Dekodieren des Base64-Inhalts oder der Inhalt ist leer.';
$string['installerwritezip'] = 'Fehler beim Schreiben der ZIP-Datei in das Plugin-Verzeichnis.';
$string['installerfilenotfound'] = 'Die Datei wurde nicht gefunden: {$a}';
$string['installerfilenotreadable'] = 'Die Datei ist nicht lesbar: {$a}';
$string['installerfailopen'] = 'Fehler beim Öffnen der ZIP-Datei.';

// Vue Strings.
$string['vueexportselect'] = 'Ausgewählte exportieren';
$string['vueexport'] = 'Exportieren';
$string['vueinstall'] = 'Installieren';
$string['vuenotfound'] = 'Nicht gefunden';
$string['vueinstallbtn'] = 'Rezept installieren';
$string['vuequestionszip'] = 'Fragen in der ZIP-Datei:';
$string['vuesimulationzip'] = 'Simulationen in der ZIP-Datei:';
$string['vuecourseszip'] = 'Kurse in der ZIP-Datei:';
$string['vuecategories'] = 'Kategorie: ';
$string['vuecustomfieldzip'] = 'Customfield in der ZIP-Datei:';
$string['vuewaitingtext'] = 'Bitte warten Sie, während die Installation läuft...';
$string['vuechooserecipe'] = 'Rezeptdatei auswählen';
$string['vuewarining'] = 'Warnung: ';
$string['vueerror'] = 'Fehler: ';
$string['vuesuccess'] = 'Erfolg: ';
