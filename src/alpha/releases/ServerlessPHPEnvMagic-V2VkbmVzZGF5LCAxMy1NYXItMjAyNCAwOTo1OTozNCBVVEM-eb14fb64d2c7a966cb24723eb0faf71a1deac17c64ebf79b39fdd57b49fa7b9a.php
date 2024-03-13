<?php

/**
 * Class ServerlessPHPEnvMagic
 *
 * Provides a middleware layer for serverless PHP environments, facilitating easy access to
 * environment variables, session and request data, and file system exploration. This class
 * aims to standardize access to these elements across various serverless platforms such as
 * AWS Lambda, Google Cloud Functions, and others.
 * 
 * Author: Tristan McGowan (tristan@ipspy.net)
 * Inception Date: Wed, March 13, 3:35 AM CDT
 */
class ServerlessPHPEnvMagic
{
    /**
     * @var array Holds the unified environment array exposing details of the serverless environment.
     */
    private array $_SVRLS_MGK_ENV = [];

    /**
     * ServerlessPHPEnvMagic constructor.
     *
     * Initializes the class by setting up the environment, performing file system explorations,
     * and conducting quick checks for common serverless functionalities.
     */
    public function __construct()
    {
        $this->initializeEnvironment();
        $this->exploreFileSystem();
        $this->performQuickChecks();
        $this->deepFileSystemExplore(__DIR__, 7);
    }

    /**
     * Initializes the environment variables, session, and server details.
     */
    private function initializeEnvironment(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $this->_SVRLS_MGK_ENV = [
            'environmentVariables' => getenv(),
            'sessionVariables' => $_SESSION,
            'serverVariables' => $_SERVER,
        ];
    }

    /**
     * Performs a basic exploration of the file system from the current directory.
     */
    private function exploreFileSystem(): void
    {
        $rootPath = $_SERVER['DOCUMENT_ROOT'] ?? getcwd();
        $this->_SVRLS_MGK_ENV['fileSystem']['basic'] = $this->listDirectoryDetails($rootPath);
    }

    /**
     * Performs quick checks for the presence of common serverless features and configurations.
     */
    private function performQuickChecks(): void
    {
        $this->_SVRLS_MGK_ENV['quickChecks'] = [
            'composer' => file_exists('composer.json'),
            'fileUploadsEnabled' => filter_var(ini_get('file_uploads'), FILTER_VALIDATE_BOOLEAN),
            'jsonDataPresent' => $this->isJsonRequest(),
            'postDataPresent' => !empty($_POST),
            'getDataPresent' => !empty($_GET),
            'putDataPresent' => $this->isPutRequest(),
            'requestDataPresent' => !empty($_REQUEST),
        ];
    }

    /**
     * Mimics the behavior of the Unix 'ls -alh' command in PHP, exploring the file system
     * deeply, up to a specified number of levels up and then recursively down.
     *
     * @param string $directory The starting directory path.
     * @param int $levelsUp The number of levels to move up the directory tree.
     */
    private function deepFileSystemExplore(string $directory, int $levelsUp): void
    {
        // Ascend up the directory levels as specified.
        $currentPath = $directory;
        for ($i = 0; $i < $levelsUp; $i++) {
            $currentPath = dirname($currentPath);
            $this->_SVRLS_MGK_ENV['fileSystem']['up'][$i] = $this->listDirectoryDetails($currentPath);
            
            if ($currentPath === '/') break; // Stop if we reach the root of the file system.
        }

        // Now explore downwards recursively from the original directory.
        $this->_SVRLS_MGK_ENV['fileSystem']['down'] = $this->listDirectoryDetailsRecursive($directory);
    }

