<?php

$mu_plugins = glob(__DIR__, GLOB_ONLYDIR);
foreach ($mu_plugins as $path) {
    $name = basename($path);

    // 1. {plugin-name}/{plugin-name}.php
    // 2. {plugin-name}/index.php
    // 2. {plugin-name}/plugin.php

    $files[] = $path . '/' . $name . '.php';
    $files[] = $path . '/index.php';
    $files[] = $path . '/plugin.php';

    foreach ($files as $file) {
        if (file_exists($file)) {
            require_once $file;
            break;
        }
    }
}
