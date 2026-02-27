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

/**
 * User profile fields installer for WB Installer recipes.
 *
 * @package     tool_wbinstaller
 * @copyright   2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @author      Mahdi Poustini
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class profilefieldsInstaller extends wbInstaller {
    /**
     * Constructor.
     * @param array $recipe
     */
    public function __construct($recipe) {
        $this->recipe = $recipe;
        $this->progress = 0;
    }

    /**
     * Execute installer.
     * @param string $extractpath
     * @param \tool_wbinstaller\wbCheck $parent
     * @return int
     */
    public function execute($extractpath, $parent = null) {
        $categories = $this->get_categories();
        foreach ($categories as $category) {
            $categoryname = $category['name'] ?? '';
            if (!$categoryname) {
                continue;
            }
            $categoryid = $this->get_or_create_profile_category($categoryname);
            foreach (($category['fields'] ?? []) as $field) {
                $this->ensure_profile_field($categoryid, $field);
            }
        }
        return 1;
    }

    /**
     * Check installer.
     * @param string $extractpath
     * @param \tool_wbinstaller\wbCheck $parent
     */
    public function check($extractpath, $parent) {
        global $CFG;
        require_once($CFG->dirroot . '/user/profile/lib.php');

        $categories = $this->get_categories();
        foreach ($categories as $category) {
            foreach (($category['fields'] ?? []) as $field) {
                $shortname = $field['shortname'] ?? '';
                if (!$shortname) {
                    continue;
                }
                if (profile_get_custom_field_data_by_shortname($shortname)) {
                    $this->feedback['needed']['profilefields']['success'][] =
                        get_string('profilefieldexists', 'tool_wbinstaller', $shortname);
                } else {
                    $this->feedback['needed']['profilefields']['warning'][] =
                        get_string('profilefieldmissing', 'tool_wbinstaller', $shortname);
                }
            }
        }
    }

    /**
     * Normalize recipe categories.
     * @return array
     */
    protected function get_categories(): array {
        if (isset($this->recipe['categories']) && is_array($this->recipe['categories'])) {
            return $this->recipe['categories'];
        }
        return is_array($this->recipe) ? $this->recipe : [];
    }

    /**
     * Create or get user profile category.
     * @param string $name
     * @return int
     */
    protected function get_or_create_profile_category(string $name): int {
        global $DB;
        $category = $DB->get_record('user_info_category', ['name' => $name], 'id');
        if ($category) {
            return (int)$category->id;
        }
        $sortorder = $DB->count_records('user_info_category') + 1;
        return (int)$DB->insert_record('user_info_category', (object)[
            'name' => $name,
            'sortorder' => $sortorder,
        ]);
    }

    /**
     * Ensure profile field exists.
     * @param int $categoryid
     * @param array $field
     */
    protected function ensure_profile_field(int $categoryid, array $field): void {
        global $CFG;
        require_once($CFG->dirroot . '/user/profile/definelib.php');
        require_once($CFG->dirroot . '/user/profile/lib.php');

        $shortname = $field['shortname'] ?? '';
        $name = $field['name'] ?? '';
        $datatype = $field['datatype'] ?? 'text';

        if (!$shortname || !$name) {
            return;
        }

        if (profile_get_custom_field_data_by_shortname($shortname)) {
            $this->feedback['needed']['profilefields']['success'][] =
                get_string('profilefieldexists', 'tool_wbinstaller', $shortname);
            return;
        }

        $defineclass = 'profile_define_' . $datatype;
        $definepath = $CFG->dirroot . '/user/profile/field/' . $datatype . '/define.class.php';
        if (!file_exists($definepath)) {
            $this->feedback['needed']['profilefields']['error'][] =
                get_string('profilefieldunsupported', 'tool_wbinstaller', $datatype);
            return;
        }
        require_once($definepath);
        if (!class_exists($defineclass)) {
            $this->feedback['needed']['profilefields']['error'][] =
                get_string('profilefieldunsupported', 'tool_wbinstaller', $datatype);
            return;
        }

        $data = (object)[
            'id' => 0,
            'shortname' => $shortname,
            'name' => $name,
            'datatype' => $datatype,
            'description' => $field['description'] ?? '',
            'descriptionformat' => $field['descriptionformat'] ?? FORMAT_HTML,
            'required' => $field['required'] ?? 0,
            'locked' => $field['locked'] ?? 0,
            'forceunique' => $field['forceunique'] ?? 0,
            'signup' => $field['signup'] ?? 0,
            'visible' => $this->map_visible($field['visible'] ?? PROFILE_VISIBLE_ALL),
            'categoryid' => $categoryid,
            'defaultdata' => $field['defaultdata'] ?? '',
            'defaultdataformat' => $field['defaultdataformat'] ?? 0,
            'param1' => $field['param1'] ?? 30,
            'param2' => $field['param2'] ?? 2048,
            'param3' => $field['param3'] ?? 0,
            'param4' => $field['param4'] ?? '',
            'param5' => $field['param5'] ?? '',
        ];

        $def = new $defineclass();
        $def->define_save($data);

        $this->feedback['needed']['profilefields']['success'][] =
            get_string('profilefieldcreated', 'tool_wbinstaller', $shortname);
    }

    /**
     * Map visible input to Moodle constants.
     * @param mixed $visible
     * @return int
     */
    protected function map_visible($visible): int {
        if (is_int($visible)) {
            return $visible;
        }
        $map = [
            'none' => PROFILE_VISIBLE_NONE,
            'private' => PROFILE_VISIBLE_PRIVATE,
            'teachers' => PROFILE_VISIBLE_TEACHERS,
            'all' => PROFILE_VISIBLE_ALL,
        ];
        $key = strtolower((string)$visible);
        return (int) $map[$key] ?? PROFILE_VISIBLE_ALL;
    }
}
