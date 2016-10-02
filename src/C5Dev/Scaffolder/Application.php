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

use C5Dev\Scaffolder\ApplicationContract;
use Illuminate\Container\Container;
use Illuminate\Support\ServiceProvider;
use Phar;
use Symfony\Component\Console\Application as App;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class Application extends App implements ApplicationContract
{
    /**
     * Service Providers.
     *
     * @var array
     */
    protected $providers = [
        \Illuminate\Bus\BusServiceProvider::class,
        \Illuminate\Filesystem\FilesystemServiceProvider::class,
        \C5Dev\Scaffolder\CommandServiceProvider::class,
        \C5Dev\Scaffolder\FileExporter\FileExporterServiceProvider::class,
    ];

    /**
     * Default installation paths for objects.
     * 
     * @var array
     */
    protected $default_install_paths = [
        'package' => 'packages',
        'theme' => 'application/themes',
        'block_type' => 'application/blocks',
    ];

    /**
     * Constructor.
     *
     * @return  void
     */
    public function __construct()
    {
        $this->setContainer(new Container());

        parent::__construct('c5 Scaffolder', '0.1.0');

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
        $container->instance(\C5Dev\Scaffolder\ApplicationContract::class, $this);

        $this->container = $container;

        $this->setBasePaths();
    }

    /**
     * Set the application base paths.
     *
     * @return  void
     */
    protected function setBasePaths()
    {
        $this->container['export_path']
            = $this->container['base_path']
            = realpath(__DIR__.'/../../../');

        if (! empty(Phar::running())) {
            $this->container['base_path'] = Phar::running();
            $this->container['export_path'] = getcwd();
        }
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
     * Get a default installation path for an object type.
     * 
     * @param  string $object_type [description]
     * @return void|string
     */
    public function getDefaultInstallPath($object_type)
    {
        if (array_key_exists($object_type, $this->default_install_paths)) {
            return $this->default_install_paths[$object_type];
        }
    }

    /**
     * Get the application banner.
     *
     * @return string
     */
    public function getBanner()
    {
        $help = '<fg=yellow>        ______</>                   ________      __    __         '.PHP_EOL;
        $help .=  '<fg=yellow>  _____/ ____/</>  ______________ _/ __/ __/___  / /___/ /__  _____'.PHP_EOL;
        $help .=  '<fg=yellow> / ___/___ \  </> / ___/ ___/ __  / /_/ /_/ __ \/ / __  / _ \/ ___/'.PHP_EOL;
        $help .=  '<fg=yellow>/ /______/ /  </>(__  ) /__/ /_/ / __/ __/ /_/ / / /_/ /  __/ /    '.PHP_EOL;
        $help .=  '<fg=yellow>\___/_____/  </>/____/\___/\__,_/_/ /_/  \____/_/\__,_/\___/_/     '.PHP_EOL;
        $help .= ''.PHP_EOL;
        $help .= '<fg=green>'.$this->getLongVersion().'</>'.PHP_EOL;

        return $help;
    }

    /**
     * Get the application help text.
     * 
     * @return string
     */
    public function getHelp()
    {
        return $this->getBanner();
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
        if (method_exists($this->container, $name)) {
            return call_user_func_array([$this->container, $name], $params);
        }
    }
}
