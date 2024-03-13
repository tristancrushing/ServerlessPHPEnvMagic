// Required imports from the Node.js standard library
// import fs from 'fs';
// import path, { dirname } from 'path';
// import { fileURLToPath } from 'url';

/**
 * The ServerlessEnvMagic class provides utilities for managing the serverless
 * environment in Node.js applications. It facilitates access to environment variables,
 * performs synchronous file system exploration, and can conditionally include base64-encoded
 * contents of files based on available memory, adapting to constraints of serverless platforms.
 */
class ServerlessEnvMagic {
    /**
     * Constructs an instance of ServerlessEnvMagic, initializing the environment data structure.
     */
    constructor() {
        this.environment = {
            environmentVariables: process.env, // Capture the current environment variables
            fileSystem: {}, // Initialize an object to hold file system exploration results
        };
    }

    /**
     * Synchronously explores the file system from a given starting directory,
     * populating the environment data structure with details about files and directories encountered.
     *
     * @param {string} directory - The starting directory for exploration. Defaults to the directory of the current module.
     */
    exploreFileSystem(directory = dirname(fileURLToPath(import.meta.url))) {
        try {
            // Synchronously read the contents of the directory
            const entries = fs.readdirSync(directory, { withFileTypes: true });
            this.environment.fileSystem[directory] = []; // Prepare to store info about contents

            // Iterate over each entry in the directory
            for (const entry of entries) {
                const entryPath = path.join(directory, entry.name);
                if (entry.isDirectory()) {
                    // Recursively explore if the entry is a directory
                    this.exploreFileSystem(entryPath);
                } else if (entry.isFile()) {
                    // Gather file details if the entry is a file
                    const stats = fs.statSync(entryPath);
                    this.environment.fileSystem[directory].push({
                        name: entry.name,
                        type: 'file',
                        size: stats.size,
                        lastModified: stats.mtime.toISOString(),
                        contentsBase64: null, // Placeholder for optional base64 encoded content
                    });
                }
            }
        } catch (error) {
            // Log any errors encountered during file system exploration
            console.error(`Error accessing directory ${directory}: ${error.message}`);
        }
    }

    /**
     * Conditionally includes base64 encoded contents of files in the directory exploration results.
     * This method is invoked after exploreFileSystem if file content inclusion is desired and memory conditions allow.
     *
     * @param {string} directory - The directory to include file contents from. Defaults to the directory of the current module.
     */
    includeFileContents(directory = dirname(fileURLToPath(import.meta.url))) {
        if (!this.environment.fileSystem[directory]) {
            // Warn if no data was found for the specified directory
            console.warn(`No file system data found for directory: ${directory}`);
            return;
        }

        // Iterate over each file in the directory and include its base64 encoded content
        for (const file of this.environment.fileSystem[directory]) {
            if (file.type === 'file') {
                const filePath = path.join(directory, file.name);
                try {
                    const content = fs.readFileSync(filePath);
                    file.contentsBase64 = content.toString('base64');
                } catch (error) {
                    console.error(`Error reading file ${file.name}: ${error.message}`);
                    file.contentsBase64 = 'Error reading file'; // Mark as error if reading fails
                }
            }
        }
    }

    /**
     * Retrieves the constructed environment data, potentially including base64 encoded file contents
     * if previously included by includeFileContents and memory conditions were met.
     *
     * @param {boolean} includeFileContents - Whether to attempt including base64 encoded file contents.
     * @return {Object} - The environment data object.
     */
    getEnvironment(includeFileContents = false) {
        // Include file contents based on the flag and memory check
        if (includeFileContents && this.checkMemoryAndSetContentFlag()) {
            this.includeFileContents();
        }
        return this.environment;
    }

    /**
     * Checks the current memory usage to decide whether it's safe to include file contents.
     * Provides a simplistic check and can be adjusted based on specific requirements.
     *
     * @return {boolean} - True if memory usage conditions allow including file contents, false otherwise.
     */
    checkMemoryAndSetContentFlag() {
        // Check current heap usage against a threshold (768 MB in this example)
        const memoryUsage = process.memoryUsage().heapUsed;
        return memoryUsage < 768 * 1024 * 1024; // Threshold: less than 768 MB used
    }
}

// export default ServerlessEnvMagic;
