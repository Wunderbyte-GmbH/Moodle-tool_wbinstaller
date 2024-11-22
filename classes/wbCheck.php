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

/**
 * Class tool_wbinstaller
 *
 * @package     tool_wbinstaller
 * @author      Jacob Viertel
 * @copyright  2023 Wunderbyte GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class wbCheck {
    /** @var array Content of the recipe. */
    public $recipe;
    /** @var string Name of the recipe. */
    public $filename;
    /** @var array Install errors. */
    public $feedback;
    /** @var array Matching ids array. */
    public $matchingids;
    /** @var array Install errors. */
    public $finished;
    /** @var wbHelper Install errors. */
    public $wbhelper;

    /**
     * Entities constructor.
     * @param array $recipe
     * @param string $filename
     */
    public function __construct($recipe, $filename) {
        $this->wbhelper = new wbHelper();
        $this->recipe = $recipe;
        $this->filename = $filename;
        $this->feedback = [];
        $this->matchingids = [];
        $this->finished = [];
    }

    /**
     * Exceute the installer.
     * @return array
     */
    public function execute() {
        $this->wbhelper->clean_installment_directory();
        raise_memory_limit(MEMORY_EXTRA);
        $extracted = $this->wbhelper->extract_save_zip_file(
            $this->recipe,
            $this->feedback,
            $this->filename,
            'precheck/'
        );
        if (!$extracted) {
            return [
                'feedback' => $this->feedback,
                'finished' => [
                  'status' => false,
                  'currentstep' => 0,
                  'maxstep' => 0,
                ],
            ];
        }
        $this->check_recipe($extracted);
        $this->wbhelper->clean_installment_directory();
        return [
            'feedback' => $this->feedback,
            'finished' => $this->finished,
        ];
    }

    /**
     * Extract and save the zipped file.
     * @param string $extracted
     * @return string
     *
     */
    public function check_recipe($extracted) {
        $directorydata = $this->wbhelper->get_directory_data('/zip/precheck/');
        if ($directorydata['jsoncontent']) {
            foreach ($directorydata['jsoncontent']['steps'] as $step) {
                foreach ($step as $steptype) {
                    $installerclass = __NAMESPACE__ . '\\' . $steptype . 'Installer';
                    if (
                        class_exists($installerclass) &&
                        isset($directorydata['jsoncontent'][$steptype])
                    ) {
                        $instance = new $installerclass($directorydata['jsoncontent'][$steptype]);
                        $instance->check($directorydata['extractpath'], $this);
                        $this->feedback[$steptype] = $instance->get_feedback();
                        $this->matchingids[$steptype] = $instance->get_matchingids();
                    } else {
                        $this->feedback[$steptype]['needed'][$steptype]['error'][] =
                            get_string('classnotfound', 'tool_wbinstaller', $steptype);
                    }
                }
            }
        } else {
            $this->feedback['wbinstaller']['error'][] =
                get_string('installerfailopen', 'tool_wbinstaller');
        }
        return true;
    }

    /**
     * Extract and save the zipped file.
     * @param string $jsonstring
     * @param int $maxstep
     *
     */
    public function get_current_step($jsonstring, $maxstep) {
        global $DB, $USER;
        $sql = "SELECT id, currentstep
            FROM {tool_wbinstaller_install}
            WHERE " . $DB->sql_compare_text('content') . " = " . $DB->sql_compare_text(':content');

        $record = $DB->get_record_sql($sql, ['content' => $jsonstring]);
        if (!$record) {
            $record = new \stdClass();
            $record->userid = $USER->id;
            $record->content = $jsonstring;
            $record->currentstep = 0;
            $record->maxstep = $maxstep;
            $record->timecreated = time();
            $record->timemodified = time();
            $DB->insert_record('tool_wbinstaller_install', $record);
        }
        $this->finished = [
            'status' => false,
            'currentstep' => $record->currentstep,
            'maxstep' => $maxstep,
        ];
    }
}
