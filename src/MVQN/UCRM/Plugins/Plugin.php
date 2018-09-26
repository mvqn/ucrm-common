<?php
declare(strict_types=1);

namespace MVQN\UCRM\Plugins;


/**
 * Class Bundler
 *
 * @package UCRM\Plugins
 */
final class Plugin
{
    // =================================================================================================================
    // CONSTANTS
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @const string The default project base path, when following the folder structure in <b>ucrm-plugin-template.</b>
     */
    private const DEFAULT_PLUGIN_PATH =
        __DIR__."/../../../../../../../";
    /**
     * @const string The default .zipignore file path, in the root of the project, including filename.
     */
    private const DEFAULT_IGNORE_PATH =
        self::DEFAULT_PLUGIN_PATH.".zipignore";

    // =================================================================================================================
    // PROPERTIES
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @var string The root path of this Plugin.
     */
    private static $_rootPath = "";

    /**
     * @var bool Set to true if the Plugin is using the template/preferred folder structure inside the 'zip' folder.
     */
    private static $_usingZip = false;

    // =================================================================================================================
    // METHODS: Paths
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * Attempts to automatically determine the correct project (production/development) root path.  This method also
     * recognizes when the template/preferred folder structure is being used and makes adjustments as needed.
     *
     * @return string Returns the best candidate for the project (production/development) root path.
     */
    public static function autoPath(): string
    {
        // .../ucrm-common/
        $this_root = realpath(__DIR__."/../../../../");

        // .../mvqn/
        $mvqn_root = realpath($this_root."/../");

        // .../vendor/
        $vend_root = realpath($mvqn_root."/../");

        // .../<ucrm-plugin-name>/  (in plugins/ on UCRM Server)
        $ucrm_root = realpath($vend_root."/../");

        // IF the next two upper directories are recognized as composer's vendor folder and this package name...
        if(basename($mvqn_root) === "mvqn" && basename($vend_root) === "vendor")
        {
            // IF the current folder iz 'zip' then we are probably using the preferred/template folder structure...
            if(basename($ucrm_root) === "zip")
            {
                // SO, adjust the root path one more time.
                $ucrm_root = realpath($ucrm_root . "/../");
                self::$_usingZip = true;
            }
            else
            {
                self::$_usingZip = false;
            }

            // THEN set and return the path to the root of the Plugin using this library!
            self::$_rootPath = $ucrm_root;
            return $ucrm_root;
        }
        else
        {
            // OTHERWISE, set and return the path to the root of this library! (FOR TESTING)
            self::$_rootPath = $this_root;
            return $this_root;
        }
    }

    /**
     * Attempts to automatically determine the correct project (production/development) root path, or overrides the
     * automatic detection when a path is provided.  This method also recognizes when the template/preferred folder
     * structure is being used.
     *
     * @param string $path An optional overridden path to use in place of the automatically detected path.
     * @return string Returns the absolute ROOT path of this Plugin, regardless of development or production server.
     */
    public static function rootPath(string $path = ""): string
    {
        // IF an override path has been provided...
        if($path !== "")
        {
            self::$_rootPath = realpath($path);
            self::$_usingZip = file_exists(self::$_rootPath."/zip");
        }
        // OTHERWISE, no override path has been provided...
        else
        {
            // Get the previously saved/detected path, if any...
            if(self::$_rootPath === "")
                self::autoPath();
        }

        // Finally, return the root path!
        return self::$_rootPath;
    }

    /**
     * @return bool
     */
    public static function usingZip(): bool
    {
        return self::rootPath() !== "" && self::$_usingZip;
    }

    /**
     * @return string Returns the absolute DATA path of this Plugin, regardless of development or production server.
     */
    public static function dataPath(): string
    {
        return realpath(self::rootPath().(self::$_usingZip ? "/zip" : "")."/data/");
    }

    // =================================================================================================================
    // METHODS: States (executing/running)
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @return bool Returns true if this Plugin is pending execution, otherwise false.
     */
    public static function executing(): bool
    {
        // NEVER really going to be in the 'zip' folder here!
        return file_exists(self::rootPath().(self::$_usingZip ? "/zip" : "")."/.ucrm-plugin-execution-requested");
    }

    /**
     * @return bool Returns true if this Plugin is currently executing, otherwise false.
     */
    public static function running(): bool
    {
        // NEVER really going to be in the 'zip' folder here!
        return file_exists(self::rootPath().(self::$_usingZip ? "/zip" : "")."/.ucrm-plugin-running");
    }

