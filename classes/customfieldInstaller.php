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

use core_question\local\bank\question_edit_contexts;
use local_catquiz\strategy_test;
use context_course;
use core_customfield\handler_test;
use Exception;
use stdClass;

/**
 * Class tool_wbinstaller
 *
 * @package     tool_wbinstaller
 * @author      Jacob Viertel
 * @copyright  2023 Wunderbyte GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class customfieldInstaller extends wbInstaller {

    /** @var \core_customfield\handler Matching the course ids from the old => new. */
    public $handler;

    /**
     * Entities constructor.
     * @param string $recipe
     * @param int $dbid
     */
    public function __construct($recipe, $dbid=null) {
        $this->dbid = $dbid;
        $this->recipe = $recipe;
        $this->progress = 0;
        $this->handler = null;
    }
    /**
     * Exceute the installer.
     * @return int
     */
    public function execute() {
        global $DB;
        $jsonstring = file_get_contents($this->recipe . '.json');
        $jsonarray = json_decode($jsonstring, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->feedback['customfields']['error'][] = 'Error decoding JSON: ' . json_last_error_msg();
            $this->set_status(2);
        }
        $customfieldfields = $DB->get_records('customfield_field', null, null, 'shortname');
        foreach ($jsonarray as $customfields) {
            $categoryid = $this->upload_category($customfields);
            foreach ($customfields['fields'] as $customfield) {
                if (!$categoryid) {
                    $this->feedback['needed'][$customfields['name']]['error'][] = "Category could not be uploaded!";
                } else if (isset($customfieldfields[$customfield['shortname']])) {
                    $this->feedback['needed'][$customfields['name']]['error'][] = "Customfield shortname already exists!";
                } else {
                    $this->upload_fieldset($customfield, $categoryid);
                    $this->feedback['needed'][$customfields['name']]['success'][] =
                      "Customfield " . $customfield['name'] . ' was installed successfully.';
                }
            }
        }
        return 1;
    }

    /**
     * Upload the category.
     * @param array $customfields
     * @return int
     */
    public function upload_category($customfields) {
        $this->handler = \core_customfield\handler::get_handler(
            $customfields['component'],
            $customfields['area']
        );
        return $this->handler->create_category($customfields['name']);
    }

    /**
     * Upload the fieldset.
     * @param array $fieldset
     * @param int $categoryid
     */
    public function upload_fieldset($fieldset, $categoryid) {
        $record = new \stdClass();
        $record->categoryid = $categoryid;
        $record->type = $fieldset['type'];
        $fieldcontroller = \core_customfield\field_controller::create(0, $record);

        $fieldcontroller->set('shortname', $fieldset['shortname']);
        $fieldcontroller->set('name', $fieldset['name']);

        $configdata = (object) $fieldset['data'];
        $configdata = json_encode($configdata);
        $fieldcontroller->set('configdata', $configdata);

        $this->handler->save_field_configuration($fieldcontroller, (object)[
            'shortname' => $fieldset['shortname'],
            'name' => $fieldset['name'],
            'type' => $fieldset['type'],
            'configdata' => $configdata
        ]);
    }

    /**
     * Exceute the installer check.
     * @return array
     */
    public function check() {
        global $DB;
        $jsonstring = file_get_contents($this->recipe . '.json');
        $jsonarray = json_decode($jsonstring, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->feedback['customfields']['error'][] = 'Error decoding JSON: ' . json_last_error_msg();
            $this->set_status(2);
        }
        $customfieldfields = $DB->get_records('customfield_field', null, null, 'shortname');
        foreach ($jsonarray as $customfields) {
            foreach ($customfields['fields'] as $customfield) {
                if (isset($customfieldfields[$customfield['shortname']])) {
                    $this->feedback['needed'][$customfields['name']]['error'][] = "Customfield shortname already exists!";
                } else {
                    $this->feedback['needed'][$customfields['name']]['success'][] =
                      "Found the new customfield: " . $customfield['name'];
                }
            }
        }
    }
}
