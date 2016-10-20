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
        ],
    ];

    /**
     * Aliases for the core commands.
     * 
     * @var array
     */
    protected $command_aliases = [
        'ClearCacheCommand' => 'clear-cache',
        'ConfigCommand' => 'core-config',
        'ExecCommand' => 'exec',
        'GenerateIDESymbolsCommand' => 'ide-symbols',
        'InstallCommand' => 'install',
        'JobCommand' => 'job',
        'ResetCommand' => 'reset',
        'InstallPackageCommand' => 'package:install',
        'PackPackageCommand' => 'package:pack',
        'TranslatePackageCommand' => 'package:translate',
        'UninstallPackageCommand' => 'package:uninstall',
        'UpdatePackageCommand' => 'package:update',
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
                $this->commands[] = 'Concrete\\Core\\Console\\Command\\'.$file->getBasename('.php');
            }

            $cms = $this->app->make('concrete');
        }

        // Add all of the queued commands.
        foreach ($this->commands as $command) {
            $reflect = new \ReflectionClass($command);
            $class_name = $reflect->getShortName();

            $require_cms_installed = in_array(
                $class_name, $this->command_restrictions['require_installed']
            );

            if ($require_cms_installed && (! isset($cms) || ! $cms->isInstalled())) {
                continue;
            }

            if (isset($this->command_aliases[$class_name])) {
                $command = $this->app->make($command);
                $command->setName($this->command_aliases[$class_name]);
            }

            $command = $this->app->addCommand($command);
        }
    }
}
