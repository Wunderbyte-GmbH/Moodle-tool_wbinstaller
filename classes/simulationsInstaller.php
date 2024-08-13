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

use Exception;
use local_catquiz\importer\testitemimporter;

/**
 * Class tool_wbinstaller
 *
 * @package     tool_wbinstaller
 * @author      Jacob Viertel
 * @copyright  2023 Wunderbyte GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class simulationsInstaller extends wbInstaller {
    /**
     * Entities constructor.
     * @param string $recipe
     * @param int $dbid
     */
    public function __construct($recipe, $dbid) {
        $this->dbid = $dbid;
        $this->recipe = $recipe;
        $this->progress = 0;
    }
    /**
     * Exceute the installer.
     * @return array
     */
    public function execute() {
        return 1;
        foreach (glob("$this->recipe/*") as $itemparamsfile) {
            try {
                $this->import_itemparams($itemparamsfile);
            } catch (Exception $e) {
                $this->errors[$itemparamsfile] = $e;
            }
        }
        return 1;
    }

     /**
      * Import the item params from the given CSV file
      *
      * @param string $filename The name of the itemparams file.
      *
      * @return void
      */
    private function import_itemparams($filename) {
        global $DB;
        $questions = $DB->get_records('question');
        if (! $questions) {
            exit('No questions were imported');
        }
        $importer = new testitemimporter();
        $content = file_get_contents($filename);
        $importer->execute_testitems_csv_import(
                (object) [
                    'delimiter_name' => 'semicolon',
                    'encoding' => null,
                    'dateparseformat' => null,
                ],
                $content
            );
    }
}