    /**
     * Lists detailed information about files and directories at a specific path, similar to 'ls -alh'.
     * Implements error handling to gracefully manage inaccessible directories.
     *
     * @param string $directory The directory path.
     * @return array An array of file and directory details or an error message.
     */
    private function listDirectoryDetails(string $directory): array
    {
        $details = [];
        try {
            if (is_dir($directory) && $handle = opendir($directory)) {
                $iterator = new DirectoryIterator($directory);
                foreach ($iterator as $fileinfo) {
                    if (!$fileinfo->isDot()) {
                        $details[] = [
                            'name' => $fileinfo->getFilename(),
                            'type' => $fileinfo->isDir() ? 'directory' : 'file',
                            // Ensure size and permissions are displayed in a secure and readable format
                            'size' => $this->formatSize($fileinfo->getSize()),
                            'permissions' => $this->formatPermissions($fileinfo->getPerms()),
                            'lastModified' => date("F d Y H:i:s.", $fileinfo->getMTime()),
                        ];
                    }
                }
                closedir($handle);
            } else {
                throw new Exception("Unable to access directory: $directory");
            }
        } catch (Exception $e) {
            // Handle the error gracefully, possibly logging it and returning a safe error message
            return ['error' => $e->getMessage()];
        }
        return $details;
    }

    /**
     * Formats file size for human-readable output.
     *
     * @param int $bytes File size in bytes.
     * @return string Formatted file size.
     */
    private function formatSize(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }

    /**
     * Formats file permissions into a human-readable string.
     *
     * @param int $perms Permissions bitmask.
     * @return string Formatted permissions.
     */
    private function formatPermissions(int $perms): string
    {
        // Format permissions here, for example: 'drwxr-xr-x'
        // This is a simplified approach, consider implementing a more comprehensive method.
        return substr(sprintf('%o', $perms), -4);
    }


    /**
     * Recursively lists detailed information about files and directories within a directory.
     *
     * @param string $directory The starting directory path.
     * @return array An array of directories and their contents.
     */
    private function listDirectoryDetailsRecursive(string $directory): array
    {
        $details = $this->listDirectoryDetails($directory);
        foreach ($details as $key => $item) {
            if ($item['type'] === 'directory') {
                $details[$key]['contents'] = $this->listDirectoryDetailsRecursive($directory . DIRECTORY_SEPARATOR . $item['name']);
            }
        }
        return $details;
    }

    /**
     * Checks if the current request contains JSON data.
     *
     * @return bool True if JSON data is present, false otherwise.
     */
    private function isJsonRequest(): bool
    {
        return !empty($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false;
    }

    /**
     * Checks if the current request is a PUT request and attempts to parse any data present.
     *
     * @return bool True if it's a PUT request with data, false otherwise.
     */
    private function isPutRequest(): bool
    {
        return !empty(file_get_contents('php://input'));
    }

    /**
     * Retrieves the unified serverless environment array with an optional
     * configuration to include base64 encoded file contents.
     *
     * @param bool $includeFileContents Whether to include base64 encoded file contents.
     * @return array The environment array, potentially including file contents.
     */
    public function getEnvironment(bool $includeFileContents = false): array
    {
        if ($includeFileContents) {
            $this->includeFileContents($this->_SVRLS_MGK_ENV['fileSystem']['down']);
        }

        return $this->_SVRLS_MGK_ENV;
    }

     /**
     * Includes base64 encoded contents of files in the file system array.
     *
     * @param array &$fileSystemArray Reference to the file system part of the environment array.
     */
    private function includeFileContents(array &$fileSystemArray): void
    {
        foreach ($fileSystemArray as &$item) {
            if ($item['type'] === 'file') {
                $contents = @file_get_contents($item['name']);
                if ($contents !== false) {
                    $item['contents_base64'] = base64_encode($contents);
                } else {
                    $item['contents_base64'] = 'Error reading file';
                }
            }

            // Recursively include contents for directories
            if ($item['type'] === 'directory' && isset($item['contents'])) {
                $this->includeFileContents($item['contents']);
            }
        }
    }

    /**
     * Sets a global constant `SRVLS_PHP_ENV_MGK` with the base environment data.
     */
    public function setConstant(): void
    {
        if (!defined('SRVLS_PHP_ENV_MGK')) {
            $baseEnvironment = $this->getEnvironment();
            define('SRVLS_PHP_ENV_MGK', serialize($baseEnvironment));
        }
    }


    /**
     * Adds or updates a value in the serverless environment array.
     *
     * @param string $key The key under which the value should be stored.
     * @param mixed $value The value to store.
     */
    public function setEnvironmentValue(string $key, $value): void
    {
        $this->_SVRLS_MGK_ENV[$key] = $value;
    }
}
