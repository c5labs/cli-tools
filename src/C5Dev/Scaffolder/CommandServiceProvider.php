<?php

/*
 * This file is part of Scaffolder.
 *
 * (c) Oliver Green <oliver@c5dev.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace C5Dev\Scaffolder;

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
        $this->app->addCommand(\C5Dev\Scaffolder\Console\MakePackageCommand::class);
        $this->app->addCommand(\C5Dev\Scaffolder\Console\MakeThemeCommand::class);
        $this->app->addCommand(\C5Dev\Scaffolder\Console\MakeBlockTypeCommand::class);

        // Add the pharize command if we're not already running as one.
        if (empty(Phar::running())) {
            $this->app->addCommand(new \C5Dev\Scaffolder\Console\CompilePharCommand());
        }
    }
}
