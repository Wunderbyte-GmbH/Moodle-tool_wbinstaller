<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Code to be executed after the plugin's database scheme has been installed is defined here.
 *
 * @package     tool_wbinstaller
 * @category    upgrade
 * @copyright   2024 Wunderbyte GmbH <info@wunderbyte.at>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Custom code to be run on installing the plugin.
 */
function xmldb_tool_wbinstaller_install() {
    global $DB;

    $role = $DB->get_record('role', ['shortname' => 'installmanager']);
    if (empty($role->id)) {
        $sql = "SELECT MAX(sortorder)+1 AS id FROM {role}";
        $max = $DB->get_record_sql($sql, []);

        $role = (object) [
            'name' => 'Install Manager',
            'shortname' => 'installmanager',
            'description' => get_string('wbinstallerroledescription', 'tool_wbinstaller'),
            'sortorder' => $max->id,
            'archetype' => '',
        ];
        $role->id = $DB->insert_record('role', $role);
    }

    // Ensure, that this role is assigned in the required context levels.
    $chk = $DB->get_record('role_context_levels', ['roleid' => $role->id, 'contextlevel' => CONTEXT_SYSTEM]);
    if (empty($chk->id)) {
        $DB->insert_record('role_context_levels', ['roleid' => $role->id, 'contextlevel' => CONTEXT_SYSTEM]);
    }
    // Ensure, that this role has the required capabilities.
    $ctx = \context_system::instance();
    $caps = [
        'tool/wbinstaller:caninstall',
        'tool/wbinstaller:canexport',
    ];
    foreach ($caps as $cap) {
        $chk = $DB->get_record('role_capabilities', [
                'contextid' => $ctx->id,
                'roleid' => $role->id,
                'capability' => $cap,
                'permission' => 1,
            ]);
        if (empty($chk->id)) {
            $DB->insert_record('role_capabilities', [
                'contextid' => $ctx->id,
                'roleid' => $role->id,
                'capability' => $cap,
                'permission' => 1,
                'timemodified' => time(),
                'modifierid' => 2,
            ]);
        }
    }
    return true;
}
