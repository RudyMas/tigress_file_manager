<?php

namespace Tigress;

/**
 * Class Data Converter (PHP version 8.3)
 *
 * @author Rudy Mas <rudy.mas@rudymas.be>
 * @copyright 2024, rudymas.be. (http://www.rudymas.be/)
 * @license https://opensource.org/licenses/GPL-3.0 GNU General Public License, version 3 (GPL-3.0)
 * @version 1.0.0
 * @lastmodified 2024-07-04
 * @package Tigress\FileManager
 */
class FileManager
{
    public array $data = [];
    public int $numberOfFiles = 0;

    /**
     * Get the version of the FileManager
     *
     * @return string
     */
    public static function version(): string
    {
        return '1.0.0';
    }

    /**
     * Create a folder
     *
     * @param string $folder
     * @param int $mode
     * @param bool $allFolders
     * @return void
     */
    public function createFolder(string $folder, int $mode = 0777, bool $allFolders = false): void
    {
        if (substr($folder, -1) !== DIRECTORY_SEPARATOR) {
            $folder .= DIRECTORY_SEPARATOR;
        }
        if (!is_dir($folder)) {
            @mkdir($folder, $mode, $allFolders) or die('Unable to create folder: ' . $folder);
        }
    }

    /**
     * Delete a folder
     *
     * @param string $folder
     * @return void
     */
    public function deleteFolder(string $folder): void
    {
        if (substr($folder, -1) !== DIRECTORY_SEPARATOR) {
            $folder .= DIRECTORY_SEPARATOR;
        }
        if (is_dir($folder)) {
            @rmdir($folder) or die('Unable to delete folder: ' . $folder);
        }
    }

    /**
     * Delete a folder and all its content
     *
     * @param string $folder
     * @return void
     */
    public function deleteTree(string $folder): void
    {
        if (substr($folder, -1) !== DIRECTORY_SEPARATOR) {
            $folder .= DIRECTORY_SEPARATOR;
        }
        if (is_dir($folder)) {
            $files = array_diff(scandir($folder), ['.', '..']);
            foreach ($files as $file) {
                (is_dir($folder . $file)) ? $this->deleteTree($folder . $file) : @unlink($folder . $file);
            }
            @rmdir($folder) or die('Unable to delete folder: ' . $folder);
        }
    }

    /**
     * Rename a file
     *
     * @param string $oldName
     * @param string $newName
     * @return void
     */
    public function renameFile(string $oldName, string $newName): void
    {
        @rename($oldName, $newName) or die('Unable to rename file: ' . $oldName . ' to ' . $newName);
    }

    /**
     * Copy a file
     *
     * @param string $source
     * @param string $destination
     * @return void
     */
    public function copyFile(string $source, string $destination): void
    {
        if (!file_exists($source)) {
            die('File not found: ' . $source);
        }
        @copy($source, $destination) or die('Unable to copy file: ' . $source . ' to ' . $destination);
    }

    /**
     * Move a file
     *
     * @param string $file
     * @param string $originalFolder
     * @param string $newFolder
     * @return void
     */
    public function moveFile(string $file, string $originalFolder, string $newFolder): void
    {
        if (substr($originalFolder, -1) !== DIRECTORY_SEPARATOR) {
            $originalFolder .= DIRECTORY_SEPARATOR;
        }
        if (substr($newFolder, -1) !== DIRECTORY_SEPARATOR) {
            $newFolder .= DIRECTORY_SEPARATOR;
        }
        if (!file_exists($originalFolder . $file)) {
            die('File not found: ' . $originalFolder . $file);
        }
        @rename($originalFolder . $file, $newFolder . $file) or die('Unable to move file: ' . $originalFolder . $file . ' to ' . $newFolder . $file);
    }

    /**
     * Read a folder
     *
     * The $sort parameter can be set to SCANDIR_SORT_ASCENDING, SCANDIR_SORT_DESCENDING or SCANDIR_SORT_NONE
     *
     * @param string $folder
     * @param int $sort
     * @return void
     */
    public function readFolder(string $folder, int $sort = SCANDIR_SORT_ASCENDING): void
    {
        if (substr($folder, -1) !== DIRECTORY_SEPARATOR) {
            $folder .= DIRECTORY_SEPARATOR;
        }
        if (is_dir($folder)) {
            $this->data = @scandir($folder, $sort) or die('Unable to read folder: ' . $folder);
            $this->numberOfFiles = count($this->data);
        }
    }

    /**
     * Get the file extension
     *
     * @param string $file
     * @return string
     */
    public function fileExtension(string $file): string
    {
        return pathinfo($file, PATHINFO_EXTENSION);
    }

    /**
     * Get the file name
     *
     * @param string $file
     * @return string
     */
    public function filename(string $file): string
    {
        return pathinfo($file, PATHINFO_FILENAME);
    }

    /**
     * Move an uploaded file to a folder with a new name and set the file permissions
     *
     * @param string $originalFile
     * @param string $newFile
     * @param string $folder
     * @param int $chmod
     * @return void
     */
    public function moveUploadedFile(string $originalFile, string $newFile, string $folder, int $chmod = 0777): void
    {
        if (substr($folder, -1) !== DIRECTORY_SEPARATOR) {
            $folder .= DIRECTORY_SEPARATOR;
        }
        if (!file_exists($folder)) {
            $this->createFolder($folder, $chmod);
        }
        @move_uploaded_file($originalFile, $folder . $newFile) or die('Unable to move uploaded file: ' . $originalFile . ' to ' . $folder . $newFile);
        @chmod($folder . $newFile, $chmod) or die('Unable to change file permissions: ' . $folder . $newFile);
    }

    /**
     * Write content to a file (overwrite existing file)
     *
     * @param string $file
     * @param string $content
     * @return void
     */
    public function writeFile(string $file, string $content): void
    {
        $openFile = @fopen($file, 'w') or die('Unable to open file: ' . $file);
        @fwrite($openFile, $content) or die('Unable to write to file: ' . $file);
        @fclose($openFile) or die('Unable to close file: ' . $file);
    }

    /**
     * Append content to a file (add content to existing file)
     *
     * @param string $file
     * @param string $content
     * @return void
     */
    public function appendFile(string $file, string $content): void
    {
        $openFile = @fopen($file, 'a') or die('Unable to open file: ' . $file);
        @fwrite($openFile, $content) or die('Unable to write to file: ' . $file);
        @fclose($openFile) or die('Unable to close file: ' . $file);
    }

    /**
     * Read content from a file
     *
     * @param string $file
     * @return string
     */
    public function readFile(string $file): string
    {
        $openFile = @fopen($file, 'r') or die('Unable to open file: ' . $file);
        $content = @fread($openFile, filesize($file)) or die('Unable to read file: ' . $file);
        @fclose($openFile) or die('Unable to close file: ' . $file);
        return $content;
    }

    /**
     * Save a file with a small amount of content
     *
     * @param string $file
     * @param string $content
     * @return void
     */
    public function writeLittleFile(string $file, string $content): void
    {
        @file_put_contents($file, $content) or die('Unable to save file: ' . $file);
    }

    /**
     * Add content to a file with a small amount of content
     *
     * @param string $file
     * @param string $content
     * @return void
     */
    public function appendLittleFile(string $file, string $content): void
    {
        @file_put_contents($file, $content, FILE_APPEND) or die('Unable to add content to file: ' . $file);
    }

    /**
     * Read a file with a small amount of content
     *
     * @param string $file
     * @return string
     */
    public function readLittleFile(string $file): string
    {
        return @file_get_contents($file) or die('Unable to read file: ' . $file);
    }
}