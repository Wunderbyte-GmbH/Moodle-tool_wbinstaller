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
 * adele plugin external functions and service definitions.
 *
 * @package     tool_wbinstaller
 * @author      Jacob Viertel
 * @copyright  2023 Wunderbyte GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'tool_wbinstaller_install_recipe' => [
        'classname' => 'tool_wbinstaller\external\install_recipe',
        'classpath' => '',
        'description' => 'Install uploaded recipe.',
        'type' => 'write',
        'ajax' => true,
    ],
    'tool_wbinstaller_get_install_progress' => [
        'classname' => 'tool_wbinstaller\external\get_install_progress',
        'classpath' => '',
        'description' => 'Get install progress.',
        'type' => 'write',
        'ajax' => true,
    ],
    'tool_wbinstaller_get_exportable_courses' => [
        'classname' => 'tool_wbinstaller\external\get_exportable_courses',
        'classpath' => '',
        'description' => 'Get exportable courses.',
        'type' => 'write',
        'ajax' => true,
    ],
    'tool_wbinstaller_download_recipe' => [
        'classname' => 'tool_wbinstaller\external\download_recipe',
        'classpath' => '',
        'description' => 'Get exportable courses.',
        'type' => 'write',
        'ajax' => true,
    ],
];
