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
use Exception;
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
global $CFG;
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->libdir . '/upgradelib.php');

\require_login();
\require_capability('moodle/site:config', context_system::instance());
// Set up the admin external page.
\admin_externalpage_setup('tool_wbinstaller');

/**
 * Class pluginsInstaller
 *
 * @package     tool_wbinstaller
 * @author      Jacob Viertel
 * @copyright  2023 Wunderbyte GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class pluginsInstaller extends wbInstaller {
    /**
     * Entities constructor.
     * @param string $recipe
     * @param int $dbid
     */
    public function __construct($recipe, $dbid=null, $optionalplugins=null) {
        $this->dbid = $dbid;
        $this->recipe = $recipe;
        $this->progress = 0;
        $this->optionalplugins = $optionalplugins;
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
            $this->feedback['plugins']['error'][] =
              get_string('jsonfaildecoding', 'tool_wbinstaller', json_last_error_msg());
            $this->set_status(2);
        }
        require_sesskey();
        $installer = tool_installaddon_installer::instance();
        $installable = [];
        if (isset($jsonarray)) {
            if (!is_dir($this->recipe)) {
                mkdir($this->recipe, 0777, true);
            }
            foreach ($jsonarray as $type => $plugins) {
                foreach ($plugins as $gitzipurl) {
                    if (
                      $type != 'optional' ||
                      in_array($gitzipurl, $this->optionalplugins)
                    ) {
                        $installable[] = $this->download_install_plugins_testing($gitzipurl, $type, $installer);
                    }
                }
            }
        }
        $this->manual_install_plugins($installable);
        return 1;
    }

    /**
     * Exceute the installer.
     * @param array $jsonarray
     * @return mixed
     */
    public function download_install_plugins_testing($gitzipurl, $type, $installer) {
        $zipfile = $this->recipe . '/' . basename($gitzipurl);
        if (download_file_content($gitzipurl, null, null, true, 300, 20, true, $zipfile)) {
            $component = $installer->detect_plugin_component($zipfile);
            return (object)[
                'component' => $component, // Will be detected during the installation process.
                'zipfilepath' => $zipfile,
                'url' => $gitzipurl,
                'type' => $type,
            ];
        } else {
            $this->feedback[$type][$gitzipurl]['error'][] = get_string('filedownloadfailed', 'tool_wbinstaller', $gitzipurl);
            $this->set_status(2);
        }
    }
    /**
     * Exceute the installer.
     * @return array
     */
    public function check() {
        $jsonstring = file_get_contents($this->recipe . '.json');
        $jsonarray = json_decode($jsonstring, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->feedback['plugins']['error'][] =
              get_string('jsonfaildecoding', 'tool_wbinstaller', json_last_error_msg());
            $this->set_status(2);
        }
        foreach ($jsonarray as $type => $plugins) {
            foreach ($plugins as $gitzipurl) {
                $this->check_plugin_compability($gitzipurl, $type);
            }
        }
    }

    /**
     * Exceute the installer.
     * @param array $installable
     */
    public function check_plugin_compability($gitzipurl, $type) {
        $plugincontent = $this->get_github_file_content($gitzipurl);
        if ($plugincontent) {
            $plugin = $this->parse_version_file($plugincontent);
            if (isset($plugin['component'])) {
                $installedversion = $this->is_component_installed($plugin['component']);
                if ($installedversion) {
                    $a = new stdClass();
                    $a->name = $plugin['component'];
                    $a->version = $installedversion;
                    $this->feedback[$type][$gitzipurl]['warning'][] =
                      get_string('pluginduplicate', 'tool_wbinstaller', $a);
                    $this->set_status(1);

                } else {
                    $this->feedback[$type][$gitzipurl]['success'][] =
                      get_string('pluginnotinstalled', 'tool_wbinstaller', $plugin['component']);
                }
            } else {
                $this->feedback[$type][$gitzipurl]['error'][] =
                  get_string('pluginfailedinformation', 'tool_wbinstaller');
                $this->set_status(2);
            }
        } else {
            $this->feedback[$type][$gitzipurl]['error'][] =
              get_string('pluginfailedinformation', 'tool_wbinstaller');
            $this->set_status(2);
        }
        return 1;
    }

    public function get_github_file_content($url) {
        $urlparts = explode('/', parse_url($url, PHP_URL_PATH));
        $owner = $urlparts[1];
        $repo = $urlparts[2];
        $branchtag = null;
        if ($urlparts[5] == 'tags') {
            $branchtag = "?ref=refs/tags/" . str_replace('.zip', '', $urlparts[6]);
        } else {
            $branchtag = "?ref=" . str_replace('.zip', '', $urlparts[6]);
        }
        $apiurl = "https://api.github.com/repos/" . $owner . "/" . $repo . "/contents/version.php" . $branchtag;
        $token = null;
        $apitoken = get_config('tool_wbinstaller', 'apitoken');
        if ($apitoken) {
            $token = 'token ' . $apitoken;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiurl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'User-Agent: PHP-cURL-Request',
            'Authorization: ' . $token
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        if (isset($data['content'])) {
            return base64_decode($data['content']);
        }
        return null;
    }

    public function parse_version_file($content) {
        $plugin = [];
        if (preg_match('/\$plugin->component\s*=\s*[\'"]([^\'"]+)[\'"]\s*;/', $content, $matches)) {
            $plugin['component'] = $matches[1];
        }
        if (preg_match('/\$plugin->version\s*=\s*([0-9]+)\s*;/', $content, $matches)) {
            $plugin['version'] = $matches[1];
        }
        return $plugin;
    }

    public function is_component_installed($componentname) {
        global $DB;
        $installedplugin = $DB->get_record('config_plugins', ['plugin' => $componentname, 'name' => 'version']);
        if ($installedplugin) {
            return $installedplugin->value;
        }
        return false;
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
                $this->feedback['plugins']['error'][] = 'Plugin installation error: ' . $e->getMessage();
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
                list($type, $name) = explode('_', $component, 2);
                if ($type == 'tool') {
                    $type = "admin/tool";
                }
                $targetdir = $CFG->dirroot . "/$type";
                if (!is_dir($targetdir)) {
                    mkdir($targetdir, 0777, true);
                }
                $zip = new \ZipArchive();
                if ($zip->open($zipfile) === true) {
                    $zip->extractTo($targetdir);
                    $zip->close();
                } else {
                    $this->feedback[$plugin->type][$plugin->url]['error'][] = "Failed to extract $plugin->component";
                    $this->set_status(2);
                    continue;
                }
                $this->feedback[$plugin->type][$plugin->url]['success'][] = "Successfully installed $plugin->component";
                unlink($zipfile);
            }
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
}
