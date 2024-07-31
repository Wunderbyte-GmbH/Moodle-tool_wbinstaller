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
 *
 * @package     tool_installer
 * @author      Jacob Viertel
 * @copyright  2023 Wunderbyte GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Import needed libraries
import { createApp } from 'vue';
import { createAppStore } from './store';
import router from './router/router'
import Notifications from '@kyvg/vue3-notification'

// Enables the Composition API
window.__VUE_OPTIONS_API__ = true;
// Disable devtools in production
window.__VUE_PROD_DEVTOOLS__ = false;

function init() {
    // We need to overwrite the variable for lazy loading.
    /* eslint-disable no-undef */
    __webpack_public_path__ = M.cfg.wwwroot + '/tool/wbinstaller/amd/build/';
    /* eslint-enable no-undef */

    const toolWbinstallerAppElements = document.getElementsByName('tool-wbinstaller-app');
    toolWbinstallerAppElements.forEach((toolWbinstallerAppElement) => {
        if (!toolWbinstallerAppElement.__vue_app__) {
            const app = createApp({});
            app.use(Notifications);
            const store = createAppStore();
            store.dispatch('loadComponentStrings');
            app.use(store);
            app.use(router);
            const userAttributeValue = toolWbinstallerAppElement.getAttribute('user');
            store.state.user = userAttributeValue;
            const contextIdValue = toolWbinstallerAppElement.getAttribute('contextid');
            store.state.contextid = contextIdValue;
            app.mount(toolWbinstallerAppElement);
        }
    });
}

export { init };