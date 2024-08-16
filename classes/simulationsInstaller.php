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
use mod_booking\importer\bookingoptionsimporter;

/**
 * Class tool_wbinstaller
 *
 * @package     tool_wbinstaller
 * @author      Jacob Viertel
 * @copyright  2023 Wunderbyte GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class simulationsInstaller extends wbInstaller {

    /** @var object Matching the course ids from the old => new. */
    public $installmatcher;

    /**
     * Entities constructor.
     * @param string $recipe
     * @param int $dbid
     */
    public function __construct($recipe, $dbid=null) {
        $this->dbid = $dbid;
        $this->recipe = $recipe;
        $this->progress = 0;
        $this->installmatcher = json_decode(file_get_contents($this->recipe . '/wbinstaller_match.json'));
    }
    /**
     * Exceute the installer.
     * @return array
     */
    public function execute() {
        foreach (glob("$this->recipe/*.csv") as $itemparamsfile) {
            try {
                $this->import_itemparams($itemparamsfile);
            } catch (Exception $e) {
                $this->feedback['needed'][basename($itemparamsfile)]['error'][] = $e;
            }
        }
        return 1;
    }

     /**
      * Exceute the installer.
      * @return array
      */
    public function check() {
        foreach (glob("$this->recipe/*.csv") as $itemparamsfile) {
            $this->feedback['needed'][basename($itemparamsfile)]['success'][] =
              get_string('simulationfilefound', 'tool_wbinstaller');
            $filenamewithoutextension = pathinfo($itemparamsfile, PATHINFO_FILENAME);

            if (
                isset($this->installmatcher->$filenamewithoutextension) &&
                class_exists($this->installmatcher->$filenamewithoutextension->name)
              ) {
                $this->feedback['needed'][basename($itemparamsfile)]['success'][] =
                  get_string(
                    'simulationinstallerfilefound',
                    'tool_wbinstaller',
                    $this->installmatcher->$filenamewithoutextension->name
                  );
            } else {
                $this->feedback['needed'][basename($itemparamsfile)]['error'][] =
                  get_string('simulationnoinstallerfilefound', 'tool_wbinstaller');
            }
        }
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
        $filenamewithoutextension = pathinfo($filename, PATHINFO_FILENAME);
        $questions = $DB->get_records('question');
        if (! $questions) {
            $this->feedback['needed'][$filename]['error'][] = 'No questions found';
        } else if (
            isset($this->installmatcher->$filenamewithoutextension) &&
            class_exists($this->installmatcher->$filenamewithoutextension->name)
        ) {
            $installeroptions = $this->installmatcher->$filenamewithoutextension;
            $importerclass = $installeroptions->name;
                $importer = new $importerclass();
                $content = file_get_contents($filename);
                $importer->execute_testitems_csv_import(
                    (object) [
                        'delimiter_name' => $installeroptions->delimiter_name ?? 'semicolon',
                        'encoding' => $installeroptions->encoding ?? null,
                        'dateparseformat' => $installeroptions->dateparseformat ?? null,
                    ],
                    $content
                );
                $this->feedback['needed'][basename($filename)]['success'][] =
                  get_string('simulationinstallersuccess', 'tool_wbinstaller', $installeroptions->name);
        } else {
            $this->feedback['needed'][basename($filename)]['error'][] =
              get_string('simulationnoinstallerfilefound', 'tool_wbinstaller');
        }
    }
}
