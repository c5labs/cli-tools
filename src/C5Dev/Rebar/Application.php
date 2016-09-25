<?php

namespace C5Dev\Rebar;

use Illuminate\Container\Container;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Console\Application as App;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Application extends App
{
    protected $providers = [
        \Illuminate\Bus\BusServiceProvider::class,
        \Illuminate\Filesystem\FilesystemServiceProvider::class,
        \C5Dev\Rebar\CommandServiceProvider::class,
        \C5Dev\Rebar\FileExporter\FileExporterServiceProvider::class,
    ];

    public function __construct($container = null)
    {
        if (! $container) {
            $container = new Container();
        }

        $container['base_path'] = realpath(__DIR__.'/../../../');

        $container->instance(\C5Dev\Rebar\Application::class, $this);

        $this->container = $container;

        parent::__construct('Rebar', '0.1.0');

        $this->registerProviders();
    }

    protected function registerProviders()
    {
        foreach ($this->providers as $provider) {
            $provider = $this->resolveProviderClass($provider);

            if (method_exists($provider, 'register')) {
                $provider->register();
            }
        }
    }

    public function run(InputInterface $input = null, OutputInterface $output = null)
    {
        $this->bootProviders();

        return parent::run($input, $output);
    }

    protected function bootProviders()
    {
        foreach ($this->providers as $provider) {
            $this->bootProvider(
                $this->resolveProviderClass($provider)
            );
        }
    }

    /**
     * Boot the given service provider.
     *
     * @param  \Illuminate\Support\ServiceProvider  $provider
     * @return mixed
     */
    protected function bootProvider(ServiceProvider $provider)
    {
        if (method_exists($provider, 'boot')) {
            return $this->call([$provider, 'boot']);
        }
    }

    /**
     * Resolve a service provider instance from the class name.
     *
     * @param  string  $provider
     * @return \Illuminate\Support\ServiceProvider
     */
    public function resolveProviderClass($provider)
    {
        return new $provider($this);
    }

    public function __call($name, $params)
    {
        return call_user_func_array([$this->container, $name], $params);
    }
}
