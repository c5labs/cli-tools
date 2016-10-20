<?php

/*
 * This file is part of Cli.
 *
 * (c) Oliver Green <oliver@c5labs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace C5Labs\Cli;

use Illuminate\Support\ServiceProvider;

class ConcreteServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton('concrete', function () {
            return $this->bootConcreteInstance();
        });
    }

    protected function bootConcreteInstance()
    {
        global $argv;

        // Set  debug mode.
        $debug = (in_array('--debug', $argv));

         // Set the base paths.
        $__DIR__ = $this->app->getConcretePath();
        define('DIR_BASE', realpath($__DIR__.'/../'));

        try {

            // Set the handler so that we can control and hide error messages.
            set_error_handler(function ($errno, $errstr, $errfile, $errline) use ($debug) {
                if ($debug) {
                    // Bubble errors up to exceptions.
                    throw new \ErrorException($errstr, $errno, E_ERROR, $errfile, $errline);
                }
            });

            /**
             * ----------------------------------------------------------------------------
             * Set required constants, including directory names, attempt to include site configuration file with database
             * information, attempt to determine if we ought to skip to an updated core, etc...
             * ----------------------------------------------------------------------------.
             */
            require $__DIR__.'/bootstrap/configure.php';

            /**
             * ----------------------------------------------------------------------------
             * Include all autoloaders
             * ----------------------------------------------------------------------------.
             */
            require $__DIR__.'/bootstrap/autoload.php';

            /*
             * ----------------------------------------------------------------------------
             * Begin concrete5 startup.
             * ----------------------------------------------------------------------------
             */
            $cms = require $__DIR__.'/bootstrap/start.php';

            $cms->setupPackageAutoloaders();

            $cms->setupPackages();
        } catch (\Exception $ex) {
            // If we're in debug rethrow any exceptions.
            if ($debug) {
                throw $ex;
            }
        } finally {
            restore_error_handler();
        }

        // We can't continue without a valid  reference to the CMS.
        if (! isset($cms) || ! is_object($cms)) {
            throw new \Exception('Failed to boot concrete, please verify your installation in your browser.');
        }

        return $cms;
    }
}
