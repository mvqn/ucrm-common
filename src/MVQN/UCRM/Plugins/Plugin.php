<?php
declare(strict_types=1);

namespace UCRM\Plugins;


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
    // PRIVATE METHODS
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * Bundler::inIgnoreFile()
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

        if (!file_exists($ignore)) {
            return false;
        }

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

    // =================================================================================================================
    // PUBLIC METHODS
    // -----------------------------------------------------------------------------------------------------------------

    /**
     * Plugin::rootPath()
     *
     * Attempts to automatically determine the correct project root path, or can override the automatic detection by
     * providing the actual project root.  This method also recognizes when the template/preferred folder structure is
     * being used.
     *
     * @param string|null $path An optional overridden path to use in place of the automatically detected path.
     * @param bool $save An optional flag to determine whether or not the overridden path is saved for future use.
     * @return string Returns the absolute ROOT path of this Plugin, regardless of development or production server.
     */
    public static function rootPath(?string $path = "", bool $save = false): string
    {
        // IF an override path has been provided...
        if($path !== "" && $path !== null)
        {
            // AND save is set, save this overridden path for future use...
            if($save)
                self::$_rootPath = realpath($path);

            // OTHERWISE, return this overridden path only this one-time!
            return realpath($path);
        }
        // OTHERWISE, no override path has been provided...
        else
        {
            // AND save is set, reset to automatic detection...
            if($save)
                self::$_rootPath = "";

            // OTHERWISE, get the previously saved/detected path!
            if(self::$_rootPath !== "")
                return self::$_rootPath;
        }

        // .../ucrm-plugin-core/
        $this_root = realpath(__DIR__."/../../../");

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

    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @return string Returns the absolute DATA path of this Plugin, regardless of development or production server.
     */
    public static function dataPath(): string
    {
        return realpath(self::rootPath().(self::$_usingZip ? "/zip" : "")."/data/");
    }

    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @return bool Returns true if this Plugin is pending execution, otherwise false.
     */
    public static function executing(): bool
    {
        return file_exists(self::rootPath().(self::$_usingZip ? "/zip" : "")."/.ucrm-plugin-execution-requested");
    }

    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @return bool Returns true if this Plugin is currently executing, otherwise false.
     */
    public static function running(): bool
    {
        // NEVER really going to be in the 'zip' folder here!
        return file_exists(self::rootPath().(self::$_usingZip ? "/zip" : "")."/.ucrm-plugin-running");
    }

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

    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @return Manifest Returns the manifest.json of this Plugin as a Manifest object.
     * @throws RequiredFileNotFoundException Thrown an exception when a manifest.json file cannot be found.
     */
    public static function manifest(): Manifest
    {
        $manifest_file = self::rootPath().(self::$_usingZip ? "/zip" : "")."/manifest.json";

        if(!file_exists($manifest_file))
            throw new RequiredFileNotFoundException(
                "A manifest.json file could not be found at '".$manifest_file."'.");

        return new Manifest($manifest_file);
    }

    // -----------------------------------------------------------------------------------------------------------------

    /**
     * @param string $message A message to output to the plugin's log.
     * @return string Returns the entire message, including timestamp, as output to the log file.
     */
    public static function log(string $message): string
    {
        return Log::write($message);
    }

    // -----------------------------------------------------------------------------------------------------------------

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


    /**
     * Bundler::bundle()
     *
     * Creates a zip archive for use when installing this Plugin.
     *
     * @param string $root Path to root of the project.
     * @param string $ignore Path to the optional .zipignore file.
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


    public static function usingZip(): bool
    {
        return self::$_usingZip;
    }



}