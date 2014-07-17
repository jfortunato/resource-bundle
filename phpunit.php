<?php

// check if we are running tests as standalone project or if we are developing
// as part of a larger project
$composer_autoload = is_file(__DIR__ . '/vendor/autoload.php') ?
    __DIR__ . '/vendor/autoload.php':
    __DIR__ . '/../../autoload.php';

require_once $composer_autoload;
