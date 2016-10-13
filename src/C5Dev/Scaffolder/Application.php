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

use Illuminate\Container\Container;
use Illuminate\Contracts\Console\Application as ApplicationContract;
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
        'theme' => 'themes',
        'block_type' => 'blocks',
        'block_type_template' => 'templates',
    ];

    /**
     * Base path to this applications files.
     * 
     * @var string
     */
    protected $app_base_path;

    /**
     * The current working directory.
     * 
     * @var string
     */
    protected $current_working_directory;

    /**
     * Path to concrete5.
     * 
     * @var string
     */
    protected $concrete_path = null;

    /**
     * Tracks where the application is being run from so that we know where to place files. 
     * 
     * Possible values are:
     * generic - We're not at any special path
     * concrete - We're at the root of a concrete5 installation 
     * package - We're at the root of a package 
     * block - We're at the root of a blook.
     * 
     * @var string
     */
    protected $working_directory_type = 'generic';

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
        $container->instance(\Illuminate\Contracts\Console\Application::class, $this);

        $this->container = $container;

        $this->setBasePaths();
    }

    /**
     * Gets the applications container.
     * 
     * @return \Illuminate\Container\Container
     */
    public function getContainer()
    {
        return $this->container;
    }

    /**
     * Get the applications base path.
     * 
     * @return string
     */
    public function getAppBasePath()
    {
        return $this->app_base_path;
    }

    /**
     * Get the path to the current working directory.
     * 
     * @return string
     */
    public function getCurrentWorkingDirectory()
    {
        return $this->current_working_directory;
    }

    /**
     * Get the path to the core concrete5 path.
     * 
     * @return null|string
     */
    public function getConcretePath()
    {
        return $this->concrete_path;
    }

    /**
     * Get the current working paths type.
     * 
     * @return string
     */
    public function getWorkingDirectoryType()
    {
        return $this->working_directory_type;
    }

    /**
     * Set the current working directory type.
     * 
     * @param string $type
     */
    public function setWorkingDirectoryType($type)
    {
        $this->working_directory_type = $type;
    }

    /**
     * Set the application base paths.
     *
     * @return  void
     */
    protected function setBasePaths()
    {
        $this->current_working_directory
            = $this->app_base_path
            = realpath(__DIR__.'/../../../');

        if (! empty(Phar::running())) {
            $this->app_base_path = Phar::running();
            $this->current_working_directory = getcwd();
        }

        $this->concrete_path = $this->determineConcreteCorePath();
        $this->working_directory_type = $this->determineWorkingDirectoryType();
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
            return $this->container->call([$provider, 'boot']);
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
     * Find the path to concrete5s core files.
     * 
     * @return null|string
     */
    public function determineConcreteCorePath()
    {
        $cwd = $this->getCurrentWorkingDirectory();

        $search_paths = [
            // at concrete5 root
            '/concrete',
            '/vendor/concrete5/concrete5',

            // in packages/package_name 
            '/../../concrete',
            '/../../vendor/concrete5/concrete5',

            // in packages/package_name/blocks/block_name
            '/../../../../concrete',
            '/../../../../vendor/concrete5/concrete5',

            // in application/blocks/block_name
            '/../../../concrete',
            '/../../../vendor/concrete5/concrete5',
        ];

        // Search for a concrete5 installation
        foreach ($search_paths as $path) {
            if (is_dir(realpath($cwd.$path))) {
                // Set the path tot the core conrete installation.
                return realpath($cwd.$path);
            }
        }

        return;
    }

    /**
     * Determine whether we are at the root of an object directory.
     * 
     * @return string
     */
    public function determineWorkingDirectoryType()
    {
        $working_directory_type = null;
        $cwd = $this->getCurrentWorkingDirectory();

        // If we're at the root of an installation, mark it.
        if (is_dir($cwd.'/concrete') || is_dir($cwd.'/vendor/concrete5/concrete5')) {
            return 'concrete';
        }

        // We're at a package root.
        elseif ('packages' === basename(realpath($cwd.'/../')) && $this->getConcretePath()) {
            return 'package';
        }

        // We're at a block root.
        elseif ('blocks' === basename(realpath($cwd.'/../')) && $this->getConcretePath()) {
            return 'block';
        }

        // We are not anywhere special.
        return 'generic';
    }

    /**
     * Adds a command object.
     *
     * If a command with the same name already exists, it will be overridden.
     * If the command is not enabled it will not be added.
     *
     * @param Command|string $command A Command object
     *
     * @return Command|null The registered command if enabled or null
     */
    public function addCommand($command)
    {
        if (is_string($command)) {
            $command = $this->make($command);
        }

        return parent::add($command);
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

    public function getObjectInstallPath($object_type, $destination_path = null)
    {
        if (! $destination_path) {
            $destination_path = $this->getCurrentWorkingDirectory();
        }

        if ($path = $this->getDefaultInstallPath($object_type)) {
            // Concrete Base Directory Blocks & Themes
            if (in_array($object_type, ['block_type', 'theme']) && 'concrete' === $this->getWorkingDirectoryType()) {
                return $destination_path.'/application/'.$path;
            }

            // Concrete Base Directory Pachages
            elseif ('package' === $object_type && 'concrete' === $this->getWorkingDirectoryType()) {
                return $destination_path.'/'.$path;
            }

            // Package Directory Blocks & themes
            elseif (in_array($object_type, ['block_type', 'theme']) && 'package' === $this->getWorkingDirectoryType()) {
                return $destination_path.'/'.$path;
            }

            // Block Directory Templates
            elseif ('block_type_template' === $object_type && 'block' === $this->getWorkingDirectoryType()) {
                return $destination_path.'/'.$path;
            }

            // Everywhere else
            else {
                return $destination_path;
            }
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
     * Call a console application command.
     *
     * @param  string  $command
     * @param  array  $parameters
     * @return int
     */
    public function call($command, array $parameters = [])
    {
        throw new \Exception('Not implemented');
    }

    /**
     * Get the output from the last command.
     *
     * @return string
     */
    public function output()
    {
        throw new \Exception('Not implemented');
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
