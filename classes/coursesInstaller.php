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

use backup;
use restore_controller;
use stdClass;

require(__DIR__.'/../../../../config.php');
require(__DIR__.'/../../../../lib/setup.php');
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
class coursesInstaller extends wbInstaller {

      /** @var array Matching the course ids from the old => new. */
      public $matchingcourseids;

    /**
     * Entities constructor.
     * @param string $recipe
     * @param int $dbid
     */
    public function __construct($recipe, $dbid) {
        $this->dbid = $dbid;
        $this->recipe = $recipe;
        $this->progress = 0;
        $this->errors = [];
        $this->matchingcourseids = [];
    }

    /**
     * Exceute the installer.
     * @return string
     */
    public function execute() {
        return 1;
        foreach (glob("$this->recipe/*.mbz") as $coursefile) {
            $this->install_course($coursefile);
        }
        return '1';
    }

    /**
     * Instal a single course.
     * @param string $coursefile
     * @return mixed
     */
    private function install_course($coursefile) {
        $xml = simplexml_load_file($coursefile . '/moodle_backup.xml');
        $courseshortname = $this->get_course_short_name($xml);
        $courseoriginalid = $this->get_course_og_id($xml);
        if (!$courseshortname || !$courseoriginalid) {
            $this->errors['install'][] = 'Could not get the short name of course: ' . $coursefile;
            return;
        }
        if ($this->course_exists($courseshortname)) {
            $this->errors['skipped'][] = "Skipped: Course with short name '$courseshortname' already exists.";
            return;
        }
        $this->restore_course($coursefile, $courseoriginalid);
    }

    /**
     * Get the course short name.
     * @param simpleXMLElement $xml
     * @return string
     */
    private function get_course_short_name($xml) {
        return (string)$xml->information->original_course_shortname;
    }

    /**
     * Get the course id.
     * @param simpleXMLElement $xml
     * @return string
     */
    private function get_course_og_id($xml) {
        return (string)$xml->information->original_course_iud;
    }

    /**
     * Check if course already exists.
     * @param string $shortname
     * @return bool
     */
    private function course_exists($shortname) {
        global $DB;
        return $DB->record_exists('course', ['shortname' => $shortname]);
    }

    /**
     * Restore the course.
     * @param string $coursefile
     * @param string $ogid
     * @return mixed
     */
    private function restore_course($coursefile, $ogid) {
        global $USER, $CFG;
        $newcourse = new stdClass();
        $newcourse->fullname = 'Temporary Course Fullname';
        $newcourse->shortname = 'temp_' . uniqid();
        $newcourse->category = 1;
        $newcourse->format = 'topics';
        $newcourse->visible = 0;
        $newcourse = create_course($newcourse);
        $this->matchingcourseids[$newcourse->id] = $ogid;

        $destination = $CFG->tempdir . '/backup/' . basename($coursefile);

        if (!is_dir($destination)) {
            mkdir($destination, 0777, true);
        }

        if (!$this->copy_directory($coursefile, $destination)) {
            $this->errors['move'][] = 'Failed to copy extracted files to the Moodle backup directory.';
            return;
        }
        $rc = new restore_controller(
            basename($coursefile),
            $newcourse->id,
            backup::INTERACTIVE_NO,
            backup::MODE_IMPORT,
            $USER->id,
            backup::TARGET_NEW_COURSE
        );

        if (!$rc->execute_precheck()) {
            $this->errors['precheck'] = "Precheck failed for course restore: '$coursefile'";
            return;
        }
        $rc->execute_plan();
        $rc->destroy();
        fulldelete($destination);
    }

    /**
     * Recursively copies a directory.
     *
     * @param string $src
     * @param string $dst
     * @return bool True on success, false on failure.
     */
    public function copy_directory($src, $dst) {
        $dir = opendir($src);
        @mkdir($dst);
        while (false !== ($file = readdir($dir))) {
            if (($file != '.') && ($file != '..')) {
                if (is_dir($src . '/' . $file)) {
                    $this->copy_directory($src . '/' . $file, $dst . '/' . $file);
                } else {
                    copy($src . '/' . $file, $dst . '/' . $file);
                }
            }
        }
        closedir($dir);
        return true;
    }
}
