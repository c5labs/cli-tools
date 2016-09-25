<?php

namespace C5Dev\Rebar\FileExporter;

use Illuminate\Support\ServiceProvider;

class FileExporterServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->bind(FileExporter::class, function() {
            return new FileExporter($this->app->make('files'));
        });
    }
}