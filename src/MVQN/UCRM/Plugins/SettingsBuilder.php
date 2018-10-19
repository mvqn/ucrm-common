<?php
declare(strict_types=1);

namespace MVQN\UCRM\Plugins;

use Nette\PhpGenerator\PhpNamespace;

final class SettingsBuilder
{
    private const CLASS_NAME = "Settings";
    private const CLASS_BASE = "MVQN\UCRM\Plugins\SettingsBase";

    /**
     * Generates a class with auto-implemented methods and then saves it to a PSR-4 compatible file.
     * @param string $namespace An optional namespace in which to include this class.
     * @throws \Exception Throws an Exception if any errors occur.
     */
    public static function generate(string $namespace = ""): void
    {
        $root = Plugin::usingZip() ? Plugin::rootPath()."/zip/" : Plugin::rootPath();
        $path = $root."/src/".str_replace("\\", "/", $namespace);

        if(!file_exists($root."/manifest.json"))
            return;

        if(!file_exists($path))
            mkdir($path, 0777, true);

        $data = json_decode(file_get_contents($root."/manifest.json"), true);
        $data = array_key_exists("configuration", $data) ? $data["configuration"] : [];

        $_namespace = new PhpNamespace($namespace);
        //$_namespace->addUse("MVQN\\UCRM\\Plugins\\Setting");
        $_namespace->addUse("MVQN\\UCRM\\Plugins\\SettingsBase");

        $_class = $_namespace->addClass(self::CLASS_NAME);
        $_class
            ->setFinal()
            ->setExtends(self::CLASS_BASE)
            ->addComment("@author Ryan Spaeth <rspaeth@mvqn.net>\n");

        foreach($data as $setting)
        {
            $_setting = new Setting($setting);

            $type = $_setting->type.(!$_setting->required ? "|null" : "");

            $_property = $_class->addProperty($_setting->key);
            $_property
                ->setVisibility("protected")
                ->setStatic()
                ->addComment("{$_setting->label}")
                ->addComment("@var {$type} {$_setting->description}");

            $getter = "get".ucfirst($_setting->key);

            $_class->addComment("@method static $type $getter()");
        }

        $code =
            "<?php\n".
            "declare(strict_types=1);\n".
            "\n".
            $_namespace;

        file_put_contents($path."/".self::CLASS_NAME.".php", $code);

    }


}