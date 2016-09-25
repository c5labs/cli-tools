<?php

/*
 * This file is part of Rebar.
 *
 * (c) Oliver Green <oliver@c5dev.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace C5Dev\Rebar\FileExporter;

use Illuminate\Support\ServiceProvider;

class FileExporterServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(FileExporter::class, function () {
            return new FileExporter($this->app->make('files'));
        });
    }
}
