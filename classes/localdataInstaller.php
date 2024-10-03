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

use local_catquiz\data\dataapi;
use stdClass;

require_once(__DIR__.'/../../../../config.php');
require_once(__DIR__.'/../../../../lib/setup.php');
global $CFG;
require_once($CFG->libdir.'/filelib.php');
require_once($CFG->libdir.'/moodlelib.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->dirroot.'/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot.'/backup/util/includes/restore_includes.php');
require_login();

/**
 * Class tool_wbinstaller
 *
 * @package     tool_wbinstaller
 * @author      Jacob Viertel
 * @copyright  2023 Wunderbyte GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class localdataInstaller extends wbInstaller {

    /** @var bool Check if data will be uploaded */
    public $uploaddata;

    /**
     * Entities constructor.
     * @param mixed $recipe
     * @param int $dbid
     */
    public function __construct($recipe, $dbid=null) {
        $this->dbid = $dbid;
        $this->recipe = $recipe;
        $this->progress = 0;
        $this->uploaddata = true;
    }

    /**
     * Exceute the installer.
     * @param string $extractpath
     * @return string
     */
    public function execute($extractpath) {
        $coursespath = $extractpath . $this->recipe['path'];
        foreach (glob("$coursespath/*") as $coursefile) {
            $this->upload_csv_file($coursefile);
        }
        return '1';
    }

    /**
     * Exceute the installer.
     * @param string $extractpath
     * @return string
     */
    public function check($extractpath) {
        $coursespath = $extractpath . $this->recipe['path'];
        foreach (glob("$coursespath/*") as $coursefile) {
            $filenameproperties = basename($coursefile);
            $fileinfo = pathinfo($filenameproperties, PATHINFO_FILENAME);
            $this->feedback['needed']['local_data']['success'][] =
                get_string('newlocaldatafilefound', 'tool_wbinstaller', $fileinfo);
        }
        return '1';
    }

    /**
     * Instal a single course.
     * @param string $coursefile
     * @return int
     */
    private function upload_csv_file($coursefile) {
        global $DB;

        $filenameproperties = basename($coursefile);
        $fileinfo = pathinfo($filenameproperties, PATHINFO_FILENAME);

        // Ensure the file is readable and accessible.
        if (!is_readable($coursefile)) {
            $this->feedback['needed']['local_data']['error'][] =
                get_string('csvnotreadable', 'tool_wbinstaller', $fileinfo);
        } else {
            $filecontents = file_get_contents($coursefile);
            $jsondata = json_decode($filecontents, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->feedback['needed']['local_data']['error'][] =
                    get_string('jsoninvalid', 'tool_wbinstaller', $fileinfo);
                return 0;
            }
            foreach ($jsondata as $row) {
                $this->uploaddata = true;
                $record = new stdClass();
                $newdata = null;
                if (isset($this->matchingcourseids[$row['courseid']])) {
                    $newdata = $DB->get_record_sql(
                      $this->recipe['translator']['sql'],
                      [
                        'id' => $this->matchingcourseids[$row['courseid']],
                      ]
                    );
                }

                if (isset($this->recipe['translator']['catscalename'])) {
                    $parentscaleid = $DB->get_record('local_catquiz_catscales',
                        ['name' => $this->recipe['translator']['catscalename']], 'id');
                    $newdata->catscaleid = $parentscaleid->id;
                }

                foreach ($row as $key => $rowcol) {
                    if (isset($this->recipe['translator']['changingcolumn'][$key])) {
                        if (isset($newdata->$key)) {
                            $record->$key = $newdata->$key;
                        } else if ($this->recipe['translator']['changingcolumn'][$key]['nested']) {
                            $record->$key = $this->update_nested_json($rowcol, $newdata->catscaleid);
                        }
                    } else if ($key != 'id') {
                        $record->$key = $rowcol;
                    }
                }
                if ($this->uploaddata) {
                    $DB->insert_record($fileinfo, $record);
                }
            }
            $this->feedback['needed']['local_data']['success'][] =
                get_string('localdatauploadsuccess', 'tool_wbinstaller', $fileinfo);
        }
        return 1;
    }

    /**
     * Check if course already exists.
     * @param array $matchingcourseids
     */
    public function set_matchingcourseids($matchingcourseids) {
        $this->matchingcourseids = $matchingcourseids;
    }

    /**
     * Check if course already exists.
     * @param string $json
     * @param string $sacleid
     * @return array
     */
    public function update_nested_json($json, $sacleid) {
        $json = json_decode($json, true);
        $translationsclaeids = $this->get_scale_matcher($json, $sacleid);
        foreach ($json as $key => $value) {
            if ($key == 'catquiz_catscales') {
                $json[$key] = $sacleid;
            } else if (preg_match('/catquiz_courses_(\d+)_(\d+)/', $key, $matches)) {
                $oldid = (int)$matches[1];
                $suffix = $matches[2];
                if (isset($translationsclaeids[$oldid])) {
                    $newid = $translationsclaeids[$oldid];
                    $newkey = "catquiz_courses_{$newid}_{$suffix}";
                    $newdata[$newkey] = $this->course_matching($value);
                } else {
                    $newdata[$key] = $value;
                }
                unset($json[$key]);
            }
        }
        $json = array_merge($json, $newdata);
        return json_encode($json);
    }

    /**
     * Check if course already exists.
     * @param array $values
     * @return array
     */
    public function course_matching($values) {
        $courseids = [];
        foreach ($values as $value) {
            if (isset($this->matchingcourseids[$value])) {
                $courseids[] = $this->matchingcourseids[$value];
            } else {
                $this->uploaddata = false;
                $this->feedback['needed']['local_data']['error'][] =
                    get_string('courseidmismatchlocaldata', 'tool_wbinstaller');
            }
        }

        return $courseids;
    }

    /**
     * Check if course already exists.
     * @param object $json
     * @param string $sacleid
     * @return array
     */
    public function get_scale_matcher($json, $sacleid) {
        $newscales = array_keys(dataapi::get_catscale_and_children($sacleid, true));
        $oldscales = [];

        foreach ($json as $key => $value) {
            if (preg_match('/catquiz_courses_(\d+)_\d+/', $key, $matches)) {
                $oldscales[(int)$matches[1]] = (int)$matches[1];
            }
        }
        sort($oldscales);
        $scaledifference = $newscales[0] - $oldscales[0];
        $matcher = [];
        foreach ($oldscales as $oldscale) {
            if (in_array($oldscale + $scaledifference, $newscales)) {
                $matcher[$oldscale] = $oldscale + $scaledifference;
            } else {
                $this->uploaddata = false;
                $this->feedback['needed']['local_data']['error'][] =
                    get_string('scalemismatchlocaldata', 'tool_wbinstaller');
                return 0;
            }
        }
        return $matcher;
    }
}
