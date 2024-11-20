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

require_once(__DIR__ . '/../../../../config.php');
require_once(__DIR__ . '/../../../../lib/setup.php');
global $CFG;
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/moodlelib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
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

    /** @var string Timestamp for directory identification. */
    public $timestamp;
    /**
     * Entities constructor.
     * @param mixed $recipe
     */
    public function __construct($recipe) {
        $this->recipe = $recipe;
        $this->progress = 0;
        $this->timestamp = date('Y-m-d_H-i-s');
    }

    /**
     * Exceute the installer.
     * @param string $extractpath
     * @param \tool_wbinstaller\wbCheck $parent
     * @return string
     */
    public function execute($extractpath, $parent = null) {
        $coursespath = $extractpath . $this->recipe['path'];
        foreach (glob("$coursespath/*") as $coursefile) {
            $this->install_course($coursefile, $parent);
        }
        return '1';
    }

    /**
     * Exceute the installer.
     * @param string $extractpath
     * @param \tool_wbinstaller\wbCheck $parent
     * @return string
     */
    public function check($extractpath, $parent) {
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
     * @param \tool_wbinstaller\wbCheck $parent
     * @return int
     */
    protected function install_course($coursefile, $parent) {
        $precheck = $this->precheck($coursefile);
        if ($precheck) {
            $this->restore_course($coursefile, $precheck, $parent);
            $this->feedback['needed'][$precheck['courseshortname']]['success'][] = $this->get_success_message($precheck);
        }
        return 1;
    }

    /**
     * Recursively copies a directory.
     *
     * @param array $precheck
     * @return string
     */
    protected function get_success_message($precheck): string {
        global $DB;
        $msgparams = new stdClass();
        $msgparams->courseshortname = $precheck['courseshortname'];
        $msgparams->category = $DB->get_field('course_categories', 'name', ['id' => 1]);
        return get_string('coursessuccess', 'tool_wbinstaller', $msgparams);
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
        foreach (glob($coursefile . "/activities/adaptivequiz_*") as $activityfolder) {
            $activityid = $this->extract_activity_id($activityfolder, 'adaptivequiz');
            if ($activityid) {
                $this->matchingids['components'][$activityid] = $activityid;
            }
        }
        foreach (glob($coursefile . "/activities/quiz_*") as $activityfolder) {
            $activityid = $this->extract_activity_id($activityfolder, 'quiz');
            if ($activityid) {
                $this->matchingids['quizid'][$activityid] = $activityid;
            }
        }

        if (!$courseshortname || !$courseoriginalid) {
            $this->feedback['needed'][$coursefile]['error'][] =
              get_string('coursesnoshortname', 'tool_wbinstaller', $coursefile);
            return 0;
        } else if ($course = $this->course_exists($courseshortname)) {
            $this->matchingids['courses'][$courseoriginalid] = $course->id;
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
     * Get activity id of quiz
     * @param string $activityfolder
     * @param string $component
     * @return mixed
     */
    protected function extract_activity_id($activityfolder, $component) {
        $xmlpath = $activityfolder . '/' . $component . '.xml';
        if (!file_exists($xmlpath)) {
            return null;
        }
        $xml = simplexml_load_file($xmlpath);
        $activityid = (string)$xml['id'];
        return $activityid;
    }

    /**
     * Get the course short name.
     * @param \simpleXMLElement $xml
     * @return string
     */
    private function get_course_short_name($xml) {
        return (string)$xml->information->original_course_shortname;
    }

    /**
     * Get the course id.
     * @param \simpleXMLElement $xml
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
     * @param \tool_wbinstaller\wbCheck $parent
     * @return mixed
     */
    protected function restore_course($coursefile, $precheck, $parent) {
        global $USER, $CFG, $DB;
        $destination = $CFG->tempdir . '/backup/' . basename($coursefile);
        if (!is_dir($destination)) {
            mkdir($destination, 0777, true);
        }
        if (!$this->copy_directory($coursefile, $destination)) {
            $this->feedback['needed'][$precheck['courseshortname']]['error'][] =
              get_string('coursesfailextract', 'tool_wbinstaller');
            return;
        }
        $subcategoryname = preg_replace('/\.[^.]+$/', '', $parent->filename);
        $category = $this->get_course_category($subcategoryname);
        if (!$category) {
            $this->feedback['needed'][$precheck['courseshortname']]['error'][] =
              get_string('coursescategorynotfound', 'tool_wbinstaller');
            return;
        }
        $this->feedback['needed'][$precheck['courseshortname']]['success'][] =
              get_string('coursescategoryfound', 'tool_wbinstaller', $category->name);
        $newcourse = new stdClass();
        $newcourse->fullname = 'Temporary Course Fullname';
        $newcourse->shortname = 'temp_' . uniqid();
        $newcourse->category = $category->id;
        $newcourse->format = 'topics';
        $newcourse->visible = 0;
        $newcourse->timecreated = time();
        $newcourse->timemodified = time();
        $newcourse->newsitems = 0;
        $newcourse = create_course($newcourse);
        $this->matchingids['courses'][$precheck['courseoriginalid']] = $newcourse->id;
        $this->restore_with_controller($coursefile, $newcourse);
        $this->force_course_visibility($newcourse->id);
        $this->update_matching_componentids(
            $coursefile,
            $newcourse->id,
            '/activities/adaptivequiz_*',
            'adaptivequiz',
            'components'
        );
        $this->update_matching_componentids(
            $coursefile,
            $newcourse->id,
            '/activities/quiz_*',
            'quiz',
            'quizid'
        );
        return;
    }

    /**
     * Recursively copies a directory.
     * @param string $coursefile
     * @param string $newcourseid
     * @param string $componentfolder
     * @param string $componenttable
     * @param string $matchinglabel
     */
    protected function update_matching_componentids(
        $coursefile,
        $newcourseid,
        $componentfolder,
        $componenttable,
        $matchinglabel
    ) {
        global $DB;
        $manager = $DB->get_manager();
        if ($manager->table_exists($componenttable)) {
            $ogcomponentids = [];
            foreach (glob($coursefile . $componentfolder) as $activityfolder) {
                $activityid = $this->extract_activity_id($activityfolder, $componenttable);
                if ($activityid) {
                    $ogcomponentids[] = $activityid;
                }
            }
            $newcoursefile = $DB->get_records($componenttable, ['course' => $newcourseid], null, 'id');
            $newcoursefileids = array_keys($newcoursefile);
            if (
                count($ogcomponentids) > 0 &&
                count($ogcomponentids) == count($newcoursefile)
            ) {
                $componentmatch = array_combine($ogcomponentids, $newcoursefileids);
                foreach ($componentmatch as $ogid => $newid) {
                    $this->matchingids[$matchinglabel][$ogid] = $newid;
                }
            }
        }
    }

    /**
     * Returns course category with lowest id or false to install courses.
     * @param string $subcategoryname
     * @return mixed
     */
    protected function get_course_category($subcategoryname) {
        global $DB;
        $timestamedsubcategoryname =  $this->timestamp . $subcategoryname;
        $parentcategoryname = 'WbInstall';
        $parentcategory = $DB->get_record(
          'course_categories',
          ['name' => $parentcategoryname],
          'id, name',
        );

        if (!$parentcategory) {
          $parentcategory = $this->set_course_category($parentcategoryname, $parentcategory);
        }
        $subcategory = $DB->get_record(
          'course_categories',
          [
            'name' => $timestamedsubcategoryname,
            'parent' => $parentcategory->id,
          ],
          'id, name',
        );
        if (!$subcategory) {
            $subcategory = $this->set_course_category($timestamedsubcategoryname, $parentcategory);
        }
        return $subcategory;
    }

    /**
     * Returns course category with lowest id or false to install courses.
     * @param string $parentcategoryname
     * @param mixed $parentcategory
     * @return mixed
     */
    protected function set_course_category($parentcategoryname, $parentcategory) {
        global $DB;
        $newcategory = new stdClass();
        $newcategory->name = $parentcategoryname;
        $newcategory->idnumber = null;
        $newcategory->description = $parentcategoryname;
        $newcategory->descriptionformat = FORMAT_HTML;
        $newcategory->parent = $parentcategory->id ?? 0;
        $newcategory->sortorder = 0;
        $newcategory->visible = 1;
        $newcategory->visibleold = 1;
        $newcategory->timemodified = time();
        $newcategory->depth = 1;
        $newcategory->path = '';

        // Insert the new category into the database.
        $newcategory->id = $DB->insert_record('course_categories', $newcategory);

        // Update the path and depth.
        $newcategory->path = '/' . $newcategory->id;
        $DB->update_record('course_categories', $newcategory);

        return $newcategory;
    }


    /**
     * Recursively copies a directory.
     *
     * @param string $courseid
     */
    protected function force_course_visibility($courseid) {
        global $DB;
        $DB->set_field('course', 'visible', 0, ['id' => $courseid]);
        rebuild_course_cache($courseid, true);
    }

    /**
     * Recursively copies a directory.
     *
     * @param string $coursefile
     * @param object $newcourse
     */
    protected function restore_with_controller($coursefile, $newcourse) {
        global $USER, $CFG;

        // Path to the backup files (uncompressed course backup folder).
        $restorepath = $coursefile;

        $destination = $CFG->tempdir . '/backup/' . basename($coursefile);
        if (!is_dir($destination)) {
            mkdir($destination, 0777, true);
        }

        // Copy course backup content to the temp backup directory.
        $this->copy_directory($coursefile, $destination);

        // Create the restore controller with the backup directory and the target course ID.
        $rc = new restore_controller(
            basename($restorepath),
            $newcourse->id,
            backup::INTERACTIVE_NO,
            backup::MODE_IMPORT,
            $USER->id,
            backup::TARGET_NEW_COURSE
        );

        if (!$rc->execute_precheck()) {
            $rc->destroy();
        }
        try {
            $rc->execute_plan();
            $rc->destroy();
            fulldelete($destination);
        } catch (\Exception $e) {
            rebuild_course_cache($newcourse->id, true);
            fulldelete($destination);
        }
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
