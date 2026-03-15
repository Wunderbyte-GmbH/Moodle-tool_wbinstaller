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

namespace tool_wbinstaller;

use stdClass;

class adeleLearningpathsInstaller extends wbInstaller {
    /** @var \core_customfield\handler Matching the course ids from the old => new. */
    public $handler;
    /** @var string DB table name. */
    public $fileinfo;
    /** @var object Local learning pathdata. */
    public $jsondata;
    /** @var \tool_wbinstaller\wbCheck Parent matching ids. */
    public $parent;
    /** @var bool Update or check values. */
    public $update;
    /** @var bool Flag indicating whether the target table exists. */
    private $tableexists;

    /**
     * Entities constructor.
     * @param array $recipe
     */
    public function __construct($recipe) {
        $this->recipe = $recipe;
        $this->progress = 0;
        $this->feedback = [];       // FIX 1: Feedback-Array initialisieren.
        $this->handler = null;
        $this->fileinfo = null;
        $this->parent = null;
        $this->tableexists = true;   // Assume true until checked.
    }

    /**
     * Execute the installer.
     * @param string $extractpath
     * @param \tool_wbinstaller\wbCheck $parent
     * @return int
     */
    public function execute($extractpath, $parent = null) {
        $this->parent = $parent;
        $this->update = true;
        $this->feedback = [];       // FIX 2: Feedback vor execute zurücksetzen.
        $this->tableexists = true;
        $this->run_recipe($extractpath);
        return '1';
    }

    /**
     * Execute the installer.
     * @param string $extractpath
     */
    public function run_recipe($extractpath) {
        global $DB;
        $coursespath = $extractpath . $this->recipe['path'];
        foreach (glob("$coursespath/*") as $coursefile) {
            $filecontents = file_get_contents($coursefile);
            $this->jsondata = json_decode($filecontents, true);
            $filenameproperties = basename($coursefile);
            $this->fileinfo = pathinfo($filenameproperties, PATHINFO_FILENAME);

            // FIX 3: Reset table-exists flag for each file/table.
            $this->tableexists = true;

            foreach ($this->jsondata as &$learningpath) {
                $learningpath['json'] = json_decode($learningpath['json']);
                if (isset($this->recipe['checks'])) {
                    foreach ($this->recipe['checks'] as $checktype => $checkproperties) {
                        if (method_exists($this, $checktype)) {
                            $this->$checktype($checkproperties, $learningpath);
                        }
                    }
                }

                // FIX 4: Nur einfügen wenn keine Warnings/Errors für DIESEN Lernpfad.
                if (
                    $this->update &&
                    $this->tableexists &&
                    empty($this->feedback['needed'][$learningpath['name']]['warning']) &&
                    empty($this->feedback['needed'][$learningpath['name']]['error'])
                ) {
                    $oldlearningpathid = 0;
                    if (isset($learningpath['id'])) {
                        $oldlearningpathid = $learningpath['id'];
                        unset($learningpath['id']);
                    }
                    $learningpath['json'] = json_encode($learningpath['json']);
                    $newlearningpathid = $DB->insert_record($this->fileinfo, $learningpath);
                    $this->update_adele_activity_id(
                        $oldlearningpathid,
                        $newlearningpathid,
                        $learningpath['name']
                    );
                }
                $this->feedback['needed'][$learningpath['name']]['success'][] =
                    get_string('newlocaldatafilefound', 'tool_wbinstaller', $learningpath['name']);
            }
        }
    }

    /**
     * Update adele activity records with the new learning path ID.
     * @param string $oldlearningpathid
     * @param string $newlearningpathid
     * @param string $learningpathname
     */
    public function update_adele_activity_id($oldlearningpathid, $newlearningpathid, $learningpathname) {
        global $DB;

        // FIX 5: Prüfen ob die adele-Tabelle überhaupt existiert, bevor darauf zugegriffen wird.
        $manager = $DB->get_manager();
        if (!$manager->table_exists('adele')) {
            $this->feedback['needed'][$learningpathname]['warning'][] =
                get_string('nomoddatafilefound', 'tool_wbinstaller', $learningpathname);
            return;
        }

        $records = $DB->get_records(
            'adele',
            [
                'learningpathid' => $oldlearningpathid,
            ]
        );
        if ($records) {
            foreach ($records as $record) {
                $record->learningpathid = $newlearningpathid;
                $DB->update_record('adele', $record);
            }
        } else {
            $this->feedback['needed'][$learningpathname]['warning'][] =
                get_string('nomoddatafilefound', 'tool_wbinstaller', $learningpathname);
        }
        return;
    }

    /**
     * Run pre-checks.
     * @param string $extractpath
     * @param \tool_wbinstaller\wbCheck $parent
     * @return string
     */
    public function check($extractpath, $parent) {
        $this->parent = $parent;
        $this->update = false;
        $this->feedback = [];       // FIX 6: Feedback vor check zurücksetzen.
        $this->tableexists = true;
        $this->run_recipe($extractpath);
        return '1';
    }

