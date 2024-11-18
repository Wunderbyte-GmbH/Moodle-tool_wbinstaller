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
 * PHPUnit test case for the 'tool_wbinstaller' class in local_adele.
 *
 * @package     tool_wbinstaller
 * @author       tool_wbinstaller
 * @copyright  2023 Georg Maißer <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \tool_wbinstaller
 */
class localdatainstaller_test extends advanced_testcase {

    /** @var localdataInstaller An instance of the localdataInstaller class being tested. */
    protected $installer;
    protected function setUp(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();
        $recipe = [
            'translator' => [
                'sql' => 'SELECT * FROM {course} WHERE id = :id',
                'duplicatecheck' => ['shortname', 'fullname'],
                'changingcolumn' => [],
            ],
            'path' => '/testdata/',
        ];
        $this->installer = new localdataInstaller($recipe);
    }

    /**
     * Test duplicatecheck function.
     * @covers ::duplicatecheck
     */
    public function test_duplicatecheck() {
        global $DB;

        // Create a mock course record.
        $record = (object) [
            'shortname' => 'testcourse',
            'fullname' => 'Test Course',
        ];
        $DB->insert_record('course', $record);

        // Call duplicatecheck with valid data.
        $isduplicate = $this->installer->duplicatecheck('course', $record);

        // Assert that the duplicate was detected.
        $this->assertTrue($isduplicate);

        // Call duplicatecheck with non-duplicate data.
        $record->shortname = 'uniquecourse';
        $isduplicate = $this->installer->duplicatecheck('course', $record);

        // Assert that no duplicate was found.
        $this->assertFalse($isduplicate);
    }

    /**
     * Test update_nested_json function.
     * @covers ::translate_string_links
     */
    public function test_translate_string_links() {
        // Mock matching IDs.
        $this->installer->parent = (object) [
            'matchingids' => [
                'courses' => [
                    'courses' => [
                        123 => 456,
                    ],
                ],
            ],
        ];

        // Test input string with IDs.
        $value = 'example.com?id=123&anotherparam=456';

        // Call the translate_string_links function.
        $translatedvalue = $this->installer->translate_string_links($value);

        // Assert the ID was replaced.
        $this->assertEquals('example.com?id=456&anotherparam=456', $translatedvalue);
    }

    /**
     * Test course_matching function.
     * @covers ::course_matching
     */
    public function test_course_matching() {
        // Mock matching IDs.
        $this->installer->parent = (object) [
            'matchingids' => [
                'courses' => [
                    'courses' => [
                        1 => 101,
                        2 => 102,
                    ],
                ],
            ],
        ];

        // Call course_matching with a valid course list.
        $courses = [1, 2];
        $matchedcourses = $this->installer->course_matching($courses);

        // Assert the courses were matched correctly.
        $this->assertEquals([101, 102], $matchedcourses);
    }

    /**
     * Test get_scale_matcher function.
     */
    public function test_get_scale_matcher() {
        // Mock data API return.
        $json = [
            'catquiz_courses_1_0' => 'testvalue1',
            'catquiz_courses_2_0' => 'testvalue2',
        ];
        $sacleid = 1;

        // Mock dataapi function.
        $this->installer = $this->getMockBuilder(localdataInstaller::class)
            ->onlyMethods(['get_scale_matcher'])
            ->setConstructorArgs([$this->installer->recipe])
            ->getMock();

        // Define expected return for the scale matcher.
        $this->installer->expects($this->any())
            ->method('get_scale_matcher')
            ->willReturn([
                1 => 10,
                2 => 20,
            ]);

        $matcher = $this->installer->get_scale_matcher($json, $sacleid);

        // Assert the matcher was created correctly.
        $this->assertEquals([
            1 => 10,
            2 => 20,
        ], $matcher);
    }
}
