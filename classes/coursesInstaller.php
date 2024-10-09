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

require_once(__DIR__.'/../../../../config.php');
require_once(__DIR__.'/../../../../lib/setup.php');
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

    /**
     * Entities constructor.
     * @param mixed $recipe
     * @param int $dbid
     */
    public function __construct($recipe, $dbid=null) {
        $this->dbid = $dbid;
        $this->recipe = $recipe;
        $this->progress = 0;
    }

    /**
     * Exceute the installer.
     * @param string $extractpath
     * @return string
     */
    public function execute($extractpath) {
        $coursespath = $extractpath . $this->recipe['path'];
        foreach (glob("$coursespath/*") as $coursefile) {
            $this->install_course($coursefile);
        }
        return '1';
    }

    /**
     * Exceute the installer.
     * @param string $extractpath
     * @return string
     */
    public function check($extractpath) {
        $coursespath = $extractpath . $this->recipe['path'];
        foreach (glob("$coursespath/*") as $coursefile) {
            $precheck = $this->precheck($coursefile);
            if ($precheck) {
                $this->feedback['needed'][$precheck['courseshortname']]['success'][] =
                    get_string('newcoursefound', 'tool_wbinstaller', $precheck['courseshortname']);
            }
        }
        return '1';
    }

    /**
     * Instal a single course.
     * @param string $coursefile
     * @return int
     */
    protected function install_course($coursefile) {
        $precheck = $this->precheck($coursefile);
        if ($precheck) {
            $this->restore_course($coursefile, $precheck);
            $this->feedback['needed'][$precheck['courseshortname']]['success'][] =
                get_string('coursessuccess', 'tool_wbinstaller', $precheck['courseshortname']);
        }
        return 1;
    }

    /**
     * Instal a single course.
     * @param string $coursefile
     * @return mixed
     */
    protected function precheck($coursefile) {
        $xml = simplexml_load_file($coursefile . '/moodle_backup.xml');
        $courseshortname = $this->get_course_short_name($xml);
        $courseoriginalid = $this->get_course_og_id($xml);
        if (!$courseshortname || !$courseoriginalid) {
            $this->feedback['needed'][$coursefile]['error'][] =
              get_string('coursesnoshortname', 'tool_wbinstaller', $coursefile);
            return 0;
        } else if ($course = $this->course_exists($courseshortname)) {
            $this->matchingcourseids[$courseoriginalid] = $course->id;
            $this->feedback['needed'][$courseshortname]['warning'][] =
              get_string('coursesduplicateshortname', 'tool_wbinstaller', $courseshortname);
            return 0;
        }
        return [
            "courseshortname" => $courseshortname,
            "courseoriginalid" => $courseoriginalid,
        ];
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
        return (string)$xml->information->original_course_id;
    }

    /**
     * Check if course already exists.
     * @param string $shortname
     * @return object
     */
    protected function course_exists($shortname) {
        global $DB;
        $course = $DB->get_record('course', ['shortname' => $shortname], 'id');
        return $course;
    }

    /**
     * Restore the course.
     * @param string $coursefile
     * @param string $precheck
     * @return mixed
     */
    protected function restore_course($coursefile, $precheck) {
        global $USER, $CFG;
        $destination = $CFG->tempdir . '/backup/' . basename($coursefile);
        if (!is_dir($destination)) {
            mkdir($destination, 0777, true);
        }
        if (!$this->copy_directory($coursefile, $destination)) {
            $this->feedback['needed'][$precheck['courseshortname']]['error'][] =
              get_string('coursesfailextract', 'tool_wbinstaller');
            return;
        }
        $newcourse = new stdClass();
        $newcourse->fullname = 'Temporary Course Fullname';
        $newcourse->shortname = 'temp_' . uniqid();
        $newcourse->category = 1;
        $newcourse->format = 'topics';
        $newcourse->visible = 0;
        $newcourse = create_course($newcourse);
        $this->matchingcourseids[$precheck['courseoriginalid']] = $newcourse->id;

        $rc = $this->create_restore_controller($coursefile, $newcourse->id, $USER->id);
        if (!$rc->execute_precheck()) {
            $this->feedback['needed'][$precheck['courseshortname']]['error'][] =
                get_string('coursesfailextract', 'tool_wbinstaller', $precheck['courseshortname']);
            delete_course($newcourse->id, false);
            fulldelete($destination);
            return;
        }

        try {
            $rc->execute_plan();
            $rc->destroy();
            fulldelete($destination);
            $coursecontent = get_course($newcourse->id);
            if (
                empty($coursecontent->sections) &&
                $coursecontent->fullname == 'Temporary Course Fullname'
            ) {
                $this->feedback['needed'][$precheck['courseshortname']]['error'][] =
                    get_string('coursesfailextract', 'tool_wbinstaller', $precheck['courseshortname']);
                delete_course($newcourse->id, false);
                fulldelete($destination);
                return;
            }
        } catch (\Exception $e) {
            delete_course($newcourse->id, false);
            $this->feedback['needed'][$precheck['courseshortname']]['error'][] =
                get_string('oldermoodlebackupversion', 'tool_wbinstaller');
            return;
        }
    }

    /**
     * Recursively copies a directory.
     *
     * @param string $coursefile
     * @param string $newcourseid
     * @param string $userid
     * @return restore_controller Restore controller.
     */
    protected function create_restore_controller($coursefile, $newcourseid, $userid) {
        return new restore_controller(
            basename($coursefile),
            $newcourseid,
            backup::INTERACTIVE_NO,
            backup::MODE_IMPORT,
            $userid,
            backup::TARGET_NEW_COURSE
        );
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

    /**
     * Check if course already exists.
     * @return array
     */
    public function get_matchingcourseids() {
        return $this->matchingcourseids;
    }
}
