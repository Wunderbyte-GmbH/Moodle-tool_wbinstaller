# Wunderbyte Installer #

TODO Describe the plugin shortly here.

TODO Provide more detailed description here.

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

2024 Wunderbyte GmbH <info@wunderbyte.at>

This program is free software: you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation, either version 3 of the License, or (at your option) any later
version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with
this program.  If not, see <https://www.gnu.org/licenses/>.
