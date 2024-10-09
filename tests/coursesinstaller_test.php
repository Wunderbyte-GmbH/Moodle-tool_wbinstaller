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
use restore_controller;
use stdClass;

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
class coursesinstaller_test extends advanced_testcase {

    protected function setUp(): void {
        $this->resetAfterTest(true);
        $this->setAdminUser();
    }

    /**
     * Test the execute method to ensure courses are installed.
     */
    public function test_execute_installs_courses() {
        global $CFG;
        $recipe = ['path' => '/testcourses'];

        // Mock course files in the test directory.
        $extractpath = $CFG->tempdir . '/test_wbinstaller';
        @mkdir($extractpath . $recipe['path'], 0777, true);
        file_put_contents($extractpath . $recipe['path'] . '/course1', 'Test Course 1');
        file_put_contents($extractpath . $recipe['path'] . '/course2', 'Test Course 2');

        // Create instance of coursesInstaller.
        $installer = $this->getMockBuilder(coursesInstaller::class)
            ->setConstructorArgs([$recipe])
            ->onlyMethods(['install_course'])
            ->getMock();

        // Expect install_course to be called exactly 2 times.
        $installer->expects($this->exactly(2))
            ->method('install_course')
            ->withConsecutive(
                [$this->equalTo($extractpath . '/testcourses/course1')],
                [$this->equalTo($extractpath . '/testcourses/course2')]
            );

        // Run the execute method.
        $installer->execute($extractpath);

    }

    /**
     * Test precheck method to check if courses are detected.
     */
    public function test_precheck_detects_courses() {
        global $CFG;

        // Mock a recipe.
        $recipe = ['path' => '/testcourses'];
        $extractpath = $CFG->tempdir . '/test_wbinstaller';

        // Create a test directory and add a mock course file.
        @mkdir($extractpath . $recipe['path'] . '/course1', 0777, true); // Ensure the directory exists.
        file_put_contents($extractpath . $recipe['path'] . '/course1/moodle_backup.xml', '<xml>mock data</xml>');

        // Create instance of coursesInstaller.
        $installer = $this->getMockBuilder(coursesInstaller::class)
            ->setConstructorArgs([$recipe])
            ->onlyMethods(['precheck'])
            ->getMock();

        // Simulate precheck returning valid data.
        $installer->method('precheck')
            ->willReturn([
                'courseshortname' => 'test_shortname',
                'courseoriginalid' => 1234,
            ]);

        // Run check method and verify feedback.
        $feedback = [];
        $installer->feedback = &$feedback;
        $installer->check($extractpath);

        $this->assertArrayHasKey('needed', $installer->feedback);
        $this->assertArrayHasKey('test_shortname', $installer->feedback['needed']);
    }

    /**
     * Test install_course method to ensure it restores the course properly.
     */
    public function test_install_course_restores_correctly() {
        global $DB;

        $coursefile = '/somepath/testcourse/course1';
        $recipe = ['path' => '/testcourses'];
        $installer = $this->getMockBuilder(coursesInstaller::class)
            ->setConstructorArgs([$recipe])
            ->onlyMethods(['precheck', 'restore_course'])
            ->getMock();

        // Simulate valid precheck results.
        $installer->method('precheck')
            ->willReturn([
                'courseshortname' => 'test_shortname',
                'courseoriginalid' => 1234,
            ]);

        // Expect restore_course to be called once.
        $installer->expects($this->once())
            ->method('restore_course')
            ->with($this->equalTo($coursefile), $this->arrayHasKey('courseshortname'));

        // Use reflection to invoke the protected method install_course.
        $reflection = new \ReflectionClass($installer);
        $method = $reflection->getMethod('install_course');
        $method->setAccessible(true); // Bypass the protected access level.

        // Call the method using reflection.
        $method->invoke($installer, $coursefile);
    }

    /**
     * Test course_exists method to check if a course already exists.
     */
    public function test_course_exists() {
        global $DB;

        // Create a mock course in the database.
        $course = new stdClass();
        $course->shortname = 'test_shortname';
        $course->fullname = 'Test Course';
        $course->category = 1;
        $course->id = $DB->insert_record('course', $course);

        // Create instance of coursesInstaller.
        $installer = new coursesInstaller([]);

        $reflection = new \ReflectionClass($installer);
        $method = $reflection->getMethod('course_exists');
        $method->setAccessible(true);

        // Call the method using reflection and check the result.
        $result = $method->invoke($installer, 'test_shortname');
        $this->assertNotNull($result);
        $this->assertEquals($course->id, $result->id);
    }

    /**
     * Test restore_course method to ensure it restores the course from the backup.
     */
    public function test_restore_course() {
        global $CFG, $USER;

        // Mock course file and precheck result.
        $coursefile = '/testpath/coursefile';
        $precheck = [
            'courseshortname' => 'shortname',
            'courseoriginalid' => 1234,
        ];

        // Create instance of coursesInstaller.
        $recipe = ['path' => '/testcourses'];
        $installer = $this->getMockBuilder(coursesInstaller::class)
            ->setConstructorArgs([$recipe])
            ->onlyMethods(['copy_directory'])
            ->getMock();

        // Mock the restore_controller.
        $mockrestorecontroller = $this->getMockBuilder(restore_controller::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['execute_precheck', 'execute_plan', 'destroy'])
            ->getMock();

        $mockrestorecontroller->expects($this->once())
            ->method('execute_precheck')
            ->willReturn(true);

        $mockrestorecontroller->expects($this->once())
            ->method('execute_plan');

        $mockrestorecontroller->expects($this->once())
            ->method('destroy');

        // Simulate restore.
        $installer->expects($this->atLeastOnce())
            ->method('copy_directory')
            ->willReturn(true);

    }

}
