<?php

namespace Saleh7\Zatca\Tests;

use PHPUnit\Framework\TestCase;
use Saleh7\Zatca\Exceptions\ZatcaStorageException;
use Saleh7\Zatca\Storage;

class StorageTest extends TestCase
{
    private Storage $storage;

    /**
     * Set up the environment for each test.
     * This will create a temporary storage directory.
     */
    protected function setUp(): void
    {
        // Initialize storage with a temporary directory
        $this->storage = new Storage(__DIR__.'/test_storage');

        // Ensure the test directory exists (create it if it doesn't)
        if (! is_dir(__DIR__.'/test_storage')) {
            mkdir(__DIR__.'/test_storage', 0777, true);
        }
    }

    /**
     * Clean up after each test.
     * This will remove the test directory and its contents.
     */
    protected function tearDown(): void
    {
        Storage::setBasePath('');
        $this->deleteDirectory(__DIR__.'/test_storage');
    }

    /**
     * Helper function to delete a directory and its contents.
     */
    private function deleteDirectory(string $dir): void
    {
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = "$dir/$file";
            // Recursively delete files and directories
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }
        rmdir($dir); // Remove the now-empty directory
    }

    /**
     * Test that the put() method writes a file successfully.
     */
    public function test_put_writes_file_successfully(): void
    {
        $content = 'Test content';
        $path = 'test_file.txt';

        // Try to put the content into the file
        $result = $this->storage->put($path, $content);
        $this->assertTrue($result); // Assert the result is true

        // Check that the file was written correctly
        $this->assertFileExists(__DIR__.'/test_storage/'.$path);
        $this->assertStringEqualsFile(__DIR__.'/test_storage/'.$path, $content);
    }

    /**
     * Test that the append() method appends data to a file correctly.
     */
    public function test_append_appends_to_file_successfully(): void
    {
        $content = 'Initial content';
        $appendContent = 'Appended content';
        $path = 'test_append.txt';

        // Write initial content to the file
        $this->storage->put($path, $content);

        // Append content to the file
        $result = $this->storage->append($path, $appendContent);
        $this->assertTrue($result); // Assert append was successful

        // Check that the content was correctly appended
        $expectedContent = $content.$appendContent;
        $this->assertStringEqualsFile(__DIR__.'/test_storage/'.$path, $expectedContent);
    }

    /**
     * Test that the get() method reads a file's content correctly.
     */
    public function test_get_reads_file_successfully(): void
    {
        $content = 'File content';
        $path = 'test_get.txt';

        // Write content to the file
        $this->storage->put($path, $content);

        // Retrieve the file content
        $retrievedContent = $this->storage->get($path);
        $this->assertEquals($content, $retrievedContent); // Assert the content is the same
    }

    /**
     * Test that the get() method throws an exception when the file does not exist.
     */
    public function test_get_throws_exception_if_file_does_not_exist(): void
    {
        $this->expectException(ZatcaStorageException::class);
        $this->expectExceptionMessage('File not found.');

        $path = 'non_existent_file.txt';
        $this->storage->get($path); // Attempt to read a non-existent file
    }

    /**
     * Test that the exists() method returns true for an existing file.
     */
    public function test_exists_returns_true_for_existing_file(): void
    {
        $content = 'Some content';
        $path = 'existing_file.txt';

        // Write the content to the file
        $this->storage->put($path, $content);

        // Assert that the file exists
        $this->assertTrue($this->storage->exists($path));
    }

    /**
     * Test that the exists() method returns false for a non-existent file.
     */
    public function test_exists_returns_false_for_non_existing_file(): void
    {
        $path = 'non_existing_file.txt';
        $this->assertFalse($this->storage->exists($path)); // Assert the file does not exist
    }

    /**
     * Test that ensureDirectoryExists() creates the directory if it does not exist.
     */
    public function test_ensure_directory_exists_creates_directory_if_not_exists(): void
    {
        $path = 'new_directory/test_file.txt';
        $content = 'Test content in a new directory';

        // Write content to a file in a non-existent directory
        $result = $this->storage->put($path, $content);

        // Assert that the file was created and written
        $this->assertTrue($result);
        $this->assertFileExists(__DIR__.'/test_storage/'.$path);
    }

    public function test_explicit_null_uses_full_paths_even_after_another_instance_set_a_base_path(): void
    {
        new Storage(__DIR__.'/test_storage');

        $this->assertSame('/tmp/full/path.xml', (new Storage(null))->path('/tmp/full/path.xml'));
    }

    public function test_omitted_base_path_still_uses_the_global_one(): void
    {
        new Storage(__DIR__.'/test_storage');

        // Backward compatibility: a non-empty constructor path also becomes the global base path.
        $this->assertSame(__DIR__.'/test_storage'.DIRECTORY_SEPARATOR.'file.xml', (new Storage)->path('file.xml'));
    }

    public function test_instance_keeps_its_own_base_path(): void
    {
        $first = new Storage('/first');
        new Storage('/second');

        $this->assertSame('/first'.DIRECTORY_SEPARATOR.'file.xml', $first->path('file.xml'));
    }
}
