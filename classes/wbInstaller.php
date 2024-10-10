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
    /** @var mixed Content of the recipe. */
    public $recipe;
    /** @var string Filename of the recipe. */
    public $filename;
    /** @var int Install progress. */
    public $progress;
    /** @var array Install errors. */
    public $feedback;
    /** @var array Install errors. */
    public $optionalplugins;
    /** @var int Install status. */
    public $status;
    /** @var int Upgrade time. */
    public $upgraderunning;
    /** @var array Matching the course ids from the old => new. */
    public $matchingcourseids;


    /**
     * Entities constructor.
     * @param string $recipe
     * @param string $filename
     * @param string $optionalplugins
     */
    public function __construct($recipe, $filename=null, $optionalplugins=null) {
        $this->filename = $filename;
        $this->recipe = $recipe;
        $this->progress = 0;
        $this->feedback = [];
        $this->optionalplugins = json_decode($optionalplugins);
        $this->status = 0;
        $this->upgraderunning = 0;
        $this->matchingcourseids = [];
    }

    /**
     * Get all tests.
     *
     */
    public function add_step() {
        $this->progress++;
    }

    /**
     * Exceute the installer.
     * @param string $extractpath
     * @return array
     */
    public function execute($extractpath) {
        raise_memory_limit(MEMORY_EXTRA);
        $extracted = $this->extract_save_zip_file();
        if (!$extracted) {
            return $this->feedback;
        }
        $response = $this->execute_recipe($extracted);
        $this->clean_after_installment();
        return $response;
    }

    /**
     * Extract and save the zipped file.
     * @param string $extracted
     * @return array
     *
     */
    public function execute_recipe($extracted) {
        $recipefolder = $extracted . str_replace('.zip', '', $this->filename) . '/';
        $jsonstring = file_get_contents($recipefolder . 'recipe.json');
        $jsonarray = json_decode($jsonstring, true);
        $currentstep = $this->get_current_step($jsonstring, count($jsonarray['steps']));
        foreach ($jsonarray['steps'][$currentstep] as $step) {
            $installerclass = __NAMESPACE__ . '\\' . $step . 'Installer';
            if (
                class_exists($installerclass) &&
                isset($jsonarray[$step])
            ) {
                if ($step == 'plugins') {
                    $instance = new $installerclass(
                        $jsonarray[$step],
                        $this->dbid,
                        $this->optionalplugins
                    );
                } else {
                    $instance = new $installerclass(
                        $jsonarray[$step],
                        $this->dbid
                    );
                }
                if ($step == 'localdata') {
                    $instance->set_matchingcourseids($this->matchingcourseids);
                }
                $instance->execute($recipefolder);
                if ($step == 'courses') {
                    $this->matchingcourseids = $instance->get_matchingcourseids();
                }
                if ($instance->upgraderunning != 0) {
                    $this->upgraderunning = $instance->upgraderunning;
                }
                $this->feedback[$step] = $instance->get_feedback();
                $this->set_status($instance->get_status());
            } else {
                $this->feedback[$step] = get_string('classnotfound', 'tool_wbinstaller', $step);
            }
        }
        $finished = $this->set_current_step($jsonstring);

        return [
            'feedback' => $this->feedback,
            'status' => $this->status,
            'finished' => $finished,
        ];
    }

    /**
     * Extract and save the zipped file.
     * @param string $jsonstring
     * @return array
     *
     */
    public function set_current_step($jsonstring): array {
        global $DB, $USER;
        $sql = "SELECT id, currentstep, maxstep
            FROM {tool_wbinstaller_install}
            WHERE " . $DB->sql_compare_text('content') . " = " . $DB->sql_compare_text(':content');

        $record = $DB->get_record_sql($sql, ['content' => $jsonstring]);
        $record->currentstep += 1;
        $finished = [
            'status' => false,
            'currentstep' => $record->currentstep,
            'maxstep' => $record->maxstep,
        ];
        if ($record->currentstep == $record->maxstep) {
            $DB->delete_records('tool_wbinstaller_install', ['id' => $record->id]);
            $finished['status'] = true;
        } else {
            $DB->update_record('tool_wbinstaller_install', $record);
        }
        return $finished;
    }

    /**
     * Extract and save the zipped file.
     * @param string $jsonstring
     * @return int
     *
     */
    public function get_current_step($jsonstring, $maxstep): int {
        global $DB, $USER;
        $sql = "SELECT id, currentstep
            FROM {tool_wbinstaller_install}
            WHERE " . $DB->sql_compare_text('content') . " = " . $DB->sql_compare_text(':content');

        $record = $DB->get_record_sql($sql, ['content' => $jsonstring]);
        if ($record) {
            return $record->currentstep;
        }
        $newrecord = new stdClass();
        $newrecord->userid = $USER->id;
        $newrecord->content = $jsonstring;
        $newrecord->currentstep = 0;
        $newrecord->maxstep = $maxstep;
        $newrecord->timecreated = time();
        $newrecord->timemodified = time();

        $DB->insert_record('tool_wbinstaller_install', $newrecord);
        return 0;
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
     * @return string
     *
     */
    public function extract_save_zip_file() {
        global $CFG;
        $extractpath = null;
        $base64string = str_replace('data:application/zip;base64,', '', $this->recipe);
        if (preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $base64string) === 0) {
            $this->feedback['wbinstaller']['error'][] = ["The base64 string is not valid."];
            $this->set_status(2);
            return false;
        }
        $filecontent = base64_decode($base64string, true);

        if ($filecontent === false || empty($filecontent)) {
            $this->feedback['wbinstaller']['error'][] =
              get_string('installervalidbase', 'tool_wbinstaller');
            $this->set_status(2);
            return false;
        }
        $pluginpath = $CFG->tempdir . '/zip/';
        $zipfilepath = $pluginpath . $this->filename;
        if (!is_dir($pluginpath)) {
            mkdir($pluginpath, 0777, true);
        }
        if (file_put_contents($zipfilepath, $filecontent) === false) {
            $this->feedback['wbinstaller']['error'][] =
              get_string('installerwritezip', 'tool_wbinstaller');
            $this->set_status(2);
            return false;
        }
        unset($filecontent);
        if (!file_exists($zipfilepath)) {
            $this->feedback['wbinstaller']['error'][] =
              get_string('installerfilenotfound', 'tool_wbinstaller', $zipfilepath);
            $this->set_status(2);
            return false;
        }
        if (!is_readable($zipfilepath)) {
            $this->feedback['wbinstaller']['error'][] =
              get_string('installerfilenotreadable', 'tool_wbinstaller', $zipfilepath);
            $this->set_status(2);
            return false;
        }
        $zip = new ZipArchive;
        if ($zip->open($zipfilepath) === true) {
            $extractpath = $pluginpath . 'extracted/';

            if (!is_dir($extractpath)) {
                mkdir($extractpath, 0777, true);
            }
            $zip->extractTo($extractpath);
            $zip->close();
        } else {
            return get_string('installerfailopen', 'tool_wbinstaller');
        }
        return $extractpath;
    }

    /**
     * Save progress DB.
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
     * Update install progress.
     *
     * @param string $progresstype
     * @param bool|null $status
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
    public function get_feedback() {
        return $this->feedback;
    }

    /**
     * Returns all errors.
     * @param string $status
     * @return array
     */
    public function set_status($status) {
        if ($this->status < $status) {
            $this->status = $status;
        }
    }
    /**
     * Returns all errors.
     * @return array
     */
    public function get_status() {
        $this->status;
    }

}
