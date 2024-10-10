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

use ZipArchive;

/**
 * Class tool_wbinstaller
 *
 * @package     tool_wbinstaller
 * @author      Jacob Viertel
 * @copyright  2023 Wunderbyte GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class wbCheck {

    /** @var array Content of the recipe. */
    public $recipe;
    /** @var string Name of the recipe. */
    public $filename;
    /** @var array Install errors. */
    public $feedback;
    /** @var array Install errors. */
    public $finished;


    /**
     * Entities constructor.
     * @param array $recipe
     * @param string $filename
     */
    public function __construct($recipe, $filename) {
        $this->recipe = $recipe;
        $this->filename = $filename;
        $this->feedback = [];
        $this->finished = [];
    }

    /**
     * Exceute the installer.
     * @return array
     */
    public function execute() {
        raise_memory_limit(MEMORY_EXTRA);
        $extracted = $this->extract_save_zip_file();
        if (!$extracted) {
            return $this->feedback;
        }
        $this->check_recipe($extracted);
        $this->clean_after_installment();
        return [
            'feedback' => $this->feedback,
            'finished' => $this->finished,
        ];
    }

    /**
     * Extract and save the zipped file.
     * @return int
     *
     */
    public function clean_after_installment() {
        global $CFG;
        $pluginpath = $CFG->tempdir . '/zip/';
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
     * @param string $extracted
     * @return string
     *
     */
    public function check_recipe($extracted) {
        global $CFG;
        $extractpath = $CFG->tempdir . '/zip/precheck/' . str_replace('.zip', '', $this->filename) . '/';
        $jsonstring = file_get_contents($extracted . str_replace('.zip', '', $this->filename) . '/recipe.json');
        $jsonarray = json_decode($jsonstring, true);
        $this->get_current_step($jsonstring, count($jsonarray['steps']));
        foreach ($jsonarray['steps'] as $step) {
            foreach ($step as $steptype) {
                $installerclass = __NAMESPACE__ . '\\' . $steptype . 'Installer';
                if (
                    class_exists($installerclass) &&
                    isset($jsonarray[$steptype])
                ) {
                    $instance = new $installerclass($jsonarray[$steptype]);
                    $instance->check($extractpath);
                    $this->feedback[$steptype] = $instance->get_feedback();
                } else {
                    $this->feedback[$steptype] = get_string('classnotfound', 'tool_wbinstaller', $steptype);
                }
            }
        }
        return true;
    }

    /**
     * Extract and save the zipped file.
     * @return string
     *
     */
    public function extract_save_zip_file() {
        global $CFG;
        $extractpath = null;
        $base64string = str_replace('data:application/zip;base64,', '', $this->recipe);
        if (preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $base64string) === 0) {
            $this->feedback['error'][] =
              get_string('installervalidbase', 'tool_wbinstaller');
            return false;
        }
        $filecontent = base64_decode($base64string, true);
        if ($filecontent === false || empty($filecontent)) {
            $this->feedback['error'][] =
              get_string('installerdecodebase', 'tool_wbinstaller');
            return false;
        }
        $pluginpath = $CFG->tempdir . '/zip/';
        $zipfilepath = $pluginpath . 'precheck_zip';
        if (!is_dir($pluginpath)) {
            mkdir($pluginpath, 0777, true);
        }
        if (file_put_contents($zipfilepath, $filecontent) === false) {
            $this->feedback['error'][] =
              get_string('installerwritezip', 'tool_wbinstaller');
            return false;
        }
        unset($filecontent);
        if (!file_exists($zipfilepath)) {
            $this->feedback['error'][] =
              get_string('installerwritezip', 'tool_wbinstaller', $zipfilepath);
            return false;
        }
        if (!is_readable($zipfilepath)) {
            $this->feedback['error'][] =
              get_string('installerfilenotreadable', 'tool_wbinstaller', $zipfilepath);
            return false;
        }
        $zip = new ZipArchive;
        if ($zip->open($zipfilepath) === true) {
            $extractpath = $pluginpath . 'precheck/';
            if (!is_dir($extractpath)) {
                mkdir($extractpath, 0777, true);
            }
            $zip->extractTo($extractpath);
            $zip->close();
        } else {
            $this->feedback['error'][] =
              get_string('installerfailopen', 'tool_wbinstaller');
        }
        return $extractpath;
    }

    /**
     * Extract and save the zipped file.
     * @param string $jsonstring
     *
     */
    public function get_current_step($jsonstring, $maxstep) {
        global $DB, $USER;
        $sql = "SELECT id, currentstep
            FROM {tool_wbinstaller_install}
            WHERE " . $DB->sql_compare_text('content') . " = " . $DB->sql_compare_text(':content');

        $record = $DB->get_record_sql($sql, ['content' => $jsonstring]);
        if (!$record) {
            $record = new \stdClass();
            $record->userid = $USER->id;
            $record->content = $jsonstring;
            $record->currentstep = 0;
            $record->maxstep = $maxstep;
            $record->timecreated = time();
            $record->timemodified = time();
            $DB->insert_record('tool_wbinstaller_install', $record);
        }
        $this->finished = [
            'status' => false,
            'currentstep' => $record->currentstep,
            'maxstep' => $maxstep,
        ];
    }
}
