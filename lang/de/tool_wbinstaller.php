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
$string['plugincomponentdetectfailed'] = 'Komponente is unbekannt.';
$string['classnotfound'] = 'Klasse für {$a} wurde nicht gefunden.';
$string['jsonfaildecoding'] = 'Fehler beim Dekodieren von JSON: {$a}.';
$string['jsonfailinsufficientpermission'] = 'Fehler: Unzureichende Schreibberechtigung {$a}.';
$string['jsonfailalreadyexist'] = 'Error: Das Verzeichnis {$a} ist bereits vorhanden.';
$string['coursesnoshortname'] = 'Der Kurzname des Kurses konnte nicht abgerufen werden: {$a}';
$string['coursesduplicateshortname'] = 'Übersprungen: Ein Kurs mit dem Kurznamen {$a} existiert bereits.';
$string['coursessuccess'] = 'Der Kurs {$a->courseshortname} wurde erfolgreich auf der Kategorieebene {$a->category} installiert: {$a}.';
$string['coursesfailextract'] = 'Fehler beim Kopieren der extrahierten Dateien in das Moodle-Backup-Verzeichnis.';
$string['coursesfailprecheck'] = 'Precheck für die Kurswiederherstellung fehlgeschlagen: {$a}.';
$string['customfieldfailupload'] = 'Kategorie konnte nicht hochgeladen werden!';
$string['customfieldduplicate'] = 'Customfield-Kurzname {$a} existiert bereits!';
$string['customcategoryduplicate'] = 'Customkategorie Name existiert bereit!';
$string['customfieldsuccesss'] = 'Customfield {$a} wurde erfolgreich installiert.';
$string['customfieldnewfield'] = 'Neues Customfield gefunden: {$a}.';
$string['pluginduplicate'] = 'Komponente {$a->name} ist bereits mit Version installiert mit der Version{$a->version}. Das Plugin wird upgedated zur Version {$a->componentversion}.';
$string['pluginolder'] = 'Komponente {$a->name} ist bereits mit der neueren Version {$a->installedversion} installiert. Ihre version {$a->componentversion} wird nicht installiert.';
$string['pluginnotinstalled'] = 'Komponente {$a} ist nicht installiert.';
$string['plugininstalled'] = 'Komponente {$a} ist installiert.';
$string['pluginfailedinformation'] = 'Fehler beim Abrufen der Komponenteninformationen.';
$string['questionfilefound'] = 'Die Frage-Datei wurde gefunden.';
$string['questionsuccesinstall'] = 'Die Fragen wurden erfolgreich hochgeladen.';
$string['simulationfilefound'] = 'Die Simulationsdatei wurde gefunden.';
$string['simulationinstallerfilefound'] = 'Simulations-Installer {$a} gefunden.';
$string['simulationnoinstallerfilefound'] = 'Der Installer {$a} wurde nicht gefunden. Falls der Installer in diesem Rezept ist, muss das Rezept ein zweites Mal ausgeführt werden damit die Daten importiert werden.';
$string['simulationinstallersuccess'] = 'Der angegebene Installer {$a} wurde gefunden und verwendet.';
$string['installervalidbase'] = 'Der Base64-String ist nicht gültig.';
$string['installerdecodebase'] = 'Fehler beim Dekodieren des Base64-Inhalts oder der Inhalt ist leer.';
$string['installerwritezip'] = 'Fehler beim Schreiben der ZIP-Datei in das Plugin-Verzeichnis.';
$string['installerfilenotfound'] = 'Die Datei wurde nicht gefunden: {$a}';
$string['installerfilenotreadable'] = 'Die Datei ist nicht lesbar: {$a}';
$string['installerfailopen'] = 'Fehler beim Öffnen der ZIP-Datei.';
$string['installerfailfinddir'] = 'Extrahierter Ordner {$a} konnte nicht gefunden werden!';
$string['installersuccessinstalled'] = '{$a} wurde erfolgreich installiert';
$string['installerfailextract'] = '{$a} konnte nicht extrahiert werden!';
$string['customfielderror'] = 'Fieldset konnte nicht hochgeladen werden: {$a}';
$string['translatorsuccess'] = 'Die Änderung von {$a->changingcolumn} aus der Tabelle {$a->table} war erfolgreich';
$string['translatorerror'] = 'Die Änderung von {$a->changingcolumn} aus der Tabelle {$a->table} konnte nicht durchgeführt werden';
$string['newcoursefound'] = 'Der neue Kurs mit dem Namen {$a} wurde gefunden';
$string['newlocaldatafilefound'] = 'FNeue lokale Daten wurden gefunden: {$a}';
$string['csvnotreadable'] = 'Csv Datei {$a} ist nicht lesbar';
$string['localdatauploadsuccess'] = 'Csv Datei {$a} wurde erfolgreich hochgeladen';
$string['localdatauploadduplicate'] = 'Csv Datei {$a} war bereits vorhanden und wurde nicht erneut hochgeladen';
$string['localdatauploadmissingcourse'] = 'Csv Datei {$a} konnte nicht hochgeladen werden, weil referenzierte Kurse nicht gefunden wurden';
$string['scalemismatchlocaldata'] = 'Es wurde keine passende Cat-Skala gefunden';
$string['courseidmismatchlocaldata'] = 'Die Kursids können nicht zugeordnet werden';
$string['noadaptivequizfound'] = 'Kein passendes adaptivequiz gefunden';
$string['oldermoodlebackupversion'] = 'Das Kurs-Backup ist älter als die aktuelle Moodle Version {$a}';

// Vue Strings.
$string['vueexportselect'] = 'Ausgewählte exportieren';
$string['vueexport'] = 'Exportieren';
$string['vueinstall'] = 'Installieren';
$string['vuenotfound'] = 'Nicht gefunden';
$string['vueinstallbtn'] = 'Rezept installieren';
$string['vuequestionszip'] = 'Fragen in der ZIP-Datei:';
$string['vuesimulationzip'] = 'Simulationen in der ZIP-Datei:';
$string['vuecourseszip'] = 'Kurse in der ZIP-Datei:';
$string['vuelocaldata'] = 'Lokale Daten im ZIP:';
$string['vuecategories'] = 'Kategorie: ';
$string['vuecustomfieldzip'] = 'Customfield in der ZIP-Datei:';
$string['vuewaitingtext'] = 'Bitte warten Sie, während die Installation läuft...';
$string['vuechooserecipe'] = 'Rezeptdatei auswählen';
$string['vuewarining'] = 'Warnung: ';
$string['vueerror'] = 'Fehler: ';
$string['vuesuccess'] = 'Erfolg: ';
$string['vuestepcountersetp'] = 'Schritt ';
$string['vuestepcounterof'] = ' von ';
$string['vuerefreshpage'] = 'Lade die Seite neu um die Änderungen zu speichern. Lade anschliessend das Rezept erneut hoch um mit der Installation abzuschliessen.';
$string['vuerefreshpagebtn'] = 'Seite neu laden';
