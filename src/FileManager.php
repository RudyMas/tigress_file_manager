<?php

namespace Tigress;

use Exception;
use ZipArchive;

/**
 * Class Data Converter (PHP version 8.4)
 *
 * @author Rudy Mas <rudy.mas@rudymas.be>
 * @copyright 2024-2025, rudymas.be. (http://www.rudymas.be/)
 * @license https://opensource.org/licenses/GPL-3.0 GNU General Public License, version 3 (GPL-3.0)
 * @version 2025.01.16.0
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
        return '2025.01.16';
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
     * Read a file with a small amount of content
     *
     * @param string $file
     * @return string
     */
    public function readLittleFile(string $file): string
    {
        return @file_get_contents($file) or die('Unable to read file: ' . $file);
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
     * Create a ZIP file
     *
     * @param array $files
     * @param string $filename
     * @param string $filepath
     * @return void
     */
    public function createZip(array $files, string $filename, string $filepath = ''): void
    {
        if ($filepath != '' && !file_exists($filepath)) {
            mkdir($filepath, 0777, true);
        }

        if ($filepath) {
            $zipFile = $filepath . '/' . $filename;
        } else {
            $zipFile = $filename;
        }

        $zip = new ZipArchive();
        $zip->open($zipFile, ZipArchive::CREATE);
        foreach ($files as $file) {
            $zip->addFile($file, basename($file));
        }
        $zip->close();
    }

    /**
     * Download a file
     *
     * @param string $filename
     * @param string $filepath
     * @param string $type
     * @return void
     * @throws Exception
     */
    public function download(string $filename, string $filepath, string $type = 'detect'): void
    {
        if ($filepath) {
            $downloadFile = $filepath . '/' . $filename;
        } else {
            $downloadFile = $filename;
        }

        if (!file_exists($downloadFile)) {
            throw new Exception('File not found: ' . $downloadFile);
        }

        if ($type === 'detect') {
            $type = mime_content_type($filepath);
        }

        header('Content-Type: ' . $type);
        header('Content-Disposition: attachment; filename="' . basename($downloadFile) . '"');
        header('Content-Length: ' . filesize($downloadFile));
        readfile($downloadFile);
    }

    /**
     * Download a CSV file
     *
     * @param string $filename
     * @param string $filepath
     * @param bool $delete
     * @return void
     * @throws Exception
     */
    public function downloadCsv(string $filename, string $filepath, bool $delete = false): void
    {
        $this->download($filename, $filepath, 'text/csv');
        if ($delete) {
            unlink($filename);
        }
    }

    /**
     * Download an Excel file
     *
     * @param string $filename
     * @param string $filepath
     * @param bool $delete
     * @return void
     * @throws Exception
     */
    public function downloadExcel(string $filename, string $filepath, bool $delete = false): void
    {
        $this->download($filename, $filepath, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        if ($delete) {
            unlink($filename);
        }
    }

    /**
     * Download a JSON file
     *
     * @param string $filename
     * @param string $filepath
     * @param bool $delete
     * @return void
     * @throws Exception
     */
    public function downloadJson(string $filename, string $filepath, bool $delete = false): void
    {
        $this->download($filename, $filepath, 'application/json');
        if ($delete) {
            unlink($filename);
        }
    }

    /**
     * Download a PDF file
     *
     * @param string $filename
     * @param string $filepath
     * @param bool $delete
     * @return void
     * @throws Exception
     */
    public function downloadPdf(string $filename, string $filepath, bool $delete = false): void
    {
        $this->download($filename, $filepath, 'application/pdf');
        if ($delete) {
            unlink($filename);
        }
    }

    /**
     * Download a ZIP file
     *
     * @param string $filename
     * @param string $filepath
     * @param bool $delete
     * @return void
     * @throws Exception
     */
    public function downloadZip(string $filename, string $filepath, bool $delete = false): void
    {
        $this->download($filename, $filepath, 'application/zip');
        if ($delete) {
            unlink($filename);
        }
    }
}