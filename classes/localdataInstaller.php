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
    /** @var \tool_wbinstaller\wbCheck Parent matching ids. */
    public $parent;

    /**
     * Entities constructor.
     * @param mixed $recipe
     */
    public function __construct($recipe) {
        $this->recipe = $recipe;
        $this->progress = 0;
        $this->uploaddata = true;
    }

    /**
     * Exceute the installer.
     * @param string $extractpath
     * @param \tool_wbinstaller\wbCheck $parent
     * @return string
     */
    public function execute($extractpath, $parent = null) {
        $this->parent = $parent;
        $coursespath = $extractpath . $this->recipe['path'];
        foreach (glob("$coursespath/*") as $coursefile) {
            try {
                $this->upload_csv_file($coursefile);
            } catch (\Exception $e) {
                $this->feedback['needed']['local_data']['error'][] =
                  get_string('jsoninvalid', 'tool_wbinstaller', $coursefile);
            }
        }
        return '1';
    }

    /**
     * Exceute the installer.
     * @param string $extractpath
     * @param \tool_wbinstaller\wbCheck $parent
     * @return string
     */
    public function check($extractpath, $parent) {
        $this->parent = $parent;
        $coursespath = $extractpath . $this->recipe['path'];
        foreach (glob("$coursespath/*") as $coursefile) {
            $filenameproperties = basename($coursefile);
            $fileinfo = pathinfo($filenameproperties, PATHINFO_FILENAME);
            $jsondata = file_get_contents($coursefile);
            $decodeddata = json_decode($jsondata, true);
            $this->process_nested_json($decodeddata);
            $this->feedback['needed']['local_data']['success'][] =
                get_string('newlocaldatafilefound', 'tool_wbinstaller', $fileinfo);
        }
        return '1';
    }

    /**
     * Recursively process nested JSON objects.
     * @param array $entries
     */
    protected function process_nested_json($entries) {
        foreach ($entries as $entry) {
            if (isset($entry['id'])) {
                $this->matchingids['testid'][$entry['id']] = $entry['id'];
            }
            if (isset($entry['componentid'])) {
                $this->matchingids['componentid'][$entry['componentid']] = $entry['componentid'];
                $this->matchingids['quizid'][$entry['componentid']] = $entry['componentid'];
            }
            if (isset($entry['courseid'])) {
                $this->matchingids['testid_courseid'][$entry['courseid']] = $entry['courseid'];
            }
            if (isset($entry['catscaleid'])) {
                $this->matchingids['scales'][$entry['catscaleid']] = $entry['catscaleid'];
            }
        }
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
                $newdata = new stdClass();
                if (isset($this->parent->matchingids['courses']['courses'][$row['courseid']])) {
                    $newdata = $DB->get_record_sql(
                        $this->recipe['translator']['sql'],
                        [
                          'id' => $this->parent->matchingids['courses']['courses'][$row['courseid']],
                        ]
                    );
                    if (!$newdata) {
                        $this->feedback['needed']['local_data']['error'][] =
                            get_string('noadaptivequizfound', 'tool_wbinstaller');
                        break;
                    }
                }

                if (isset($this->recipe['translator']['catscalename'])) {
                    $parentscaleid = $DB->get_record(
                        'local_catquiz_catscales',
                        ['name' => $this->recipe['translator']['catscalename']],
                        'id'
                    );
                    $newdata->catscaleid = $parentscaleid->id;
                }
                $position = strpos($row['component'], '_');
                $moudlename = substr($row['component'], $position + 1);
                $moudleid = $DB->get_field('modules', 'id', ['name' => $moudlename]);
                $cm = get_coursemodule_from_instance(
                    'adaptivequiz',
                    $newdata->componentid,
                    $newdata->courseid
                );
                $coursemoduleid = 0;
                if ($cm) {
                    $coursemoduleid = $cm->id;
                }
                foreach ($row as $key => $rowcol) {
                    if (isset($this->recipe['translator']['changingcolumn'][$key])) {
                        if (isset($newdata->$key)) {
                            $record->$key = $newdata->$key;
                        } else if ($this->recipe['translator']['changingcolumn'][$key]['nested']) {
                            $record->$key = $this->update_nested_json(
                                $rowcol,
                                $newdata->catscaleid,
                                $this->recipe['translator']['changingcolumn'][$key]['keys'],
                                $moudleid,
                                $coursemoduleid
                            );
                        }
                    } else if ($key != 'id') {
                        $record->$key = $rowcol;
                    }
                }
                $duplicatecheck = $this->duplicatecheck($fileinfo, $record);
                if ($duplicatecheck) {
                    $this->feedback['needed']['local_data']['warning'][] =
                        get_string('localdatauploadduplicate', 'tool_wbinstaller', $fileinfo);
                    break;
                } else if ($this->uploaddata) {
                    $newid = $DB->insert_record($fileinfo, $record);
                    $this->matchingids['testid'][$row['id']] = $newid;
                    $this->feedback['needed']['local_data']['success'][] =
                        get_string('localdatauploadsuccess', 'tool_wbinstaller', $fileinfo);
                } else {
                    $this->feedback['needed']['local_data']['error'][] =
                        get_string('localdatauploadmissingcourse', 'tool_wbinstaller', $fileinfo);
                }
            }
        }
        return 1;
    }

    /**
     * Check if course already exists.
     * @param string $fileinfo
     * @param object $record
     * @return bool
     */
    public function duplicatecheck($fileinfo, $record) {
        global $DB;
        $duplicatecheck = $this->recipe['translator']['duplicatecheck'] ?? null;
        if (
            empty($fileinfo) ||
            empty($record) ||
            empty($duplicatecheck)
        ) {
            return false;
        }
        $conditions = [];
        foreach ($duplicatecheck as $field) {
            if (isset($record->$field)) {
                $conditions[$field] = $record->$field;
            }
        }
        return $DB->record_exists($fileinfo, $conditions);
    }

    /**
     * Check if course already exists.
     * @param string $json
     * @param string $sacleid
     * @param array $keys
     * @param int $moudleid
     * @param int $coursemoduleid
     * @return string
     */
    public function update_nested_json($json, $sacleid, $keys, $moudleid, $coursemoduleid) {
        $json = json_decode($json, true);
        $translationsclaeids = $this->get_scale_matcher($json, $sacleid);
        $newdata = [];
        foreach ($keys as $changingkey) {
            foreach ($json as $key => $value) {
                if ($key == 'catquiz_catscales') {
                    $json[$key] = $sacleid;
                } else if ($key == 'module') {
                    $json[$key] = $moudleid;
                } else if ($key == 'update' || $key == 'coursemodule') {
                    $json[$key] = $coursemoduleid;
                } else if (str_contains($key, $changingkey)) {
                    $postfix = str_replace($changingkey . '_', '', $key);
                    $matches = explode('_', $postfix);
                    $oldid = (int)$matches[0];
                    if (
                      isset($translationsclaeids[$oldid]) &&
                      count($matches) > 1
                    ) {
                        $newid = $translationsclaeids[$oldid];
                        $newkey = $changingkey . "_{$newid}";
                        if (isset($matches[1])) {
                            $newkey .= "_{$matches[1]}";
                        }
                        if (
                            isset($this->recipe['translator']['changingcourseids']) &&
                            str_contains($key, $this->recipe['translator']['changingcourseids'])
                        ) {
                            $newdata[$newkey] = $this->course_matching($value);

                        } else {
                            $newdata[$newkey] = $this->translate_string_links($value);
                        }
                    } else {
                        $newdata[$key] = $value;
                    }
                    unset($json[$key]);
                }
            }
        }
        $json = array_merge($json, $newdata);
        return json_encode($json);
    }

    /**
     * Translate the links inside feedbacks strings that ref to courses.
     * @param mixed $value
     * @return mixed
     */
    public function translate_string_links($value) {
        global $CFG;
        if (!is_string($value)) {
            return $value;
        }
        preg_match_all('/course\/view\.php\?id=(\d+)/', $value, $matches);
        $ids = $matches[1];
        if (!empty($ids)) {
            foreach ($ids as $currentId) {
                $newid = $this->parent->matchingids['courses']['courses'][$currentId] ?? false;
                if ($newid) {
                    $value = preg_replace(
                        '/id=' . $currentId . '/', // Match the specific ID
                        'ID=' . $newid, // Replace with the new ID
                        $value
                    );
                    // Replace the old root with the new root in the value.
                    $value = preg_replace(
                        '/https?:\/\/[^\/]+\/(course\/view\.php\?ID=' . $newid . ')/',
                        $CFG->wwwroot . '/$1', // Keep the course path and ID intact.
                        $value
                    );
                } else {
                    $this->feedback['needed']['local_data']['error'][] =
                        get_string('courseidmismatchlocaldatalink', 'tool_wbinstaller', $currentId);
                }
            }
            $value = preg_replace(
                '/ID=/',
                'id=',
                $value
            );
        }
        return $value;
    }

    /**
     * Check if course already exists.
     * @param array $values
     * @return array
     */
    public function course_matching($values) {
        $courseids = [];
        foreach ($values as $value) {
            if (isset($this->parent->matchingids['courses']['courses'][$value])) {
                $courseids[] = $this->parent->matchingids['courses']['courses'][$value];
            } else {
                if ($this->uploaddata) {
                    $this->feedback['needed']['local_data']['error'][] =
                        get_string('courseidmismatchlocaldata', 'tool_wbinstaller');
                }
                $this->uploaddata = false;
            }
        }
        return $courseids;
    }

    /**
     * Check if course already exists.
     * @param object $json
     * @param string $sacleid
     * @return mixed
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
        $this->matchingids['catscales'] = $matcher;
        return $matcher;
    }
}
