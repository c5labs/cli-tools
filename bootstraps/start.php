<?php

/*
 * This file is part of Scaffolder.
 *
 * (c) Oliver Green <oliver@c5dev.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if (file_exists(__DIR__.'/../vendor/autoload.php')) {
    $path = __DIR__.'/..';
} elseif (file_exists(__DIR__.'/../../../../vendor/autoload.php')) {
    $path = __DIR__.'/../../../..';
} else {
    throw new Exception('Please ensure you have installed this packages depencencies via composer install.');
}

require $path.'/vendor/autoload.php';
require $path.'/vendor/illuminate/support/helpers.php';

$console = new \C5Dev\Scaffolder\Application();
$console->run();
