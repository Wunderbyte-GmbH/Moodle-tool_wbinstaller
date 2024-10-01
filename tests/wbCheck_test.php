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

use advanced_testcase;
use core_customfield\handler;
use core_customfield\field_controller;
use restore_controller;
use stdClass;
use ZipArchive;

/**
 * PHPUnit test case for the 'tool_wbinstaller' class in local_adele.
 *
 * @package     tool_wbinstaller
 * @author       tool_wbinstaller
 * @copyright  2023 Georg Maißer <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_wbinstaller
 */
class wbCheck_test extends advanced_testcase {

    protected function setUp(): void {
        $this->resetAfterTest(true);
    }

    /**
     * Test the execute method, which combines several other functionalities.
     */
    public function test_execute() {
        // Mock recipe data and filename.
        $recipe = 'data:application/zip;base64,dGVzdGZpbGU=';
        $filename = 'testrecipe.zip';

        // Create an instance of wbCheck with mocked data.
        $check = $this->getMockBuilder(wbCheck::class)
            ->setConstructorArgs([$recipe, $filename])
            ->onlyMethods(['extract_save_zip_file', 'check_recipe', 'clean_after_installment'])
            ->getMock();

        // Mock extract_save_zip_file to simulate successful extraction.
        $check->expects($this->once())
            ->method('extract_save_zip_file')
            ->willReturn('/extracted/path');

        // Mock check_recipe to simulate checking the extracted content.
        $check->expects($this->once())
            ->method('check_recipe')
            ->with('/extracted/path')
            ->willReturn(true);

        // Mock clean_after_installment to simulate the cleanup after installation.
        $check->expects($this->once())
            ->method('clean_after_installment');

        // Call the execute method and check the result.
        $feedback = $check->execute();
        $this->assertIsArray($feedback);
    }

    /**
     * Test extract_save_zip_file method to ensure it handles valid base64 strings and extraction.
     */
    public function test_extract_save_zip_file() {
        global $CFG;

        // Mock recipe data.
        $recipe = 'data:application/zip;base64,UEsFBgAAAAAAAAAAAAAAAAAAAAAAAA==';  // Simulated minimal zip base64
        $filename = 'testrecipe.zip';

        // Mock the global $CFG.
        $CFG = new \stdClass();
        $CFG->tempdir = '/tempdir';
        $CFG->ostype = 'UNIX';
        $CFG->debugdeveloper = true;
        $CFG->umaskpermissions = 02777;

         // Create a real zip file in the temporary directory.
        $zipfilepath = $CFG->tempdir . '/zip/precheck_zip';
        if (!is_dir(dirname($zipfilepath))) {
            mkdir(dirname($zipfilepath), 0777, true);  // Ensure the directory exists.
        }

        // Create a real zip file to simulate the scenario.
        $zip = new ZipArchive();
        $zip->open($zipfilepath, ZipArchive::CREATE);
        $zip->addFromString('testfile.txt', 'This is a test file.');
        $zip->close();

        // Create an instance of wbCheck.
        $check = new \tool_wbinstaller\wbCheck($recipe, $filename);

        // Test if the extraction works as expected.
        $extractpath = $check->extract_save_zip_file();

        // Check if the extraction was successful and the file exists.
        $this->assertEquals($CFG->tempdir . '/zip/precheck/', $extractpath);

    }
}
