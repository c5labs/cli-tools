<?php

namespace C5Dev\Rebar;

use Illuminate\Support\ServiceProvider;

class CommandServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->add(new \C5Dev\Rebar\Console\MakePackageCommand());
    }
}
