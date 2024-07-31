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
 * Entities Class to display list of entity records.
 *
 * @package     tool_wbinstaller
 * @author      Jacob Viertel
 * @copyright  2023 Wunderbyte GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_wbinstaller;

use moodle_exception;
use stdClass;
use ZipArchive;

/**
 * Class tool_wbinstaller
 *
 * @package     tool_wbinstaller
 * @author      Jacob Viertel
 * @copyright  2023 Wunderbyte GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class wbInstaller {

    /** @var int ID of the install status db entry. */
    public $dbid;
    /** @var string Content of the recipe. */
    public $recipe;
    /** @var string Filename of the recipe. */
    public $filename;
    /** @var int Install progress. */
    public $progress;
    /** @var array Install errors. */
    public $errors;


    /**
     * Entities constructor.
     * @param string $recipe
     * @param string $filename
     */
    public function __construct($recipe, $filename) {
        $this->filename = $filename;
        $this->recipe = $recipe;
        $this->progress = 0;
        $this->errors = [];
    }

    /**
     * Get all tests.
     *
     * @return array
     */
    public function add_step() {
        $this->progress++;
    }

    /**
     * Exceute the installer.
     * @return array
     */
    public function execute() {
        $notfoundinstaller = [];
        $extracterrors = $this->extract_save_zip_file();
        if ($extracterrors) {
            $this->errors['wbinstaller'] = $extracterrors;
            return $this->errors;
        }
        $this->save_install_progress();
        $extractpath = __DIR__ . '/zip/extracted/' . str_replace('.zip', '', $this->filename);
        $files = scandir($extractpath);
        foreach ($files as $file) {
            if ($file[0] !== '.') {
                $parts = explode('.', $file);
                $installerclass = __NAMESPACE__ . '\\' . $parts[0] . 'Installer';
                if (class_exists($installerclass)) {
                    $instance = new $installerclass(
                      $extractpath . '/' . $parts[0],
                      $this->dbid
                    );
                    $instance->execute();
                    $this->errors[$parts[0]] = $instance->get_errors() ? implode(',', $instance->get_errors()) : '';
                } else {
                    $notfoundinstaller[] = $parts[0];
                }
                $this->update_install_progress('progress');
            }
        }
        $this->clean_after_installment();
        $this->update_install_progress('progress', 1);
        return $this->errors;
    }

    /**
     * Extract and save the zipped file.
     * @return int
     *
     */
    public function clean_after_installment() {
        $pluginpath = __DIR__ . '/zip/';
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($pluginpath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $path = $item->getRealPath();
            if ($item->isDir()) {
                rmdir($path);
            } else {
                unlink($path);
            }
        }
        return rmdir($pluginpath);
    }

    /**
     * Extract and save the zipped file.
     * @return array
     *
     */
    public function extract_save_zip_file() {
        $base64string = str_replace('data:application/zip;base64,', '', $this->recipe);
        $filecontent = base64_decode($base64string);

        // Check if the decoded content is valid.
        if ($filecontent === false || empty($filecontent)) {
            return "Failed to decode base64 content or the content is empty.";
        }
        // Define the path within the plugin's directory.
        $pluginpath = __DIR__ . '/zip/';
        $zipfilepath = $pluginpath . $this->filename;

        // Ensure the plugin directory exists.
        if (!is_dir($pluginpath)) {
            mkdir($pluginpath, 0777, true);
        }

        // Save the decoded data to a file within the plugin directory.
        if (file_put_contents($zipfilepath, $filecontent) === false) {
            return "Failed to write the ZIP file to the plugin directory.";
        }
        // Verify the file path and permissions.
        if (!file_exists($zipfilepath)) {
            return "The file does not exist: $zipfilepath";
        }

        if (!is_readable($zipfilepath)) {
            return "The file is not readable: $zipfilepath";
        }

        // Initialize the ZipArchive class.
        $zip = new ZipArchive;
        if ($zip->open($zipfilepath) === true) {
            $extractpath = $pluginpath . 'extracted/';

            if (!is_dir($extractpath)) {
                mkdir($extractpath, 0777, true);
            }
            $zip->extractTo($extractpath);
            $zip->close();
        } else {
            return "Failed to open the ZIP file.";
        }
        return false;
    }

    /**
     * Get all tests.
     *
     * @return int
     */
    public function save_install_progress() {
        global $DB, $USER;
        $record = new stdClass();
        $record->userid = $USER->id;
        $record->filename = $this->filename;
        $record->content = $this->recipe;
        $record->progress = $this->progress;
        $record->subprogress = 0;
        $record->status = 0;
        $record->timecreated = time();
        $record->timemodified = time();
        $this->dbid = $DB->insert_record('tool_wbinstaller_install', $record);
        return 1;
    }

    /**
     * Get all tests.
     *
     * @param string $progresstype
     * @param bool|null $finished Optional parameter.
     * @return int
     */
    public function update_install_progress($progresstype, $status = 0) {
        global $DB;
        if (!$status) {
            $this->add_step();
        }
        if ($record = $DB->get_record('tool_wbinstaller_install', ['id' => $this->dbid])) {
            $record->$progresstype = $this->progress;
            $record->timemodified = time();
            $record->status = $status;
            $DB->update_record('tool_wbinstaller_install', $record);
        } else {
            throw new moodle_exception('recordnotfound', 'tool_wbinstaller', '', $this->dbid);
        }
        return 1;
    }

    /**
     * Get current process.
     *
     * @param string $filename
     * @return array
     */
    public static function get_install_progress($filename) {
        global $DB;
        $sql = "SELECT progress, subprogress FROM {tool_wbinstaller_install} ";
        $where = "WHERE filename = ? ORDER BY timecreated DESC LIMIT 1";
        $record = $DB->get_record_sql($sql . $where, [$filename]);
        if ($record) {
            return $record;
        } else {
            throw new moodle_exception('recordnotfound', 'tool_wbinstaller', '', $filename);
        }
    }

    /**
     * Returns all errors.
     * @return array
     */
    public function get_errors() {
        return $this->errors;
    }

}
