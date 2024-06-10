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
 * Web interface for generating plugins.
 *
 * @package    tool_wbinstaller
 * @copyright  2024 Georg Maißer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir.'/moodlelib.php');

admin_externalpage_setup('tool_wbinstaller');

$url = new moodle_url('/admin/tool/wbinstaller/index.php');
$PAGE->set_url($url);
$PAGE->set_title(get_string('pluginname', 'tool_wbinstaller'));
$PAGE->set_heading(get_string('pluginname', 'tool_wbinstaller'));

$step = optional_param('step', '0', PARAM_INT);
$component = optional_param('component1', '', PARAM_TEXT);

$returnurl = new moodle_url('/admin/tool/wbinstaller/index.php');
echo $OUTPUT->header();
echo 'hello';
echo $OUTPUT->footer();
