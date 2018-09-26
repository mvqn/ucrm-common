<?php

require __DIR__."/vendor/autoload.php";

use UCRM\Core\Log;

/**
 * main.php (required)
 *
 * Main file of the plugin. This is what will be executed when the plugin is run by UCRM.
 *
 */

chdir(__DIR__);

(function () {
    Log::write("Finished");
})();
