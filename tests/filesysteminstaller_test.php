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
 * Tests for filesystemInstaller.
 *
 * @package     tool_wbinstaller
 * @copyright   2026 Wunderbyte GmbH <info@wunderbyte.at>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class filesysteminstaller_test extends advanced_testcase {
    /**
     * Setup.
     * @coversNothing
     */
    protected function setUp(): void {
        $this->resetAfterTest(true);
        parent::setUp();
    }

    /**
     * Test execute handles copy, move and delete instructions.
     * @covers \tool_wbinstaller\filesystemInstaller::execute
     * @covers \tool_wbinstaller\filesystemInstaller::copy_recursive
     * @covers \tool_wbinstaller\filesystemInstaller::path_exists
     * @covers \tool_wbinstaller\filesystemInstaller::copy_path
     * @covers \tool_wbinstaller\filesystemInstaller::move_path
     * @covers \tool_wbinstaller\filesystemInstaller::delete_path
     */
    public function test_execute_handles_copy_move_delete(): void {
        global $CFG;

        $base = $CFG->tempdir . '/wbinstaller_filesystem_test_' . uniqid('', true);
        mkdir($base, 0777, true);

        $copyfromdir = $base . '/copyfromdir';
        $copytodir = $base . '/copytodir';
        mkdir($copyfromdir . '/nested', 0777, true);
        file_put_contents($copyfromdir . '/nested/a.txt', 'copy-dir-content');

        $copyfromfile = $base . '/copyfrom.txt';
        $copytofile = $base . '/copyto/copied.txt';
        file_put_contents($copyfromfile, 'copy-file-content');

        $movefromdir = $base . '/movefromdir';
        $movetodir = $base . '/moveto/moveddir';
        mkdir($movefromdir . '/nested', 0777, true);
        file_put_contents($movefromdir . '/nested/b.txt', 'move-dir-content');

        $movefromfile = $base . '/movefrom.txt';
        $movetofile = $base . '/moveto/moved.txt';
        file_put_contents($movefromfile, 'move-file-content');

        $deleteddir = $base . '/deletedir';
        mkdir($deleteddir . '/nested', 0777, true);
        file_put_contents($deleteddir . '/nested/c.txt', 'delete-dir-content');

        $deletedfile = $base . '/delete.txt';
        file_put_contents($deletedfile, 'delete-file-content');

        $recipe = [
            'copy' => [
                ['from' => $copyfromdir, 'to' => $copytodir],
                ['from' => $copyfromfile, 'to' => $copytofile],
            ],
            'move' => [
                ['from' => $movefromdir, 'to' => $movetodir],
                ['from' => $movefromfile, 'to' => $movetofile],
            ],
            'delete' => [$deleteddir, $deletedfile],
        ];

        $installer = new filesystemInstaller($recipe);
        $result = $installer->execute($base, null);
        $this->assertSame(1, $result);

        $this->assertFileExists($copytodir . '/nested/a.txt');
        $this->assertSame('copy-dir-content', file_get_contents($copytodir . '/nested/a.txt'));
        $this->assertFileExists($copytofile);
        $this->assertSame('copy-file-content', file_get_contents($copytofile));

        $this->assertDirectoryDoesNotExist($movefromdir);
        $this->assertFileExists($movetodir . '/nested/b.txt');
        $this->assertFileDoesNotExist($movefromfile);
        $this->assertFileExists($movetofile);
        $this->assertSame('move-file-content', file_get_contents($movetofile));

        $this->assertDirectoryDoesNotExist($deleteddir);
        $this->assertFileDoesNotExist($deletedfile);

        $this->remove_test_directory($base);
    }

    /**
     * Test execute warns on missing copy/move/delete paths.
     * @covers \tool_wbinstaller\filesystemInstaller::execute
     * @covers \tool_wbinstaller\filesystemInstaller::path_exists
     */
    public function test_execute_warns_for_missing_paths(): void {
        global $CFG;

        $base = $CFG->tempdir . '/wbinstaller_filesystem_test_missing_' . uniqid('', true);
        mkdir($base, 0777, true);

        $recipe = [
            'copy' => [
                ['from' => $base . '/missingcopy', 'to' => $base . '/copytarget'],
            ],
            'move' => [
                ['from' => $base . '/missingmove', 'to' => $base . '/movetarget'],
            ],
            'delete' => [$base . '/missingdelete'],
        ];

        $installer = new filesystemInstaller($recipe);
        $installer->execute($base, null);

        $warnings = $installer->feedback['needed']['filesystem']['warning'] ?? [];
        $this->assertCount(3, $warnings);

        $this->remove_test_directory($base);
    }

    /**
     * Recursively remove test directories.
     * @param string $dir
     */
    private function remove_test_directory(string $dir): void {
        if (!is_dir($dir)) {
            return;
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                @rmdir($item->getPathname());
            } else {
                @unlink($item->getPathname());
            }
        }
        @rmdir($dir);
    }
}
