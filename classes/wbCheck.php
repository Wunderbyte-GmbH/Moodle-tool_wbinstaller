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

use moodle_exception;
use stdClass;
use ZipArchive;

/**
 * Class tool_wbinstaller
 *
 * @package     tool_wbinstaller
 * @author      Jacob Viertel
 * @copyright  2023 Wunderbyte GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class wbCheck {

    /** @var string Content of the recipe. */
    public $recipe;
    /** @var string Name of the recipe. */
    public $filename;
    /** @var array Install errors. */
    public $feedback;

    /**
     * Entities constructor.
     * @param string $recipe
     * @param string $filename
     */
    public function __construct($recipe, $filename) {
        $this->recipe = $recipe;
        $this->filename = $filename;
        $this->feedback = [];
    }

    /**
     * Exceute the installer.
     * @return array
     */
    public function execute() {
        raise_memory_limit(MEMORY_EXTRA);
        $extracted = $this->extract_save_zip_file();
        reduce_memory_limit(MEMORY_STANDARD);
        if (!$extracted) {
            return $this->feedback;
        }
        $this->check_recipe($extracted);
        $this->clean_after_installment();
        return $this->feedback;
    }

    /**
     * Extract and save the zipped file.
     * @return int
     *
     */
    public function clean_after_installment() {
        $pluginpath = __DIR__ . '/zip/';
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($pluginpath, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($items as $item) {
            $path = $item->getRealPath();
            if ($item->isDir()) {
                rmdir($path);
            } else {
                unlink($path);
            }
        }
        return rmdir($pluginpath);
    }

    /**
     * Extract and save the zipped file.
     * @return string
     *
     */
    public function check_recipe($extracted) {
        $extractpath = __DIR__ . '/zip/precheck/' . str_replace('.zip', '', $this->filename);
        $files = scandir($extractpath);
        foreach ($files as $file) {
            if ($file[0] !== '.') {
                $parts = explode('.', $file);
                $installerclass = __NAMESPACE__ . '\\' . $parts[0] . 'Installer';
                if (class_exists($installerclass)) {
                    $instance = new $installerclass(
                      $extractpath . '/' . $parts[0]
                    );
                    $instance->check();
                    $this->feedback[$parts[0]] = $instance->get_feedback();
                } else {
                    $notfoundinstaller[] = $parts[0];
                }
            }
        }
        return true;
    }

    /**
     * Extract and save the zipped file.
     * @return string
     *
     */
    public function extract_save_zip_file() {
        $extractpath = null;
        $base64string = str_replace('data:application/zip;base64,', '', $this->recipe);
        if (preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $base64string) === 0) {
            $this->feedback['error'][] = ["The base64 string is not valid."];
            return false;
        }
        $filecontent = base64_decode($base64string, true);
        if ($filecontent === false || empty($filecontent)) {
            $this->feedback['error'][] = "Failed to decode base64 content or the content is empty.";
            return false;
        }
        $pluginpath = __DIR__ . '/zip/';
        $zipfilepath = $pluginpath . 'precheck_zip';
        if (!is_dir($pluginpath)) {
            mkdir($pluginpath, 0777, true);
        }
        if (file_put_contents($zipfilepath, $filecontent) === false) {
            $this->feedback['error'][] = "Failed to write the ZIP file to the plugin directory.";
            return false;
        }
        unset($filecontent);
        if (!file_exists($zipfilepath)) {
            $this->feedback['error'][] = "The file does not exist: $zipfilepath";
            return false;
        }
        if (!is_readable($zipfilepath)) {
            $this->feedback['error'][] = "The file is not readable: $zipfilepath";
            return false;
        }
        $zip = new ZipArchive;
        if ($zip->open($zipfilepath) === true) {
            $extractpath = $pluginpath . 'precheck/';
            if (!is_dir($extractpath)) {
                mkdir($extractpath, 0777, true);
            }
            $zip->extractTo($extractpath);
            $zip->close();
        } else {
            $this->feedback['error'][] = "Failed to open the ZIP file.";
        }
        return $extractpath;
    }
}
