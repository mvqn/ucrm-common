<?php

use MVQN\UCRM\Plugins\Log;
use MVQN\UCRM\Plugins\Plugin;

class RestClientTests extends PHPUnit\Framework\TestCase
{
    protected function setUp()
    {
        $path = Plugin::rootPath(__DIR__."/../../../../examples/plugin-example/");
        echo "Using '$path' as the root path for testing purposes!\n\n";

    }

    public function testPaths()
    {
        echo "ROOT: ".Plugin::rootPath()."\n";

        $zip = Plugin::usingZip() ? "T" : "F";
        echo "ZIP?: ".$zip."\n";

        $data = Plugin::dataPath();
        echo "DATA: ".$data."\n";
    }

    public function testConfig()
    {
        $config = Plugin::config();
        echo Log::writeObject($config);
    }

    public function testData()
    {
        $config = Plugin::data();
        echo Log::writeObject($config);
    }



}