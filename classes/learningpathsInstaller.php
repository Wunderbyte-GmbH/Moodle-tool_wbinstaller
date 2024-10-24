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

/**
 * Class tool_wbinstaller
 *
 * @package     tool_wbinstaller
 * @author      Jacob Viertel
 * @copyright  2023 Wunderbyte GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class learningpathsInstaller extends wbInstaller {
    /** @var \core_customfield\handler Matching the course ids from the old => new. */
    public $handler;
    /** @var string DB table name. */
    public $fileinfo;
    /** @var object Local learning pathdata. */
    public $jsondata;
    /** @var \tool_wbinstaller\wbCheck Parent matching ids. */
    public $parent;
    /** @var array Matching ids old to new. */
    public $matchingids;

    /**
     * Entities constructor.
     * @param array $recipe
     * @param int $dbid
     */
    public function __construct($recipe, $dbid = null) {
        $this->dbid = $dbid;
        $this->recipe = $recipe;
        $this->progress = 0;
        $this->handler = null;
        $this->fileinfo = null;
        $this->parent = null;
    }

    /**
     * Exceute the installer.
     * @param string $extractpath
     * @return int
     */
    public function execute($extractpath) {
        global $DB;
        foreach ($this->recipe as $pluginname => $configfields) {
            foreach ($configfields as $configfield => $value) {
                $currentvalue = get_config($pluginname, $configfield);
                if ($currentvalue !== false) {
                    set_config($configfield, $value, $pluginname);
                    $this->feedback['needed'][$pluginname]['success'][] =
                        get_string('configvalueset', 'tool_wbinstaller', $configfield);
                } else {
                    $this->feedback['needed'][$pluginname]['error'][] =
                      get_string('confignotfound', 'tool_wbinstaller', $configfield);
                }
            }
        }
        return 1;
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
            $filecontents = file_get_contents($coursefile);
            $this->jsondata = json_decode($filecontents, true);
            $filenameproperties = basename($coursefile);
            $this->fileinfo = pathinfo($filenameproperties, PATHINFO_FILENAME);
            foreach ($this->jsondata as $learningpath) {
                $learningpath['json'] = json_decode($learningpath['json']);
                if (isset($this->recipe['checks'])) {
                    foreach ($this->recipe['checks'] as $checktype => $checkproperties) {
                        if (method_exists($this, $checktype)) {
                            $this->$checktype($checkproperties, $learningpath);
                        }
                    }
                }
                $this->feedback['needed'][$learningpath['name']]['success'][] =
                    get_string('newlocaldatafilefound', 'tool_wbinstaller', $learningpath['name']);
            }
        }
        return '1';
    }

    /**
     * Check if the local data exists.
     * @param array $properties
     * @param object $learningpath
     */
    public function check_component_exists($properties, $learningpath) {
        $missingcomponents = [];
        foreach ($properties as $property => $options) {
            $nodes = self::get_value_by_path($learningpath, $property);
            foreach ($nodes as $node) {
                foreach ($options as $property => $dataoptions) {
                    $completionnodes = self::get_value_by_path($node, $property);
                    foreach ($completionnodes as $completionnode) {
                        $componentvalue = self::get_value_by_path($completionnode, $dataoptions);
                        if ($componentvalue) {
                            foreach ($componentvalue as $testkey => $testvalue) {
                                self::check_entity_id_exists(
                                    $testvalue,
                                    $learningpath['name'],
                                    $missingcomponents,
                                    'localdata',
                                    $testkey
                                );
                            }
                        }
                    }
                }
            }
        }
        if (!empty($missingcomponents)) {
            $this->feedback['needed'][$learningpath['name']]['error'][] =
              get_string('missingcomponents', 'tool_wbinstaller', implode(', ', array_unique($missingcourses)));
        }
    }

    /**
     * Exceute the installer.
     * @param string $extractpath
     * @param object $learningpath
     */
    public function check_courses_exists($properties, $learningpath) {
        $missingcourses = [];
        foreach ($properties as $property => $options) {
            $nodes = self::get_value_by_path($learningpath, $property);
            foreach ($nodes as $node) {
                foreach ($options as $property => $dataoptions) {
                    $nodesdata = self::get_value_by_path($node, $property);
                    self::check_entity_id_exists(
                        $nodesdata,
                        $learningpath['name'],
                        $missingcourses,
                        'courses',
                        'courses'
                    );
                }
            }
        }
        if (!empty($missingcourses)) {
            $this->feedback['needed'][$learningpath['name']]['error'][] =
              get_string('missingcourses', 'tool_wbinstaller', implode(', ', array_unique($missingcourses)));
        }
    }

    /**
     * Exceute the installer.
     * @param mixed $data
     * @param string $name
     * @param array $missingentities
     * @param string $matchingtype
     * @param string $checkname
     */
    public function check_entity_id_exists($data, $name, &$missingentities, $matchingtype, $checkname) {
        if (isset($this->parent->matchingids[$matchingtype][$checkname])) {
            if (is_array($data)) {
                foreach ($data as $courseid) {
                    if (!in_array($courseid, $this->parent->matchingids[$matchingtype][$checkname])) {
                        $missingentities[] = $courseid;
                    }
                }
            } else if (is_string($data)) {
                if (!in_array($data, haystack: $this->parent->matchingids[$matchingtype][$checkname])) {
                    $missingentities[] = $data;
                }
            } else if (is_object($data)) {
                if (
                    isset($data->parent->id) &&
                    !in_array($data->parent->id, $this->parent->matchingids[$matchingtype][$checkname])
                ) {
                    $missingentities[] = $data;
                }
            } else {
                $this->feedback['needed'][$name]['error'][] =
                    get_string('coursetypenotfound', 'tool_wbinstaller');
            }
        }
    }

    /**
     * Exceute the installer.
     * @param object $data
     * @param string $path
     */
    public function get_value_by_path($data, $path) {
        $parts = explode('->', $path);
        foreach ($parts as $part) {
            if (is_array($data) && isset($data[$part])) {
                $data = $data[$part];
            } else if (is_object($data) && isset($data->$part)) {
                $data = $data->$part;
            } else {
                return null;
            }
        }
        return $data;
    }

    /**
     * Exceute the installer.
     * @param string $extractpath
     * @param object $learningpath
     */
    public function check_table_exists($properties, $learningpath) {
        global $DB;
        $manager = $DB->get_manager();
        if (!$manager->table_exists($this->fileinfo)) {
            $this->feedback['needed'][$learningpath['name']]['error'][] =
              get_string('dbtablenotfound', 'tool_wbinstaller', $this->fileinfo);
        }
    }

    /**
     * Check if course already exists.
     * @return array
     */
    public function get_matchingids() {
        return $this->matchingids;
    }
}
