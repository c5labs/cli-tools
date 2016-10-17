<?php

/*
 * This file is part of Scaffolder.
 *
 * (c) Oliver Green <oliver@c5labs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace C5Labs\Scaffolder\FileExporter;

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
