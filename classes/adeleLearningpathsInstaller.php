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
 * Adele learning paths installer for the Wunderbyte installer tool.
 *
 * Handles the import and validation of Adele learning path records,
 * including course and component existence checks, entity ID remapping,
 * and updating related activity records.
 *
 * @package     tool_wbinstaller
 * @author      Jacob Viertel
 * @copyright   2026 Wunderbyte GmbH
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace tool_wbinstaller;

use stdClass;

/**
 * Installer class for Adele learning paths.
 *
 * Extends the base wbInstaller to provide specialised logic for importing
 * Adele learning path JSON data, validating referenced courses and components,
 * remapping entity IDs, and persisting records to the database.
 *
 * @package     tool_wbinstaller
 * @author      Jacob Viertel
 * @copyright   2026 Wunderbyte GmbH
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class adeleLearningpathsInstaller extends wbInstaller {
    /** @var \core_customfield\handler|null Custom field handler for course ID matching. */
    public $handler;

    /** @var string|null Database table name derived from the JSON filename. */
    public $fileinfo;

    /** @var array|null Decoded learning path data from the JSON file. */
    public $jsondata;

    /** @var \tool_wbinstaller\wbCheck|null Parent instance providing shared matching IDs. */
    public $parent;

    /** @var bool Whether to perform actual database updates (true) or only validation (false). */
    public $update;

    /** @var bool Flag indicating whether the target database table exists. */
    private $tableexists;

    /**
     * Constructor for the Adele learning paths installer.
     *
     * Initialises the installer with the given recipe configuration and sets
     * all state properties to their default values.
     *
     * @param array $recipe The recipe configuration for learning path installation.
     */
    public function __construct($recipe) {
        $this->recipe = $recipe;
        $this->progress = 0;
        $this->handler = null;
        $this->fileinfo = null;
        $this->parent = null;
        $this->tableexists = true;
    }

    /**
     * Execute the learning path installation.
     *
     * Sets the installer to update mode and delegates to run_recipe()
     * to process all learning path JSON files found in the recipe path.
     *
     * @param string $extractpath The path to the extracted recipe directory.
     * @param \tool_wbinstaller\wbCheck|null $parent The parent installer for shared state access.
     * @return string Returns '1' on completion.
     */
    public function execute($extractpath, $parent = null) {
        $this->parent = $parent;
        $this->update = true;
        $this->feedback = [];
        $this->tableexists = true;
        $this->run_recipe($extractpath);
        return '1';
    }

    /**
     * Process all learning path JSON files defined in the recipe.
     *
     * Iterates over all JSON files in the recipe path, decodes each file,
     * runs configured checks against each learning path record, and inserts
     * valid records into the database when in update mode. Collects feedback
     * for each learning path.
     *
     * @param string $extractpath The path to the extracted recipe directory.
     * @return void
     */
    public function run_recipe($extractpath) {
        global $DB;
        $path = $extractpath . $this->recipe['path'];

        foreach (glob("$path/*.json") as $file) {
            $filecontent = file_get_contents($file);
            $this->jsondata = json_decode($filecontent, true);
            $filename = basename($file);
            $this->fileinfo = pathinfo($filename, PATHINFO_FILENAME);

            // Reset the table-exists flag for each file since each file may target a different table.
            $this->tableexists = true;

            foreach ($this->jsondata as &$learningpath) {
                $learningpath['json'] = json_decode($learningpath['json']);

                // Run all configured checks against this learning path record.
                if (isset($this->recipe['checks'])) {
                    foreach ($this->recipe['checks'] as $checktype => $checkproperties) {
                        if (method_exists($this, $checktype)) {
                            $this->$checktype($checkproperties, $learningpath);
                        }
                    }
                }

                // Only insert the record if in update mode, the table exists, and no issues were found.
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
     * Update Adele activity records with the new learning path ID.
     *
     * Looks up all Adele activity records referencing the old learning path ID
     * and updates them to point to the new ID. If the Adele table does not exist
     * or no matching records are found, a warning is added to the feedback.
     *
     * @param int $oldlearningpathid The original learning path ID from the imported data.
     * @param int $newlearningpathid The newly assigned learning path ID after database insertion.
     * @param string $learningpathname The name of the learning path for feedback reporting.
     * @return void
     */
    public function update_adele_activity_id($oldlearningpathid, $newlearningpathid, $learningpathname) {
        global $DB;

        // Verify the Adele table exists before attempting any database operations.
        $dbmanager = $DB->get_manager();
        if (!$dbmanager->table_exists('adele')) {
            $this->feedback['needed'][$learningpathname]['warning'][] =
                get_string('nomoddatafilefound', 'tool_wbinstaller', $learningpathname);
            return;
        }

        $activityrecords = $DB->get_records(
            'adele',
            ['learningpathid' => $oldlearningpathid]
        );

        if ($activityrecords) {
            foreach ($activityrecords as $activityrecord) {
                $activityrecord->learningpathid = $newlearningpathid;
                $DB->update_record('adele', $activityrecord);
            }
        } else {
            $this->feedback['needed'][$learningpathname]['warning'][] =
                get_string('nomoddatafilefound', 'tool_wbinstaller', $learningpathname);
        }
    }

    /**
     * Run pre-checks without performing any database modifications.
     *
     * Sets the installer to check-only mode and delegates to run_recipe()
     * to validate all learning path JSON files without inserting records.
     *
     * @param string $extractpath The path to the extracted recipe directory.
     * @param \tool_wbinstaller\wbCheck $parent The parent installer for shared state access.
     * @return string Returns '1' on completion.
     */
    public function check($extractpath, $parent) {
        $this->parent = $parent;
        $this->update = false;
        $this->tableexists = true;
        $this->run_recipe($extractpath);
        return '1';
    }

    /**
     * Validate that referenced components exist in the matching ID maps.
     *
     * Traverses the learning path structure along the paths defined in the
     * properties configuration, checks each referenced component ID against
     * the parent's matching ID maps, and collects missing components. When
     * in update mode, remaps component IDs to their new values.
     *
     * @param array $properties Configuration defining the paths to component references.
     * @param array $learningpath Reference to the learning path record being validated.
     * @return void
     */
    public function check_component_exists($properties, &$learningpath) {
        $missingcomponents = [];

        foreach ($properties as $propertypath => $options) {
            $nodes = self::get_value_by_path($learningpath, $propertypath);
            if ($nodes === null) {
                continue;
            }

            foreach ($nodes as $node) {
                foreach ($options as $optionkey => $dataoptions) {
                    $completionnodes = self::get_value_by_path($node, $optionkey);
                    if ($completionnodes === null) {
                        continue;
                    }

                    foreach ($completionnodes as &$completionnode) {
                        $componentvalue = self::get_value_by_path($completionnode, $dataoptions);
                        if ($componentvalue) {
                            foreach ($componentvalue as $entitykey => &$entityvalue) {
                                $matchingtable = ($entitykey == 'quizid') ? 'courses' : 'localdata';
                                self::check_entity_id_exists(
                                    $entityvalue,
                                    $learningpath['name'],
                                    $missingcomponents,
                                    $matchingtable,
                                    $entitykey
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
                get_string(
                    'missingcomponents',
                    'tool_wbinstaller',
                    implode(', ', array_unique($missingcomponents))
                );
        }
    }

    /**
     * Validate that referenced courses exist in the matching ID maps.
     *
     * Traverses the learning path structure along the paths defined in the
     * properties configuration, checks each referenced course ID against
     * the parent's matching ID maps, and collects missing courses. When
     * in update mode, remaps course IDs to their new values.
     *
     * @param array $properties Configuration defining the paths to course references.
     * @param array $learningpath Reference to the learning path record being validated.
     * @return void
     */
    public function check_courses_exists($properties, &$learningpath) {
        $missingcourses = [];

        foreach ($properties as $propertypath => $options) {
            $nodes = self::get_value_by_path($learningpath, $propertypath);
            if ($nodes === null) {
                continue;
            }

            foreach ($nodes as &$node) {
                foreach ($options as $optionkey => $dataoptions) {
                    $coursedata = self::get_value_by_path($node, $optionkey);
                    self::check_entity_id_exists(
                        $coursedata,
                        $learningpath['name'],
                        $missingcourses,
                        'courses',
                        'courses'
                    );
                    if ($this->update) {
                        self::set_value_by_path($node, $optionkey, $coursedata);
                    }
                }
            }
        }

        if (!empty($missingcourses)) {
            $this->feedback['needed'][$learningpath['name']]['error'][] =
                get_string(
                    'missingcourses',
                    'tool_wbinstaller',
                    implode(', ', array_unique($missingcourses))
                );
        }
    }

    /**
     * Check whether an entity ID exists in the parent's matching ID map.
     *
     * Supports arrays, strings, and objects with a parent->id property.
     * When in update mode and the ID is found, remaps it to the new value.
     * When the ID is not found, adds it to the missing entities list.
     *
     * @param mixed $data Reference to the entity ID data to validate and potentially remap.
     * @param string $learningpathname The learning path name for feedback reporting.
     * @param array $missingentities Reference to the list collecting missing entity IDs.
     * @param string $matchingtype The entity type key in the matching IDs map (e.g., 'courses').
     * @param string $checkname The sub-key within the matching type (e.g., 'courses', 'quizid').
     * @return void
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
     * Retrieve a value from a nested array or object using a path string.
     *
     * The path uses '->' as a separator to traverse nested levels.
     * Returns null if any segment of the path does not exist.
     *
     * @param mixed $data The source array or object to traverse.
     * @param string $path The '->' separated path to the desired value.
     * @return mixed|null The value at the given path, or null if not found.
     */
    public function get_value_by_path($data, $path) {
        $pathsegments = explode('->', $path);

        foreach ($pathsegments as $segment) {
            if (is_array($data) && isset($data[$segment])) {
                $data = $data[$segment];
            } else if (is_object($data) && isset($data->$segment)) {
                $data = $data->$segment;
            } else {
                return null;
            }
        }
        return $data;
    }

    /**
     * Set a value in a nested array or object using a path string.
     *
     * The path uses '->' as a separator to traverse nested levels.
     * Does nothing if any intermediate segment of the path does not exist.
     *
     * @param mixed $data Reference to the source array or object to modify.
     * @param string $path The '->' separated path to the target location.
     * @param mixed $value The value to set at the given path.
     * @return void
     */
    public function set_value_by_path(&$data, $path, $value) {
        $pathsegments = explode('->', $path);
        $lastsegment = array_pop($pathsegments);

        foreach ($pathsegments as $segment) {
            if (is_array($data) && isset($data[$segment])) {
                $data = &$data[$segment];
            } else if (is_object($data) && isset($data->$segment)) {
                $data = &$data->$segment;
            } else {
                return;
            }
        }

        if (is_array($data)) {
            $data[$lastsegment] = $value;
        } else if (is_object($data)) {
            $data->$lastsegment = $value;
        }
    }

    /**
     * Check whether the target database table exists.
     *
     * Sets the tableexists flag to false if the table derived from the
     * current JSON filename does not exist, preventing subsequent database
     * operations from failing.
     *
     * @param string $properties The check properties (unused, required by interface).
     * @param array $learningpath The learning path record for feedback reporting.
     * @return void
     */
    public function check_table_exists($properties, $learningpath) {
        global $DB;
        $dbmanager = $DB->get_manager();
        if (!$dbmanager->table_exists($this->fileinfo)) {
            $this->tableexists = false;
            $this->feedback['needed'][$learningpath['name']]['warning'][] =
                get_string('dbtablenotfound', 'tool_wbinstaller', $this->fileinfo);
        }
    }

    /**
     * Check whether a learning path with the same name already exists in the database.
     *
     * Skips the check if the target table does not exist. If a duplicate is found,
     * adds an error to the feedback for the affected learning path.
     *
     * @param string $properties The check properties (unused, required by interface).
     * @param array $learningpath The learning path record to check for duplicates.
     * @return void
     */
    public function check_path_exists($properties, $learningpath) {
        global $DB;

        // Do not query a non-existent table.
        if (!$this->tableexists) {
            return;
        }

        $existingrecord = $DB->get_record(
            $this->fileinfo,
            ['name' => $learningpath['name']]
        );

        if ($existingrecord) {
            $this->feedback['needed'][$learningpath['name']]['error'][] =
                get_string('learningpathalreadyexistis', 'tool_wbinstaller', $this->fileinfo);
        }
    }
}
