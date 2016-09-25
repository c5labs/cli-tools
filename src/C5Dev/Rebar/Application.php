<?php

/*
 * This file is part of Rebar.
 *
 * (c) Oliver Green <oliver@c5dev.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace C5Dev\Rebar;

use Illuminate\Container\Container;
use Illuminate\Support\ServiceProvider;
use Symfony\Component\Console\Application as App;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Application extends App
{
    /**
     * Service Providers.
     *
     * @var array
     */
    protected $providers = [
        \Illuminate\Bus\BusServiceProvider::class,
        \Illuminate\Filesystem\FilesystemServiceProvider::class,
        \C5Dev\Rebar\CommandServiceProvider::class,
        \C5Dev\Rebar\FileExporter\FileExporterServiceProvider::class,
    ];

    /**
     * Constructor.
     *
     * @return  void
     */
    public function __construct()
    {
        $this->setContainer(new Container());

        parent::__construct('Rebar', '0.1.0');

        $this->registerProviders();
    }

    /**
     * Set the applications container instance.
     *
     * @param \Illuminate\Container\Container $container
     * @return  void
     */
    public function setContainer(Container $container)
    {
        $container['base_path'] = realpath(__DIR__.'/../../../');

        $container->instance(\C5Dev\Rebar\Application::class, $this);

        $this->container = $container;
    }

    /**
     * Register the applications service providers.
     *
     * @return void
     */
    protected function registerProviders()
    {
        foreach ($this->providers as $provider) {
            $provider = $this->resolveProviderClass($provider);

            if (method_exists($provider, 'register')) {
                $provider->register();
            }
        }
    }

    /**
     * Boot the applications service providers.
     *
     * @return void
     */
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

    /**
     * Runs the current application.
     *
     * {@inheritdoc}
     */
    public function run(InputInterface $input = null, OutputInterface $output = null)
    {
        $this->bootProviders();

        return parent::run($input, $output);
    }

    /**
     * Pass any unknown method calls to the container instance.
     *
     * @param  string $name
     * @param  array $params
     * @return mixed
     */
    public function __call($name, $params)
    {
        return call_user_func_array([$this->container, $name], $params);
    }
}
