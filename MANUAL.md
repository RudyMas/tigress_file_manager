# Tigress File Manager — Programmer's Manual

**Version:** 2025.12.09  
**Namespace:** `Tigress`  
**PHP:** >= 8.5  
**License:** GPL-3.0-or-later

---

## Table of Contents

1. [Overview](#overview)
2. [Installation](#installation)
3. [Getting Started](#getting-started)
4. [API Reference](#api-reference)
   - [File Read/Write](#file-readwrite)
   - [File Operations](#file-operations)
   - [Folder Operations](#folder-operations)
   - [Upload Handling](#upload-handling)
   - [ZIP Archives](#zip-archives)
   - [HTTP Download](#http-download)
   - [Utility](#utility)
5. [Error Handling](#error-handling)
6. [Important Notes & Caveats](#important-notes--caveats)

---

## Overview

`FileManager` is a single-class PHP library that wraps common filesystem operations
into a simple object-oriented API. It handles reading, writing, copying, moving,
renaming, and deleting files; creating and deleting directories; processing file
uploads; creating ZIP archives; and streaming files for HTTP download with
correct MIME types.

---

## Installation

```bash
composer require tigress/file-manager
```

Or as part of the full Tigress Framework:

```bash
composer create-project tigress/tigress <project_name>
```

**Required PHP extensions:** `ext-fileinfo`, `ext-zip`

---

## Getting Started

```php
<?php
require_once 'vendor/autoload.php';

use Tigress\FileManager;

$fm = new FileManager();
```

All methods are instance methods on this object (except `version()` which is
static). The class holds two public properties populated by `readFolder()`:

- `$fm->data` — array of file/folder names
- `$fm->numberOfFiles` — count of items in `$data`

---

## API Reference

### File Read/Write

#### `appendFile(string $file, string $content): void`
Appends content to a file using `fopen()` in append mode (`a`). Best for
repeated appends in a loop; uses less memory than `appendLittleFile()`
because it keeps the file pointer open only during the write.

```php
$fm->appendFile('log.txt', "New log entry\n");
```

#### `appendLittleFile(string $file, string $content): void`
Appends content using `file_put_contents()` with the `FILE_APPEND` flag.
Slightly slower on repeated calls but more concise for one-off appends.

```php
$fm->appendLittleFile('log.txt', "Quick entry\n");
```

#### `readFile(string $file): string`
Reads an entire file via `fopen()`/`fread()`.

```php
$content = $fm->readFile('document.txt');
```

#### `readLittleFile(string $file): string`
Reads an entire file via `file_get_contents()`. Prefer this for small files.

```php
$content = $fm->readLittleFile('note.txt');
```

#### `writeFile(string $file, string $content): void`
Overwrites a file using `fopen()` with write mode (`w`).

```php
$fm->writeFile('config.json', json_encode($config));
```

#### `writeLittleFile(string $file, string $content): void`
Overwrites a file via `file_put_contents()`. Prefer this for small payloads.

```php
$fm->writeLittleFile('status.txt', 'OK');
```

---

### File Operations

#### `copyFile(string $source, string $destination): void`
Copies a source file to a destination path.

```php
$fm->copyFile('backup.sql', 'exports/backup-2025.sql');
```

#### `moveFile(string $file, string $originalFolder, string $newFolder): void`
Moves a file from one folder to another. The filename stays the same.

```php
$fm->moveFile('report.pdf', 'temp/', 'final/');
```

#### `renameFile(string $oldName, string $newName): void`
Renames (or moves) a file.

```php
$fm->renameFile('draft.txt', 'final.txt');
```

#### `deleteFile(string $filename, string $filepath): void`
Deletes a file. If `$filepath` is empty, `$filename` is treated as an
absolute path. If `$filepath` is given, the path is constructed as
`$filepath . '/' . $filename`.

```php
$fm->deleteFile('temp.txt', './cache/');
$fm->deleteFile('./cache/temp.txt', ''); // equivalent
```

#### `fileExtension(string $file): string`
Returns the file extension (string after the last dot).

```php
echo $fm->fileExtension('photo.jpg'); // "jpg"
```

#### `filename(string $file): string`
Returns the filename without extension.

```php
echo $fm->filename('photo.jpg'); // "photo"
```

---

### Folder Operations

#### `createFolder(string $folder, int $mode = 0777, bool $allFolders = false): void`
Creates a single directory or a nested directory tree. The path is
automatically normalized to end with `DIRECTORY_SEPARATOR`.

```php
$fm->createFolder('uploads');                    // single folder
$fm->createFolder('data/2025/logs', 0755, true); // recursive
```

#### `deleteFolder(string $folder): void`
Deletes an **empty** directory. Has no effect if the directory does not exist.

```php
$fm->deleteFolder('temp/empty_dir');
```

#### `deleteTree(string $folder): void`
Recursively deletes a directory and all its contents (files and
subdirectories). Use with caution — this is destructive and permanent.

```php
$fm->deleteTree('cache/');
```

#### `readFolder(string $folder, int $sort = SCANDIR_SORT_ASCENDING): void`
Scans a directory and stores the results in `$this->data` (array of file names)
and `$this->numberOfFiles` (count). The `.` and `..` entries are included.

```php
$fm->readFolder('./documents');
foreach ($fm->data as $item) {
    echo $item . "\n";
}
echo "Found {$fm->numberOfFiles} items";
```

Sort options: `SCANDIR_SORT_ASCENDING` (default), `SCANDIR_SORT_DESCENDING`,
`SCANDIR_SORT_NONE`.

---

### Upload Handling

#### `moveUploadedFile(string $originalFile, string $newFile, string $folder, int $chmod = 0777): void`
Moves an uploaded file from its temporary path to a permanent location.
Creates the destination folder if it does not exist. Sets file permissions
via `chmod()`.

> **Security note:** This method expects `$originalFile` to be the string path
> from `$_FILES['key']['tmp_name']`. The underlying PHP function
> `move_uploaded_file()` verifies the file was actually uploaded via HTTP POST.

```php
$fm->moveUploadedFile(
    $_FILES['avatar']['tmp_name'],
    'avatar.jpg',
    './uploads',
    0644
);
```

---

### ZIP Archives

#### `createZip(array $files, string $filename, string $filepath = ''): void`
Creates a ZIP archive containing the given files. Files are stored with
their basename only (directory structure is flattened). Creates the output
directory if it does not exist.

```php
$fm->createZip(
    ['doc1.pdf', 'doc2.pdf', 'image.png'],
    'bundle.zip',
    './exports'
);
```

---

### HTTP Download

These methods send HTTP headers and file content directly to the client.
They must be called **before any output is sent** in a web context. They
throw `Exception` if the file does not exist.

#### `download(string $filename, string $filepath, string $type = 'detect'): void`
Generic download. When `$type` is `'detect'`, the MIME type is auto-detected
using `mime_content_type()`. Otherwise, the provided MIME type string is used.

```php
$fm->download('report.pdf', './exports');
$fm->download('custom.xyz', './data', 'application/octet-stream');
```

#### Typed download methods

Each sets a specific MIME type and optionally deletes the file after sending:

| Method | MIME Type |
|---|---|
| `downloadCsv(string $filename, string $filepath, bool $delete = false)` | `text/csv` |
| `downloadExcel(string $filename, string $filepath, bool $delete = false)` | `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet` |
| `downloadJson(string $filename, string $filepath, bool $delete = false)` | `application/json` |
| `downloadPdf(string $filename, string $filepath, bool $delete = false)` | `application/pdf` |
| `downloadXml(string $filename, string $filepath, bool $delete = false)` | `application/xml` |
| `downloadZip(string $filename, string $filepath, bool $delete = false)` | `application/zip` |

```php
$fm->downloadCsv('report.csv', './exports', delete: true);
$fm->downloadPdf('invoice.pdf', './invoices');
```

---

### Utility

#### `version(): string` (static)
Returns the library version string.

```php
echo FileManager::version(); // "2025.12.09"
```

---

## Error Handling

The library uses two error-handling approaches:

1. **`or die(...)` pattern** — Most methods suppress PHP errors with `@` and
   terminate on failure with a plain `die()` message. This is simple but
   provides no recovery path; the script exits immediately.

2. **Exceptions** — The `download()` family of methods uses proper PHP
   exceptions:

   ```php
   try {
       $fm->download('missing.pdf', './data');
   } catch (Exception $e) {
       // handle error gracefully
   }
   ```
