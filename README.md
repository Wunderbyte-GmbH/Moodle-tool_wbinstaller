# Wunderbyte Installer #

A Moodle admin tool that allows you to install and configure complex Moodle setups from a single recipe file. The Wunderbyte Installer automates the deployment of courses, settings, plugins, and other Moodle components, making it easy to replicate entire learning environments with just a few clicks.

The **Wunderbyte Installer** (`tool_wbinstaller`) is a powerful Moodle admin tool developed to streamline and automate the setup of complex Moodle environments. Instead of manually configuring courses, activities, user roles, settings, and other components one by one, administrators can use a structured recipe (e.g., a JSON or XML configuration file) to deploy an entire Moodle setup in a single, reproducible process.

This plugin is especially useful for:

- **Institutions and organizations** that need to replicate standardized or complex Moodle environments across multiple instances.
- **Developers and integrators** who want to automate test or demo environment provisioning.
- **Training providers** who need to quickly spin up pre-configured course structures for workshops or onboarding.

## Features ##

- **Automated Plugin download and setup** – Install and configure courses, activities, enrolments, and settings from a single recipe file.
- **Extensible architecture** – The installer supports various Moodle components and can be extended to cover additional configuration areas.
- **Admin-friendly interface** – Accessible through Moodle's site administration area with a clear, guided workflow.
- **Reproducible deployments** – Links courses, urls and ensures consistent environments ontarget Moodle instances.
- **Support of complex plugin achitectures** like local_catquiz (including questions, itemparametes and test settings) or adele learningpaths (including corresponding courses).

## Installing via uploaded ZIP file ##

1. Log in to your Moodle site as an admin and go to _Site administration >
   Plugins > Install plugins_.
2. Upload the ZIP file with the plugin code. You should only be prompted to add
   extra details if your plugin type is not automatically detected.
3. Check the plugin validation report and finish the installation.

## Installing manually ##

The plugin can be also installed by putting the contents of this directory to

    {your/moodle/dirroot}/admin/tool/wbinstaller

Afterwards, log in to your Moodle site as an admin and go to _Site administration >
Notifications_ to complete the installation.

Alternatively, you can run

    $ php admin/cli/upgrade.php

to complete the installation from the command line.

## Known Issues ##

* using under nginx, there might be problems (presumably due to not supporting slashed urls)
* make sure, your php.ini-settings for upload_max_filesize, post_max_size and max_inputvars are sufficiently set for BOTH, apache2 (or nginx) AND the CLI php interpreter
* if being prompted to trigger plugin installation manuelly, please complete the procedure before coninueing installing your installation recipe
* for the local_catquiz plugin, embedded pictures will not be ex- and imported successfully at the moment
  
In case you encounter any further issue, please let us know in the issue section of the GitHub repository:
https://github.com/Wunderbyte-GmbH/Moodle-tool_wbinstaller/issues

## License ##

2026 Wunderbyte GmbH <info@wunderbyte.at>

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <https://www.gnu.org/licenses/>.
