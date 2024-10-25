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
 * Validate if the string does excist.
 *
 * @package     tool_installer
 * @author      Jacob Viertel
 * @copyright  2023 Wunderbyte GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Import needed libraries
import { createStore } from 'vuex';
import moodleAjax from 'core/ajax';
import moodleStorage from 'core/localstorage';
import Notification from 'core/notification';

// Defining store for application
export function createAppStore() {
    return createStore({
        state() {
            return {
                strings: {},
                contextid: 0,
                user: null,
            };
        },
        mutations: {
            setStrings(state, strings) {
                state.strings = strings;
            },
        },
        actions: {
            // Actions are asynchronous.
            async loadLang(context) {
                const lang = document.documentElement.lang.replace(/-/g, '_');
                context.commit('setLang', lang);
            },
            async loadComponentStrings(context) {
                const lang = document.documentElement.lang.replace(/-/g, '_');
                const cacheKey = 'tool_installer/strings/' + lang;
                const cachedStrings = moodleStorage.get(cacheKey);
                if (cachedStrings) {
                    context.commit('setStrings', JSON.parse(cachedStrings));
                } else {
                    const request = {
                        methodname: 'core_get_component_strings',
                        args: {
                            'component': 'tool_wbinstaller',
                            lang,
                        },
                    };
                    const loadedStrings = await moodleAjax.call([request])[0];
                    let strings = {};
                    loadedStrings.forEach((s) => {
                        strings[s.stringid] = s.string;
                    });
                    context.commit('setStrings', strings);
                    moodleStorage.set(cacheKey, JSON.stringify(strings));
                }
            },
            async installRecipe(context, payload) {
              return await ajax('tool_wbinstaller_install_recipe',
              {
                userid: context.state.user,
                contextid: context.state.contextid,
                file: payload.uploadedFile,
                filename: payload.filename,
                optionalplugins: payload.selectedOptionalPlugins,
              });
            },
            async checkRecipe(context, payload) {
              return await ajax('tool_wbinstaller_check_recipe',
              {
                userid: context.state.user,
                contextid: context.state.contextid,
                file: payload.uploadedFile,
                filename: payload.filename,
              });
            },
            async getInstallProgress(context, payload) {
              return 1
            },
            async getExportableCourses(context) {
              return await ajax('tool_wbinstaller_get_exportable_courses',
              {
                userid: context.state.user,
                contextid: context.state.contextid,
              });
            },
            async downloadRecipe(context, payload) {
              return await ajax('tool_wbinstaller_download_recipe',
                {
                  userid: context.state.user,
                  contextid: context.state.contextid,
                  courseids: JSON.stringify(payload.courseids),
                });
            },
        }
    });
}

/**
 * Single ajax call to Moodle.
 */
export async function ajax(method, args) {
    const request = {
        methodname: method,
        args: Object.assign( args ),
    };

    try {
        const response = await moodleAjax.call([request]);
        return response[0];
    } catch (e) {
        Notification.exception(e);
        throw e;
    }
}