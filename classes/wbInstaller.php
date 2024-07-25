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

/**
 * Class tool_wbinstaller
 *
 * @package     tool_wbinstaller
 * @author      Jacob Viertel
 * @copyright  2023 Wunderbyte GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class wbInstaller {

    public $dbid;
    public $recipe;
    public $filename;
    public $progress;
    /**
     * Entities constructor.
     */
    public function __construct($recipe, $filename) {
        $this->filename = $filename;
        $this->recipe = $recipe;
        $this->progress = 0;
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
        $filecontent = json_decode($this->recipe);
        $this->save_install_progress();
        foreach ($filecontent as $installer => $content) {
            $installerclass = __NAMESPACE__ . '\\' . $installer . 'Installer';
            if (class_exists($installerclass)) {
                $instance = new $installerclass($content, $this->dbid);
                $instance->execute($content);
            } else {
                $notfoundinstaller[] = $installer;
            }
            $this->update_install_progress('progress');
        }
        return 1;
    }

    /**
     * Get all tests.
     *
     * @return array
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
    }

    /**
     * Get all tests.
     *
     * @return array
     */
    public function update_install_progress($progresstype) {
        global $DB;
        $this->add_step();
        if ($record = $DB->get_record('tool_wbinstaller_install', ['id' => $this->dbid])) {
            $record->$progresstype = $this->progress;
            $record->timemodified = time();
            $DB->update_record('tool_wbinstaller_install', $record);
        } else {
            throw new moodle_exception('recordnotfound', 'tool_wbinstaller', '', $this->dbid);
        }
    }

    /**
     * Get current process.
     *
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

}
