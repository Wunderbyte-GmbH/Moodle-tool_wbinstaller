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

namespace tool_wbinstaller;

/**
 * Filesystem and repository setup for WB Installer recipes.
 *
 * @package     tool_wbinstaller
 * @copyright   2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @author      Mahdi Poustini
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filesystemInstaller extends wbInstaller {
    /**
     * Constructor.
     * @param array $recipe
     */
    public function __construct($recipe) {
        $this->recipe = $recipe;
        $this->progress = 0;
    }

    /**
     * Execute installer.
     * @param string $extractpath
     * @param \tool_wbinstaller\wbCheck $parent
     * @return int
     */
    public function execute($extractpath, $parent = null) {
        global $CFG;

        if (!empty($this->recipe['enable_filesystem_repository'])) {
            $this->enable_filesystem_repository();
        }

        $moodledata = $this->recipe['moodledata'] ?? $CFG->dataroot;
        $dirs = $this->recipe['dirs'] ?? [];
        foreach ($dirs as $dir) {
            $path = rtrim($moodledata, '/') . '/' . ltrim($dir, '/');
            if (is_dir($path)) {
                $this->feedback['needed']['filesystem']['success'][] =
                    get_string('filesystemdirexists', 'tool_wbinstaller', $path);
                continue;
            }
            if (mkdir($path, 0770, true) || is_dir($path)) {
                $this->feedback['needed']['filesystem']['success'][] =
                    get_string('filesystemdircreated', 'tool_wbinstaller', $path);
            } else {
                $this->feedback['needed']['filesystem']['error'][] =
                    get_string('filesystemdirfailed', 'tool_wbinstaller', $path);
            }
        }

        foreach (($this->recipe['copy'] ?? []) as $copy) {
            $from = $copy['from'] ?? '';
            $to = $copy['to'] ?? '';
            if (!$from || !$to) {
                continue;
            }
            if (!$this->path_exists($from)) {
                $this->feedback['needed']['filesystem']['warning'][] =
                    get_string('filesystemcopyskip', 'tool_wbinstaller', $from);
                continue;
            }
            if ($this->copy_path($from, $to)) {
                $this->feedback['needed']['filesystem']['success'][] =
                    get_string('filesystemcopyok', 'tool_wbinstaller', (object)['from' => $from, 'to' => $to]);
            } else {
                $this->feedback['needed']['filesystem']['error'][] =
                    get_string('filesystemcopyfailed', 'tool_wbinstaller', (object)['from' => $from, 'to' => $to]);
            }
        }

        foreach (($this->recipe['move'] ?? []) as $move) {
            $from = $move['from'] ?? '';
            $to = $move['to'] ?? '';
            if (!$from || !$to) {
                continue;
            }
            if (!$this->path_exists($from)) {
                $this->feedback['needed']['filesystem']['warning'][] =
                    get_string('filesystemmoveskip', 'tool_wbinstaller', $from);
                continue;
            }
            if ($this->move_path($from, $to)) {
                $this->feedback['needed']['filesystem']['success'][] =
                    get_string('filesystemmoveok', 'tool_wbinstaller', (object)['from' => $from, 'to' => $to]);
            } else {
                $this->feedback['needed']['filesystem']['error'][] =
                    get_string('filesystemmovefailed', 'tool_wbinstaller', (object)['from' => $from, 'to' => $to]);
            }
        }

        foreach (($this->recipe['delete'] ?? []) as $path) {
            if (empty($path)) {
                continue;
            }
            if (!$this->path_exists($path)) {
                $this->feedback['needed']['filesystem']['warning'][] =
                    get_string('filesystemdeleteskip', 'tool_wbinstaller', $path);
                continue;
            }
            if ($this->delete_path($path)) {
                $this->feedback['needed']['filesystem']['success'][] =
                    get_string('filesystemdeleteok', 'tool_wbinstaller', $path);
            } else {
                $this->feedback['needed']['filesystem']['error'][] =
                    get_string('filesystemdeletefailed', 'tool_wbinstaller', $path);
            }
        }

        return 1;
    }

    /**
     * Check installer.
     * @param string $extractpath
     * @param \tool_wbinstaller\wbCheck $parent
     */
    public function check($extractpath, $parent) {
        global $CFG;
        $moodledata = $this->recipe['moodledata'] ?? $CFG->dataroot;
        $dirs = $this->recipe['dirs'] ?? [];
        foreach ($dirs as $dir) {
            $path = rtrim($moodledata, '/') . '/' . ltrim($dir, '/');
            if (is_dir($path)) {
                $this->feedback['needed']['filesystem']['success'][] =
                    get_string('filesystemdirexists', 'tool_wbinstaller', $path);
            } else {
                $this->feedback['needed']['filesystem']['warning'][] =
                    get_string('filesystemdirfailed', 'tool_wbinstaller', $path);
            }
        }
    }

    /**
     * Enable filesystem repository plugin.
     */
    protected function enable_filesystem_repository(): void {
        \core\plugininfo\repository::enable_plugin('filesystem', \core\plugininfo\repository::REPOSITORY_ON);
        set_config('enabled', 1, 'repository_filesystem');
        set_config('enableuserinstances', 1, 'repository_filesystem');
        $this->feedback['needed']['filesystem']['success'][] =
            get_string('filesystemrepoenabled', 'tool_wbinstaller');
    }

    /**
     * Recursively copy a directory.
     * @param string $src
     * @param string $dst
     */
    protected function copy_recursive(string $src, string $dst): void {
        if (!is_dir($dst)) {
            mkdir($dst, 0770, true);
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($src, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $item) {
            $target = $dst . DIRECTORY_SEPARATOR . $iterator->getSubPathName();
            if ($item->isDir()) {
                if (!is_dir($target)) {
                    mkdir($target, 0770, true);
                }
            } else {
                copy($item->getPathname(), $target);
            }
        }
    }

    /**
     * Check if a filesystem path exists, including broken links.
     * @param string $path
     * @return bool
     */
    protected function path_exists(string $path): bool {
        return file_exists($path) || is_link($path);
    }

    /**
     * Copy a file or directory.
     * @param string $from
     * @param string $to
     * @return bool
     */
    protected function copy_path(string $from, string $to): bool {
        if (is_dir($from)) {
            $this->copy_recursive($from, $to);
            return is_dir($to);
        }
        $parent = dirname($to);
        if (!is_dir($parent) && !mkdir($parent, 0770, true) && !is_dir($parent)) {
            return false;
        }
        return copy($from, $to);
    }

    /**
     * Move a file or directory.
     * @param string $from
     * @param string $to
     * @return bool
     */
    protected function move_path(string $from, string $to): bool {
        $parent = dirname($to);
        if (!is_dir($parent) && !mkdir($parent, 0770, true) && !is_dir($parent)) {
            return false;
        }
        if (@rename($from, $to)) {
            return true;
        }
        if (!$this->copy_path($from, $to)) {
            return false;
        }
        return $this->delete_path($from);
    }

    /**
     * Delete file or directory recursively.
     * @param string $path
     * @return bool
     */
    protected function delete_path(string $path): bool {
        if (is_link($path) || is_file($path)) {
            return @unlink($path);
        }
        if (is_dir($path)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($iterator as $item) {
                $itempath = $item->getPathname();
                if ($item->isDir()) {
                    if (!@rmdir($itempath)) {
                        return false;
                    }
                } else {
                    if (!@unlink($itempath)) {
                        return false;
                    }
                }
            }
            return @rmdir($path);
        }
        return false;
    }
}
