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

use Phar;
use Illuminate\Support\ServiceProvider;

class CommandServiceProvider extends ServiceProvider
{
    /**
     * Commands to be registered.
     * 
     * @var array
     */
    protected $commands = [
        \C5Labs\Cli\Console\MakePackageCommand::class,
        \C5Labs\Cli\Console\MakeThemeCommand::class,
        \C5Labs\Cli\Console\MakeBlockTypeCommand::class,
        \C5Labs\Cli\Console\MakeBlockTypeTemplateCommand::class,
        \C5Labs\Cli\Console\ConfigCommand::class,
        \C5Labs\Cli\Console\ClearCacheCommand::class,
        \C5Labs\Cli\Console\ShellCommand::class,
        \C5Labs\Cli\Console\InfoCommand::class,
        \C5Labs\Cli\Console\BackupCommand::class,
    ];

    /**
     * Command names that require the CMS to be installed.
     * 
     * @var array
     */
    protected $command_restrictions = [
        'require_installed'  => [
            'ClearCacheCommand',
            'InstallPackageCommand',
            'UninstallPackageCommand',
            'UpdatePackageCommand',
            'JobCommand',
        ]
    ];

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        // Add the pharize command if we're not already running as one.
        if (empty(Phar::running())) {
            $this->app->addCommand(new \C5Labs\Cli\Console\PharCommand());
        }

        // Add the loaded cores CLI commands
        if ($path = $this->app->getConcretePath()) {
            $finder = new \Symfony\Component\Finder\Finder();
            $files = $finder->files()->in($path.'/src/Console/Command');

            foreach ($finder as $file) {
                $this->commands[] = "Concrete\\Core\\Console\\Command\\".$file->getBasename('.php');
            }

            $cms = $this->app->make('concrete');
        }

        // Add all of the queued commands.
        foreach ($this->commands as $command) {
            $reflect = new \ReflectionClass($command);
            $require_cms_installed = in_array(
                $reflect->getShortName(), $this->command_restrictions['require_installed']
            );

            if ($require_cms_installed && (! isset($cms) || ! $cms->isInstalled())) {
                continue;
            }

            $this->app->addCommand($command);
        }
    }
}
