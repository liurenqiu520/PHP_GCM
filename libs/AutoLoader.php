<?php
/**
 * @see http://php.net/manual/ja/language.namespaces.php#103606
 */
class AutoLoader
{

    public static function addIncludePath(array $dirs)
    {
        $currentPath = ini_get('include_path');
        if ($currentPath) {
            $currentPath = explode(PATH_SEPARATOR, $currentPath);
            $dirs = array_merge($currentPath, $dirs);
            $dirs = array_unique($dirs);
        }
        ini_set('include_path', implode(PATH_SEPARATOR, $dirs));
    }

    // here we store the already-initialized namespaces
    private static $loadedNamespaces = array();

    static function loadClass($className)
    {

        // we assume the class AAA\BBB\CCC is placed in /AAA/BBB/CCC.php
        $className = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $className);

        // we get the namespace parts
        $namespaces = explode(DIRECTORY_SEPARATOR, $className);
        unset($namespaces[sizeof($namespaces) - 1]);

        // now we loops over namespaces
        $current = "";
        foreach ($namespaces as $namepart) {
            // we chain $namepart to parent namespace string
            $current .= '\\' . $namepart;
            // skip if the namespace is already initialized
            if (in_array($current, self::$loadedNamespaces)) continue;
            // wow, we got a namespace to load, so:
            $fnload = $current . DIRECTORY_SEPARATOR . "__init.php";
            if (file_exists($fnload)) require($fnload);
            // then we flag the namespace as already-loaded
            self::$loadedNamespaces[] = $current;
        }

        // we build the filename to require
        $loadFile = $className . ".php";

        self::isSafeFileName($loadFile);

        require($loadFile);

        return class_exists($className, false);
    }

    static function register()
    {
        spl_autoload_register("AutoLoader::loadClass");
    }

    static function unregister()
    {
        spl_autoload_unregister("AutoLoader::loadClass");
    }


    /**
     * ファイル文字列が安全か
     *
     * @param string $filename
     * @throws Exception
     */
    private static function isSafeFileName($filename)
    {
        if (!is_string($filename)) {
            throw new \Exception('$filename must be a string');
        }

        if (preg_match('/[^a-z0-9\\/\\\\_.-]/i', $filename)) {
            throw new \Exception('Load Error: Illegal Filename');
        }
    }
}
