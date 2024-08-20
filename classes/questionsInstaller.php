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
use context_course;
use Exception;

/**
 * Class tool_wbinstaller
 *
 * @package     tool_wbinstaller
 * @author      Jacob Viertel
 * @copyright  2023 Wunderbyte GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class questionsInstaller extends wbInstaller {
    /**
     * Entities constructor.
     * @param string $recipe
     * @param int $dbid
     */
    public function __construct($recipe, $dbid=null) {
        $this->dbid = $dbid;
        $this->recipe = $recipe;
        $this->progress = 0;
    }
    /**
     * Exceute the installer.
     * @return array
     */
    public function execute() {
        foreach (glob("$this->recipe/*") as $questionfile) {
            try {
                $qformat = $this->create_qformat($questionfile, 1);
                $qformat->importprocess();
                $this->feedback['needed'][basename($questionfile)]['success'][] = $questionfile;
            } catch (Exception $e) {
                $this->feedback['needed'][basename($questionfile)]['error'][] = $e;
            }
        }
        return 1;
    }

    /**
     * Exceute the installer.
     * @return array
     */
    public function check() {
        foreach (glob("$this->recipe/*") as $questionfile) {
            $this->feedback['needed'][basename($questionfile)]['success'][] =
              get_string('questionfilefound', 'tool_wbinstaller');
        }
    }

    /**
     * Create a new qformat object so that we can import questions.
     *
     * NOTE: copied from qformat_xml_import_export_test.php
     *
     * Create object qformat_xml for test.
     * @param string $filename with name for testing file.
     * @param int $courseid
     * @return \qformat_xml XML question format object.
     */
    private function create_qformat($filename, $courseid) {
        global $CFG;
        require_once($CFG->libdir . '/questionlib.php');
        require_once($CFG->dirroot . '/question/format/xml/format.php');
        require_once($CFG->dirroot . '/question/format.php');

        $qformat = new \qformat_xml();
        $qformat->setContexts((new question_edit_contexts(context_course::instance($courseid)))->all());
        $qformat->setCourse($course);
        $qformat->setFilename(__DIR__ . '/../fixtures/' . $filename);
        $qformat->setRealfilename($filename);
        $qformat->setMatchgrades('error');
        $qformat->setCatfromfile(1);
        $qformat->setContextfromfile(1);
        $qformat->setStoponerror(1);
        $qformat->setCattofile(1);
        $qformat->setContexttofile(1);
        $qformat->set_display_progress(false);
        $qformat->setFilename($filename);
        return $qformat;
    }
}
