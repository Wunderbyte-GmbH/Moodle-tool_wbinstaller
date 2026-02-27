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

use core_course_category;

/**
 * Course categories installer for WB Installer recipes.
 *
 * @package     tool_wbinstaller
 * @copyright   2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @author      Mahdi Poustini
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class categoriesInstaller extends wbInstaller {
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
        $paths = $this->get_paths();
        foreach ($paths as $path) {
            $parentid = 0;
            foreach ($path as $name) {
                $category = $this->get_category_by_name_parent($name, $parentid);
                if ($category) {
                    $this->feedback['needed']['categories']['success'][] =
                        get_string('categoriesexists', 'tool_wbinstaller', $name);
                    $parentid = $category->id;
                    continue;
                }
                $newcategory = core_course_category::create((object)[
                    'name' => $name,
                    'parent' => $parentid,
                ]);
                $parentid = $newcategory->id;
                $this->feedback['needed']['categories']['success'][] =
                    get_string('categoriescreated', 'tool_wbinstaller', $name);
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
        $paths = $this->get_paths();
        foreach ($paths as $path) {
            $parentid = 0;
            $missing = false;
            foreach ($path as $name) {
                $category = $this->get_category_by_name_parent($name, $parentid);
                if (!$category) {
                    $missing = true;
                    $this->feedback['needed']['categories']['warning'][] =
                        get_string('categoriesmissing', 'tool_wbinstaller', $name);
                    break;
                }
                $parentid = $category->id;
            }
            if (!$missing) {
                $this->feedback['needed']['categories']['success'][] =
                    get_string('categoriesfound', 'tool_wbinstaller', implode(' / ', $path));
            }
        }
    }

    /**
     * Normalize recipe paths.
     * @return array
     */
    protected function get_paths(): array {
        if (isset($this->recipe['paths']) && is_array($this->recipe['paths'])) {
            return $this->recipe['paths'];
        }
        return is_array($this->recipe) ? $this->recipe : [];
    }

    /**
     * Get category by name and parent.
     * @param string $name
     * @param int $parentid
     * @return \stdClass|null
     */
    protected function get_category_by_name_parent(string $name, int $parentid) {
        global $DB;
        return $DB->get_record('course_categories', [
            'name' => $name,
            'parent' => $parentid,
        ]);
    }
}