    // =================================================================================================================
    // METHODS: Objects (Config, Manifest, Data)
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @return Config Returns the data/config.json of this Plugin.
     * @throws RequiredFileNotFoundException Thrown an exception when a config.json file cannot be found.
     */
    public static function config(): Config
    {
        $config_file = self::dataPath()."/config.json";

        if(!file_exists($config_file))
            throw new RequiredFileNotFoundException(
                "A 'config.json' file could not be found at '".$config_file."'.");

        $json = file_get_contents($config_file);

        return new Config($json);
    }

    /**
     * @return Manifest Returns the manifest.json of this Plugin as a Manifest object.
     * @throws RequiredFileNotFoundException Thrown an exception when a manifest.json file cannot be found.
     */
    public static function manifest(): Manifest
    {
        $manifest_file = self::rootPath().(self::$_usingZip ? "/zip" : "")."/manifest.json";

        if(!file_exists($manifest_file))
            throw new RequiredFileNotFoundException(
                "A 'manifest.json' file could not be found at '".$manifest_file."'.");

        return new Manifest($manifest_file);
    }

    /**
     * @return Data Returns the Data from the ucrm.json file that was generated by the UCRM.
     * @throws RequiredFileNotFoundException Thrown an exception when a ucrm.json file cannot be found.
     */
    public static function data(): Data
    {
        $data_file = self::rootPath().(self::$_usingZip ? "/zip" : "")."/ucrm.json";

        if(!file_exists($data_file))
            throw new RequiredFileNotFoundException(
                "The 'ucrm.json' file could not be found at '".self::$_rootPath."'.");

        $json = file_get_contents($data_file);

        return new Data($json);
    }

    // =================================================================================================================
    // METHODS: Bundling
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * Checks an optional .zipignore file for inclusion of the specified string.
     *
     * @param string $path The relative path for which to search in the ignore file.
     * @param string $ignore The path to the optional ignore file, defaults to project root.
     *
     * @return bool Returns true if the path is found in the file, otherwise false.
     */
    private static function inIgnoreFile(string $path, string $ignore = ""): bool
    {
        $ignore = $ignore ?: realpath(self::DEFAULT_IGNORE_PATH);

        if (!file_exists($ignore))
            return false;

        $lines = explode(PHP_EOL, file_get_contents($ignore));

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === "")
                continue;

            $starts_with = substr($line, 0, 1) !== "#" && substr($path, 0, strlen($line)) === $line;

            if ($starts_with)
                return true;
        }

        return false;
    }

    /**
     * Creates a zip archive for use when installing this Plugin.
     *
     * @param string $root Path to root of the project, defaults to currently determined root path.
     * @param string $ignore Path to the optional .zipignore file, defaults to project root.
     */
    public static function bundle(string $root = "", string $ignore = ""): void
    {
        echo "Bundling...\n";

        $root = realpath($root ?: self::rootPath());
        $ignore = realpath($ignore ?: self::rootPath()."/.zipignore");

        $archive_name = basename($root);
        $archive_path = $root."/zip/";

        echo "$archive_path => $archive_name.zip\n";

        $directory = new \RecursiveDirectoryIterator($archive_path);
        $file_info = new \RecursiveIteratorIterator($directory);

        $files = [];
        foreach ($file_info as $info)
        {
            $real_path = $info->getPathname();
            $file_name = $info->getFilename();

            // Skip /. and /..
            if($file_name === "." || $file_name === "..")
                continue;

            $path = str_replace($root, "", $real_path); // Remove base path from the path string.
            $path = str_replace("\\", "/", $path); // Match .zipignore format
            $path = substr($path, 1, strlen($path) - 1);

            if (!self::inIgnoreFile($path, $ignore))
            {
                $files[] = $path;
                echo "ADDED  : $path\n";
            }
            else
                echo "IGNORED: $path\n";
        }

        $zip = new \ZipArchive();
        $file_name = $root."/$archive_name.zip";

        if ($zip->open($file_name, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            exit("Unable to create $file_name!\n");
        }

        // Save the current working directory and move to the root of the project for the next steps!
        $old_dir = getcwd();
        chdir($root);

        // Loop through each file in the list...
        foreach ($files as $file) {
            // Ensure .zipignore directory separators are converted to OS separators.
            //$path = str_replace("/", DIRECTORY_SEPARATOR, $file);
            $path = $file;

            // Remove the leading folder, as we do not want that structure in the ZIP archive.
            $local = str_replace("zip/", "", $path);

            // Add the file to the archive.
            $zip->addFile($path, $local);
        }

        $total_files = $zip->numFiles;
        $status = $zip->status !== 0 ? $zip->getStatusString() : "SUCCESS!";

        echo "FILES  : $total_files\n";
        echo "STATUS : $status\n";

        // Close the archive, we're all finished!
        $zip->close();

        // Return to the previous working directory.
        chdir($old_dir);
    }






}