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
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->addCommand(\C5Labs\Cli\Console\MakePackageCommand::class);
        $this->app->addCommand(\C5Labs\Cli\Console\MakeThemeCommand::class);
        $this->app->addCommand(\C5Labs\Cli\Console\MakeBlockTypeCommand::class);
        $this->app->addCommand(\C5Labs\Cli\Console\MakeBlockTypeTemplateCommand::class);
        $this->app->addCommand(\C5Labs\Cli\Console\ConfigCommand::class);
        $this->app->addCommand(\C5Labs\Cli\Console\ClearCacheCommand::class);
        $this->app->addCommand(\C5Labs\Cli\Console\ShellCommand::class);
        $this->app->addCommand(\C5Labs\Cli\Console\InfoCommand::class);

        // Add the pharize command if we're not already running as one.
        if (empty(Phar::running())) {
            $this->app->addCommand(new \C5Labs\Cli\Console\PharCommand());
        }
    }
}
