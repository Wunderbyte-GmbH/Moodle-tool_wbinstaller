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
class configInstaller extends wbInstaller {
    /** @var \core_customfield\handler Matching the course ids from the old => new. */
    public $handler;

    /**
     * Entities constructor.
     * @param array $recipe
     * @param int $dbid
     */
    public function __construct($recipe) {
        $this->recipe = $recipe;
        $this->progress = 0;
        $this->handler = null;
    }

    /**
     * Exceute the installer.
     * @param string $extractpath
     * @param \tool_wbinstaller\wbCheck $parent
     * @return int
     */
    public function execute($extractpath, $parent = null) {
        global $DB;
        foreach ($this->recipe as $pluginname => $configfields) {
            foreach ($configfields as $configfield => $value) {
                $currentvalue = get_config($pluginname, $configfield);
                if ($currentvalue !== false) {
                    set_config($configfield, $value, $pluginname);
                    $this->feedback['needed'][$pluginname]['success'][] =
                        get_string('configvalueset', 'tool_wbinstaller', $configfield);
                } else {
                    $this->feedback['needed'][$pluginname]['error'][] =
                      get_string('confignotfound', 'tool_wbinstaller', $configfield);
                }
            }
        }
        return 1;
    }

    /**
     * Exceute the installer check.
     * @param string $extractpath
     * @param \tool_wbinstaller\wbCheck $parent
     */
    public function check($extractpath, $parent = null) {
        global $DB;
        foreach ($this->recipe as $pluginname => $configfields) {
            foreach ($configfields as $configfield => $value) {
                $currentvalue = get_config($pluginname, $configfield);
                if ($currentvalue !== false) {
                    $this->feedback['needed'][$pluginname]['success'][] =
                        get_string('configsettingfound', 'tool_wbinstaller', $configfield);
                } else {
                    $this->feedback['needed'][$pluginname]['warning'][] =
                      get_string('confignotfound', 'tool_wbinstaller', $configfield);
                }
            }
        }
        return 1;
    }
}
