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
use core_component;
use Exception;
use stdClass;
use tool_installaddon_installer;

/**
 * Class tool_wbinstaller
 *
 * @package     tool_wbinstaller
 * @author      Jacob Viertel
 * @copyright  2023 Wunderbyte GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__.'/../../../../config.php');
require_once(__DIR__.'/../../../../lib/setup.php');
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

    /** @var object Known subplugins. */
    public $knownsubplugins;
    /** @var tool_installaddon_installer Plugin installer. */
    protected $addoninstaller;

    /**
     * Entities constructor.
     * @param array $recipe
     * @param int $dbid
     * @param array $optionalplugins
     */
    public function __construct($recipe, $dbid=null, $optionalplugins=null) {
        $this->dbid = $dbid;
        $this->recipe = $recipe;
        $this->progress = 0;
        $this->optionalplugins = $optionalplugins;
        $this->addoninstaller = tool_installaddon_installer::instance();
        $this->knownsubplugins = [
          'adaptivequizcatmodel_catquiz' => 'mod',
        ];

    }

    /**
     * Exceute the installer.
     * @param string $extractpath
     * @return int
     */
    public function execute($extractpath) {
        global $PAGE, $DB;
        // Set the page context.
        $PAGE->set_context(context_system::instance());
        $installer = tool_installaddon_installer::instance();
        $installable = [];
        if (isset($this->recipe)) {
            foreach ($this->recipe as $type => $plugins) {
                foreach ($plugins as $gitzipurl) {
                    if (
                      $type != 'optional' ||
                      in_array($gitzipurl, $this->optionalplugins)
                    ) {
                        $install = $this->check_plugin_compability($gitzipurl, $type, true);
                        if ($install != 2) {
                            $installable[] = $this->download_install_plugins_testing($gitzipurl, $type, $installer, $install);
                        }
                    }
                }
            }
        }
        $this->manual_install_plugins($installable);
        $this->upgraderunning = $DB->get_field('config', 'value', ['name' => 'upgraderunning']);
        $DB->set_field('config', 'value', '0', ['name' => 'upgraderunning']);
        return 1;
    }

    /**
     * Exceute the installer.
     * @param string $gitzipurl
     * @param string $type
     * @param mixed $installer
     * @param string $install
     */
    public function download_install_plugins_testing($gitzipurl, $type, $installer, $install) {
        $zipfile = $this->recipe . '/' . $install . '.zip';
        if (download_file_content($gitzipurl, null, null, true, 300, 20, true, $zipfile)) {
            $component = $installer->detect_plugin_component($zipfile);
            return (object)[
                'component' => $component,
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
     * @param string $extractpath
     */
    public function check($extractpath) {
        foreach ($this->recipe as $type => $plugins) {
            foreach ($plugins as $gitzipurl) {
                $this->check_plugin_compability($gitzipurl, $type);
            }
        }
    }

    /**
     * Exceute the installer.
     * @param string $gitzipurl
     * @param string $type
     * @param bool $execute
     * @return int
     */
    public function check_plugin_compability($gitzipurl, $type, $execute = false) {
        $plugincontent = $this->get_github_file_content($gitzipurl);
        if ($plugincontent) {
            $plugin = $this->parse_version_file($plugincontent);
            if (isset($plugin['component'])) {
                $installedversion = $this->is_component_installed($plugin['component']);
                $a = new stdClass();
                $a->name = $plugin['component'] ?? '';
                $a->installedversion = (int)$installedversion ?? '';
                $a->componentversion = (int)$plugin['version'] ?? '';
                if ($a->installedversion == 0) {
                    if ($execute ) {
                        $this->feedback[$type][$gitzipurl]['success'][] =
                            get_string('pluginnotinstalled', 'tool_wbinstaller', $plugin['component']);
                        return $plugin['component'];
                    } else {
                        $this->feedback[$type][$gitzipurl]['success'][] =
                          get_string('pluginnotinstalled', 'tool_wbinstaller', $plugin['component']);
                    }
                } else if ($a->installedversion > $a->componentversion) {
                    if ($execute) {
                        $this->feedback[$type][$gitzipurl]['warning'][] =
                          get_string('pluginduplicate', 'tool_wbinstaller', $a);
                        return $plugin['component'];
                    } else {
                        $this->feedback[$type][$gitzipurl]['warning'][] =
                          get_string('pluginduplicate', 'tool_wbinstaller', $a);
                        $this->set_status(1);
                    }
                } else if ($a->installedversion <= $a->componentversion) {
                    if ($execute) {
                        $this->feedback[$type][$gitzipurl]['error'][] =
                          get_string('pluginolder', 'tool_wbinstaller', $a);
                        return 2;
                    } else {
                        $this->feedback[$type][$gitzipurl]['error'][] =
                          get_string('pluginolder', 'tool_wbinstaller', $a);
                        $this->set_status(2);
                    }
                }
            } else {
                $this->feedback[$type][$gitzipurl]['error'][] =
                  get_string('pluginfailedinformation', 'tool_wbinstaller');
                $this->set_status(2);
                if ($execute) {
                    return 2;
                }
            }
        } else {
            $this->feedback[$type][$gitzipurl]['error'][] =
              get_string('pluginfailedinformation', 'tool_wbinstaller');
            $this->set_status(2);
            if ($execute) {
                return 2;
            }
        }
        return 1;
    }

    /**
     * Exceute the installer.
     * @param string $url
     * @return string
     */
    public function get_github_file_content($url) {
        $urlparts = explode('/', parse_url($url, PHP_URL_PATH));
        $owner = $urlparts[1];
        $repo = $urlparts[2];
        $branchtag = null;
        if ($urlparts[5] == 'tags') {
            $branchtag = "?ref=refs/tags/" . str_replace('.zip', '', $urlparts[6]);
        } else if ($urlparts[6] != null) {
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
            'Authorization: ' . $token,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        if (isset($data['content'])) {
            return base64_decode($data['content']);
        }
        return null;
    }

    /**
     * Exceute the installer.
     * @param string $content
     * @return array
     */
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

    /**
     * Exceute the installer.
     * @param string $componentname
     */
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
        global $CFG, $DB;
        if (!empty($installable)) {
            foreach ($installable as $plugin) {
                $zipfile = $plugin->zipfilepath;
                $component = $this->addoninstaller->detect_plugin_component($zipfile);
                if (!$component) {
                    $this->feedback[$plugin->type][$plugin->url]['error'][] =
                        get_string('plugincomponentdetectfailed', 'tool_wbinstaller');
                    continue;
                }
                // Dynamically split the component into plugin type and name.
                list($plugintype, $pluginname) = core_component::normalize_component($component);
                $plugintypes = core_component::get_plugin_types();

                // Use core_plugin_manager to get the plugin type and directory structure.
                $pluginman = \core_plugin_manager::instance();
                $targetdir = $pluginman->get_plugintype_root($plugintype);

                // Check if it's a core plugin or a subplugin.
                if (!is_dir($targetdir)) {
                    $result = mkdir($targetdir, 0777, true);
                    if (!$result) {
                        // Check if the directory was not created due to insufficient permissions.
                        if (is_dir($targetdir)) {
                            $this->feedback[$plugin->type][$plugin->url]['error'][] =
                              get_string('jsonfailalreadyexist', 'tool_wbinstaller', $targetdir);
                        } else {
                            $this->feedback[$plugin->type][$plugin->url]['error'][] =
                              get_string('jsonfailinsufficientpermission', 'tool_wbinstaller', $targetdir);
                        }
                        continue;
                    }
                }
                $zip = new \ZipArchive();
                if ($zip->open($zipfile) === true) {
                    $tempdir = $targetdir . '/temp_extract_' . $pluginname;
                    if (!is_dir($tempdir)) {
                        $result = mkdir($tempdir, 0777, true);
                        if (!$result) {
                            // Similar check for temporary directory creation.
                            if (is_dir($tempdir)) {
                                $this->feedback[$plugin->type][$plugin->url]['error'][] =
                                  get_string('jsonfailalreadyexist', 'tool_wbinstaller', $tempdir);
                            } else {
                                $this->feedback[$plugin->type][$plugin->url]['error'][] =
                                  get_string('jsonfailinsufficientpermission', 'tool_wbinstaller', $tempdir);
                            }
                            continue; // Skip this iteration and move on to the next plugin.
                        }
                    }
                    $zip->extractTo($tempdir);
                    $zip->close();
                    $extracteddirname = null;
                    $handle = opendir($tempdir);
                    while (($entry = readdir($handle)) !== false) {
                        if ($entry != '.' && $entry != '..' && is_dir($tempdir . '/' . $entry)) {
                            $extracteddirname = $entry;
                            break;
                        }
                    }
                    closedir($handle);
                    if ($extracteddirname) {
                        $finaldir = $targetdir . '/' . $pluginname;
                        rename($tempdir . '/' . $extracteddirname, $finaldir);
                        rmdir($tempdir);
                        $this->feedback[$plugin->type][$plugin->url]['success'][] =
                          get_string('installersuccessinstalled', 'tool_wbinstaller', $plugin->component);
                    } else {
                        $this->feedback[$plugin->type][$plugin->url]['error'][] =
                          get_string('installerfailfinddir', 'tool_wbinstaller', $plugin->component);
                        $this->set_status(2);
                    }
                    unlink($zipfile);
                } else {
                    $this->feedback[$plugin->type][$plugin->url]['error'][] =
                    get_string('installerfailextract', 'tool_wbinstaller', $plugin->component);
                    $this->set_status(2);
                }
            }
            manager::write_close();
            rebuild_course_cache(0, true);
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
