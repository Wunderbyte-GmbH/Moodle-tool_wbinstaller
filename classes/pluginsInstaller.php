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

use moodle_url;
use context_system;
use core\session\manager;
use core_plugin_manager;
use tool_installaddon_installer;

/**
 * Class tool_wbinstaller
 *
 * @package     tool_wbinstaller
 * @author      Jacob Viertel
 * @copyright  2023 Wunderbyte GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pluginsInstaller extends wbInstaller {
    /**
     * Entities constructor.
     */
    public function __construct($recipe, $dbid) {
        $this->dbid = $dbid;
        $this->recipe = $recipe;
        $this->progress = 0;
    }
    /**
     * Exceute the installer.
     * @param string $file
     * @return array
     */
    public function execute() {
        global $PAGE, $CFG;
        // Set the context and require login.
        $context = context_system::instance();
        $PAGE->set_context($context);
        require_login();

        // Setup the page URL (this is necessary for handling redirection and context).
        $PAGE->set_url(new moodle_url('/admin/tool/wbinstaller/index.php'));

        // Enable maintenance mode before starting the upgrade process.
        manager::write_close();
        //set_config('maintenance_enabled', 1);

        //$result = $this->download_install_plugins();
        $result = $this->download_install_plugins_testing();
        // Disable maintenance mode after the upgrade process is complete
        //set_config('maintenance_enabled', 0);

        return $result;
    }

    /**
     * Exceute the installer.
     * @return array
     */
    public function download_install_plugins_testing() {
        return 1;
    }

    /**
     * Exceute the installer.
     * @return array
     */
    public function download_install_plugins() {
        global $CFG, $DB;
        require_once($CFG->libdir . '/filelib.php');
        require_once($CFG->libdir . '/upgradelib.php');
        require_once($CFG->dirroot . '/cache/lib.php');

        $installer = tool_installaddon_installer::instance();
        $feedback = [];
        $installable = [];
        $tempdir = make_temp_directory('tool_wbinstaller');

        foreach ($this->recipe as $url) {
            $zipfile = $tempdir . '/' . basename($url);
            if (download_file_content($url, null, null, true, 300, 20, true, $zipfile)) {
                $component = $installer->detect_plugin_component($zipfile);
                if ($component) {
                    $feedback[$url] = $component;
                    $installable[] = (object)[
                        'component' => $component,
                        'zipfilepath' => $zipfile,
                    ];
                } else {
                    $feedback[$url] = get_string('componentdetectfailed', 'tool_wbinstaller', $url);
                }
            } else {
                $feedback[$url] = get_string('filedownloadfailed', 'tool_wbinstaller', $url);
            }
            $this->update_install_progress('subprogress');
        }
        if (!empty($installable)) {
            // Perform the upgrade installation process.
            upgrade_install_plugins($installable, true, get_string('installfromzip', 'tool_wbinstaller'),
                new moodle_url('/admin/tool/wbinstaller/index.php', array('installzipconfirm' => 1))
            );
            // Clear all caches to ensure Moodle recognizes the new plugins.
            purge_all_caches();

            // Verify that the plugins have been installed.
            foreach ($installable as $plugin) {
                if ($DB->record_exists('config_plugins', ['plugin' => $plugin->component])) {
                    $feedback[$plugin->component] = get_string('installed', 'tool_wbinstaller');
                } else {
                    $feedback[$plugin->component] = get_string('installfailed', 'tool_wbinstaller');
                }
            }
            $feedback['status'] = 'success';
        } else {
            $feedback['status'] = 'no_installable_files';
        }
        return $feedback;
    }
}
