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
use tool_installaddon_installer;

/**
 * Class tool_wbinstaller
 *
 * @package     tool_wbinstaller
 * @author      Jacob Viertel
 * @copyright  2023 Wunderbyte GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../../../config.php');
require(__DIR__.'/../../../../lib/setup.php');


require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/upgradelib.php');

\require_login();
\require_capability('moodle/site:config', context_system::instance());
// Set up the admin external page.
\admin_externalpage_setup('tool_wbinstaller');
class pluginsInstaller extends wbInstaller {
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
    }
    /**
     * Exceute the installer.
     * @return array
     */
    public function execute() {
        global $PAGE;

        // Set the page context.
        $PAGE->set_context(context_system::instance());

        $jsonstring = file_get_contents($this->recipe . '.json');
        $jsonarray = json_decode($jsonstring, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->errors[] = 'Error decoding JSON: ' . json_last_error_msg();
        }
        $installable = $this->download_install_plugins_testing($jsonarray);
        $this->manual_install_plugins($installable);
        return $installable;
    }

    /**
     * Exceute the installer.
     * @param array $installable
     */
    public function upgrade_install_plugins_recipe($installable) {
        if (!empty($installable)) {
            try {
                ob_start();
                upgrade_install_plugins($installable, 1, get_string('installfromzip', 'tool_wbinstaller'),
                    new moodle_url('/admin/tool/wbinstaller/index.php', ['installzipconfirm' => 1])
                );
                ob_end_clean();
            } catch (Exception $e) {
                // Catch and log any errors during the installation process
                $this->errors[] = 'Plugin installation error: ' . $e->getMessage();
            }
        }
    }

    /**
     * Manual plugin installation.
     * @param array $installable
     */
    public function manual_install_plugins($installable) {
        global $CFG;
        if (!empty($installable)) {
            foreach ($installable as $plugin) {
                $zipfile = $plugin->zipfilepath;
                $component = $plugin->component;

                // Determine the target directory for the plugin.
                list($type, $name) = explode('_', $component, 2);
                if ($type == 'tool') {
                    $type = "admin/tool";
                }
                $targetdir = $CFG->dirroot . "/$type";

                // Create the target directory if it doesn't exist.
                if (!is_dir($targetdir)) {
                    mkdir($targetdir, 0777, true);
                }

                // Extract the ZIP file to the target directory.
                $zip = new \ZipArchive();
                if ($zip->open($zipfile) === true) {
                    $zip->extractTo($targetdir);
                    $zip->close();
                } else {
                    $this->errors[] = "Failed to extract $zipfile";
                    continue;
                }

                // Clean up the ZIP file.
                unlink($zipfile);
            }

            // Set up the upgrade environment.

            manager::write_close();
            rebuild_course_cache(0, true);
            // Capture output.
            ob_start();
            try {
                upgrade_noncore(true);
                ob_end_clean();
            } catch (\moodle_exception $e) {
                ob_end_clean();
            }
        }
    }

    /**
     * Exceute the installer.
     * @param array $jsonarray
     * @return mixed
     */
    public function download_install_plugins_testing($jsonarray) {
        require_sesskey();
        $installer = tool_installaddon_installer::instance();
        $installable = [];
        if (isset($jsonarray['links'])) {
            if (!is_dir($this->recipe)) {
                mkdir($this->recipe, 0777, true);
            }
            foreach ($jsonarray['links'] as $url) {
                $zipfile = $this->recipe . '/' . basename($url);
                if (download_file_content($url, null, null, true, 300, 20, true, $zipfile)) {
                    $component = $installer->detect_plugin_component($zipfile);
                    $installable[] = (object)[
                        'component' => $component, // Will be detected during the installation process.
                        'zipfilepath' => $zipfile,
                    ];
                } else {
                    $this->errors[] = get_string('filedownloadfailed', 'tool_wbinstaller', $url);
                }
            }
        }
        return $installable;
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
                new moodle_url('/admin/tool/wbinstaller/index.php', ['installzipconfirm' => 1])
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
