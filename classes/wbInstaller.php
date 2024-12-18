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
    public $matchingids;
    /** @var mixed Parent data */
    public $parent;
    /** @var wbHelper Install errors. */
    public $wbhelper;

    /**
     * Entities constructor.
     * @param string $recipe
     * @param string $filename
     * @param string $optionalplugins
     */
    public function __construct($recipe, $filename = null, $optionalplugins = null) {
        $this->wbhelper = new wbHelper();
        $this->filename = $filename;
        $this->recipe = $recipe;
        $this->progress = 0;
        $this->feedback = [];
        $this->optionalplugins = json_decode($optionalplugins);
        $this->status = 0;
        $this->upgraderunning = 0;
        $this->matchingids = [];
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
     * @param \tool_wbinstaller\wbCheck $parent
     * @return array
     */
    public function execute($extractpath, $parent = null) {
        $this->wbhelper->clean_installment_directory();
        raise_memory_limit(MEMORY_EXTRA);
        $extracted = $this->wbhelper->extract_save_zip_file(
            $this->recipe,
            $this->feedback,
            $this->filename,
            'extracted/'
        );
        if (isset($this->feedback['wbinstaller']['error'])) {
            $this->set_status(2);
        }
        if (!$extracted) {
            return [
                'feedback' => $this->feedback,
                'finished' => [
                  'status' => false,
                  'currentstep' => 0,
                  'maxstep' => 0,
                ],
            ];
        }
        $response = $this->execute_recipe($extracted);
        $this->wbhelper->clean_installment_directory();
        return $response;
    }

    /**
     * Extract and save the zipped file.
     * @param string $extracted
     * @return array
     *
     */
    public function execute_recipe($extracted) {
        $directorydata = $this->wbhelper->get_directory_data('/zip/extracted/');
        $currentstep = $this->get_current_step(
            $directorydata['jsonstring'],
            count($directorydata['jsoncontent']['steps'])
        );
        foreach ($directorydata['jsoncontent']['steps'][$currentstep] as $steptype) {
            $installerclass = __NAMESPACE__ . '\\' . $steptype . 'Installer';
            if (
                class_exists($installerclass) &&
                isset($directorydata['jsoncontent'][$steptype])
            ) {
                $instance = new $installerclass($directorydata['jsoncontent'][$steptype]);
                if ($instance !== null) {
                    $instance->execute($directorydata['extractpath'], $this);
                    if ($instance->upgraderunning != 0) {
                        $this->upgraderunning = $instance->upgraderunning;
                    }
                    $this->feedback[$steptype] = $instance->get_feedback();
                    $this->matchingids[$steptype] = $instance->get_matchingids();
                    $this->set_status($instance->get_status());
                } else {
                    $this->feedback[$steptype]['needed'][$steptype]['error'][] =
                        get_string('classnotfound', 'tool_wbinstaller', 'TESTING');
                }
            } else {
                $this->feedback[$steptype]['needed'][$steptype]['error'][] =
                    get_string('classnotfound', 'tool_wbinstaller', $steptype);
            }
        }
        $finished = $this->set_current_step($directorydata['jsonstring']);

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
     * @param int $maxstep
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
     * Check if course already exists.
     * @return array
     */
    public function get_matchingids() {
        return $this->matchingids;
    }

    /**
     * Returns all errors.
     * @param string $status
     */
    public function set_status($status) {
        if ($this->status < $status) {
            $this->status = $status;
        }
    }
    /**
     * Returns all errors.
     * @return int
     */
    public function get_status() {
        return $this->status;
    }
}
