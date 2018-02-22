<?php
/**
 * Created by PhpStorm.
 * User: yomi
 * Date: 11/25/17
 * Time: 5:46 AM
 */

return function ($prefix, $baseDir) {
    spl_autoload_register(function ($class) use ($prefix, $baseDir) {
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            return;
        }
        $relative_class = substr($class, $len);
        $file = $baseDir . str_replace('\\', '/', $relative_class) . '.php';
        $file = strtolower($file);

        if (file_exists($file)) {
            require $file;
        }
    });
};
