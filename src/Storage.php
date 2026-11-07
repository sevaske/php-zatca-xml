<?php

namespace Saleh7\Zatca;

use Saleh7\Zatca\Exceptions\ZatcaStorageException;

class Storage
{
    /**
     * Global default base path, set only via setBasePath().
     */
    private static ?string $defaultBasePath = null;

    /**
     * Base path of this instance.
     */
    private ?string $basePath;

    /**
     * Constructor to set the base storage path of this instance.
     *
     * @param  string|null  $basePath  Root directory for storage. If null, the global path from setBasePath()
     *                                 is used; if that is not set either, paths are used as given (full paths).
     */
    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath
            ? rtrim($basePath, DIRECTORY_SEPARATOR)
            : self::$defaultBasePath;
    }

    /**
     * Sets a global default base storage path for instances created without an explicit path.
     *
     * @param  string|null  $path  The base directory, or null to reset.
     */
    public static function setBasePath(?string $path): void
    {
        self::$defaultBasePath = $path ? rtrim($path, DIRECTORY_SEPARATOR) : null;
    }

    /**
     * Writes data to a file, creating directories if necessary.
     *
     * @param  string  $path  Relative or full path of the file.
     * @param  string  $content  Content to write.
     * @return bool True if writing was successful, false otherwise.
     *
     * @throws ZatcaStorageException If the file cannot be written.
     */
    public function put(string $path, string $content): bool
    {
        $fullPath = $this->path($path);
        $directory = dirname($fullPath);

        $this->ensureDirectoryExists($directory);

        if (file_put_contents($fullPath, $content) === false) {
            throw new ZatcaStorageException('Failed to write to file.', [
                'path' => $fullPath,
            ]);
        }

        return true;
    }

    /**
     * Appends data to a file, creating directories if necessary.
     *
     * @param  string  $path  Relative or full path of the file.
     * @param  string  $content  Content to append.
     * @return bool True if writing was successful, false otherwise.
     *
     * @throws ZatcaStorageException If the file cannot be written.
     */
    public function append(string $path, string $content): bool
    {
        $fullPath = $this->path($path);
        $directory = dirname($fullPath);

        $this->ensureDirectoryExists($directory);

        if (file_put_contents($fullPath, $content, FILE_APPEND) === false) {
            throw new ZatcaStorageException('Failed to append to file.', [
                'path' => $fullPath,
            ]);
        }

        return true;
    }

    /**
     * Reads content from a file.
     *
     * @param  string  $path  Relative or full path of the file.
     * @return string The file contents.
     *
     * @throws ZatcaStorageException If the file does not exist or cannot be read.
     */
    public function get(string $path): string
    {
        $fullPath = $this->path($path);

        if (! file_exists($fullPath)) {
            throw new ZatcaStorageException('File not found.', [
                'path' => $fullPath,
            ]);
        }

        $content = file_get_contents($fullPath);

        if ($content === false) {
            throw new ZatcaStorageException('Failed to read file.', [
                'path' => $fullPath,
            ]);
        }

        return $content;
    }

    /**
     * Checks if a file exists.
     *
     * @param  string  $path  Relative or full path of the file.
     * @return bool True if the file exists, false otherwise.
     */
    public function exists(string $path): bool
    {
        return file_exists($this->path($path));
    }

    /**
     * Returns the full path of a file.
     *
     * @param  string  $file  Relative or full path of the file.
     * @return string Absolute path to the file.
     */
    public function path(string $file): string
    {
        if ($this->basePath) {
            return $this->basePath.DIRECTORY_SEPARATOR.$file;
        }

        return $file;
    }

    /**
     * Ensures the directory exists, creates it if needed.
     *
     * @param  string  $path  Directory path.
     *
     * @throws ZatcaStorageException If the directory cannot be created.
     */
    protected function ensureDirectoryExists(string $path): void
    {
        if (! $path) {
            return;
        }

        // If a directory exists but is not writable, throw exception
        if (is_dir($path)) {
            if (! is_writable($path)) {
                throw new ZatcaStorageException('Directory exists but is not writable.', ['path' => $path]);
            }

            return;
        }

        // Ensure the parent directory exists before creating the target directory
        $parentDir = dirname($path);
        if (! is_dir($parentDir)) {
            $this->ensureDirectoryExists($parentDir); // Recursively create parent directories
        }

        // If a directory does not exist, attempt to create it
        if (! is_dir($path) && ! mkdir($path, 0755, true) && ! is_dir($path)) {
            throw new ZatcaStorageException('Failed to create directory.', ['path' => $path]);
        }
    }
}