    /**
     * Check if the local data exists.
     * @param array $properties
     * @param array $learningpath
     */
    public function check_component_exists($properties, &$learningpath) {
        $missingcomponents = [];
        foreach ($properties as $property => $options) {
            $nodes = self::get_value_by_path($learningpath, $property);
            if ($nodes === null) {
                continue;
            }
            foreach ($nodes as $node) {
                // FIX 7: Variable-Shadowing behoben ($property -> $optionkey).
                foreach ($options as $optionkey => $dataoptions) {
                    $completionnodes = self::get_value_by_path($node, $optionkey);
                    if ($completionnodes === null) {
                        continue;
                    }
                    foreach ($completionnodes as &$completionnode) {
                        $componentvalue = self::get_value_by_path($completionnode, $dataoptions);
                        if ($componentvalue) {
                            foreach ($componentvalue as $testkey => &$testvalue) {
                                $table = $testkey == 'quizid' ? 'courses' : 'localdata';
                                self::check_entity_id_exists(
                                    $testvalue,
                                    $learningpath['name'],
                                    $missingcomponents,
                                    $table,
                                    $testkey
                                );
                            }
                        }
                        if ($this->update) {
                            self::set_value_by_path($completionnode, $optionkey, $componentvalue);
                        }
                    }
                }
            }
        }
        if (!empty($missingcomponents)) {
            $this->feedback['needed'][$learningpath['name']]['error'][] =
              get_string('missingcomponents', 'tool_wbinstaller', implode(', ', array_unique($missingcomponents)));
        }
    }

    /**
     * Check if courses exist.
     * @param array $properties
     * @param object $learningpath
     */
    public function check_courses_exists($properties, &$learningpath) {
        $missingcourses = [];
        foreach ($properties as $property => $options) {
            $nodes = self::get_value_by_path($learningpath, $property);
            if ($nodes === null) {
                continue;
            }
            foreach ($nodes as &$node) {
                // FIX 8: Variable-Shadowing behoben ($property -> $optionkey).
                foreach ($options as $optionkey => $dataoptions) {
                    $nodesdata = self::get_value_by_path($node, $optionkey);
                    self::check_entity_id_exists(
                        $nodesdata,
                        $learningpath['name'],
                        $missingcourses,
                        'courses',
                        'courses'
                    );
                    if ($this->update) {
                        self::set_value_by_path($node, $optionkey, $nodesdata);
                    }
                }
            }
        }
        if (!empty($missingcourses)) {
            $this->feedback['needed'][$learningpath['name']]['error'][] =
              get_string('missingcourses', 'tool_wbinstaller', implode(', ', array_unique($missingcourses)));
        }
    }

    /**
     * Check if entity ID exists in matching IDs.
     * @param mixed $data
     * @param string $name
     * @param array $missingentities
     * @param string $matchingtype
     * @param string $checkname
     */
    public function check_entity_id_exists(&$data, $name, &$missingentities, $matchingtype, $checkname) {
        if (isset($this->parent->matchingids[$matchingtype][$checkname])) {
            if (is_array($data)) {
                foreach ($data as &$courseid) {
                    if (!isset($this->parent->matchingids[$matchingtype][$checkname][$courseid])) {
                        $missingentities[] = $courseid;
                    } else if ($this->update) {
                        $courseid = $this->parent->matchingids[$matchingtype][$checkname][$courseid] ?? $courseid;
                    }
                }
            } else if (is_string($data)) {
                if (!isset($this->parent->matchingids[$matchingtype][$checkname][$data])) {
                    $missingentities[] = $data;
                } else if ($this->update) {
                    $data = (string) ($this->parent->matchingids[$matchingtype][$checkname][$data] ?? $data);
                }
            } else if (
                is_object($data) &&
                isset($data->parent->id)
            ) {
                if (!in_array($data->parent->id, $this->parent->matchingids[$matchingtype][$checkname])) {
                    $missingentities[] = $data;
                } else if ($this->update) {
                    $data->parent->id =
                        $this->parent->matchingids[$matchingtype][$checkname][$data->parent->id] ??
                        $data->parent->id;
                }
            } else {
                $this->feedback['needed'][$name]['error'][] =
                    get_string('coursetypenotfound', 'tool_wbinstaller');
            }
        }
    }

    /**
     * Get value by path in a multi-dimensional array or object.
     * @param mixed $data
     * @param string $path
     * @return mixed
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
     * Set the value by path in a multi-dimensional array or object.
     * @param mixed $data The original array or object
     * @param string $path The path where to set the value
     * @param mixed $value The value to set
     */
    public function set_value_by_path(&$data, $path, $value) {
        $parts = explode('->', $path);
        $lastpart = array_pop($parts);

        foreach ($parts as $part) {
            if (is_array($data) && isset($data[$part])) {
                $data = &$data[$part];
            } else if (is_object($data) && isset($data->$part)) {
                $data = &$data->$part;
            } else {
                return;
            }
        }
        if (is_array($data)) {
            $data[$lastpart] = $value;
        } else if (is_object($data)) {
            $data->$lastpart = $value;
        }
    }

    /**
     * Checks if the table exists.
     * FIX 9: Setzt $this->tableexists Flag, damit nachfolgende Checks und
     * der Insert-Block nicht auf eine nicht-existente Tabelle zugreifen.
     * @param string $properties
     * @param array $learningpath
     */
    public function check_table_exists($properties, $learningpath) {
        global $DB;
        $manager = $DB->get_manager();
        if (!$manager->table_exists($this->fileinfo)) {
            $this->tableexists = false;
            $this->feedback['needed'][$learningpath['name']]['warning'][] =
              get_string('dbtablenotfound', 'tool_wbinstaller', $this->fileinfo);
        }
    }

    /**
     * Checks if the path exists.
     * FIX 10: Prüft zuerst ob die Tabelle existiert, bevor ein DB-Zugriff erfolgt.
     * @param string $properties
     * @param object $learningpath
     */
    public function check_path_exists($properties, $learningpath) {
        global $DB;

        // Nicht auf eine nicht-existente Tabelle zugreifen.
        if (!$this->tableexists) {
            return;
        }

        $path = $DB->get_record(
            $this->fileinfo,
            ['name' => $learningpath['name']]
        );
        if ($path) {
            $this->feedback['needed'][$learningpath['name']]['error'][] =
              get_string('learningpathalreadyexistis', 'tool_wbinstaller', $this->fileinfo);
        }
    }
}
