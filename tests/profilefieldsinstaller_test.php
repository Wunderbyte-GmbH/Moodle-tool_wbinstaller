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

/**
 * Tests for profilefieldsInstaller.
 *
 * @package     tool_wbinstaller
 * @copyright   2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class profilefieldsinstaller_test extends advanced_testcase {
    /**
     * Setup.
     */
    protected function setUp(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        parent::setUp();
    }

    /**
     * Test execute creates a profile field.
     *
     * @covers \tool_wbinstaller\profilefieldsInstaller::execute
     * @covers \tool_wbinstaller\profilefieldsInstaller::get_categories
     * @covers \tool_wbinstaller\profilefieldsInstaller::get_or_create_profile_category
     * @covers \tool_wbinstaller\profilefieldsInstaller::ensure_profile_field
     * @covers \tool_wbinstaller\profilefieldsInstaller::map_visible
     */
    public function test_execute_creates_profile_field(): void {
        global $DB;

        $shortname = 'kandidatenummer';
        $categoryname = 'Category1';
        $recipe = [
            'categories' => [[
                'name' => $categoryname,
                'fields' => [[
                    'shortname' => $shortname,
                    'name' => 'WB Test Field',
                    'datatype' => 'text',
                    'required' => 1,
                ]],
            ]],
        ];

        $installer = new profilefieldsInstaller($recipe);
        $result = $installer->execute('', null);

        $this->assertSame(1, $result);
        $this->assertTrue($DB->record_exists('user_info_category', ['name' => $categoryname]));
        $this->assertTrue($DB->record_exists('user_info_field', ['shortname' => $shortname]));
    }

    /**
     * Test execute does not create duplicate profile fields on repeated runs.
     *
     * @covers \tool_wbinstaller\profilefieldsInstaller::execute
     * @covers \tool_wbinstaller\profilefieldsInstaller::ensure_profile_field
     */
    public function test_execute_does_not_create_profile_field_twice(): void {
        global $DB;

        $shortname = 'kandidatenummer';
        $categoryname = 'Category1';
        $recipe = [
            'categories' => [[
                'name' => $categoryname,
                'fields' => [[
                    'shortname' => $shortname,
                    'name' => 'WB Duplicate Test Field',
                    'datatype' => 'text',
                ]],
            ]],
        ];

        $installer = new profilefieldsInstaller($recipe);
        $installer->execute('', null);
        $installer->execute('', null);

        $this->assertEquals(1, $DB->count_records('user_info_field', ['shortname' => $shortname]));
        $this->assertEquals(1, $DB->count_records('user_info_category', ['name' => $categoryname]));
    }
}
