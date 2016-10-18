<?php

/*
 * This file is part of Cli.
 *
 * (c) Oliver Green <oliver@c5labs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace C5Labs\Cli\Console;

use Psy\Configuration;
use Psy\Shell;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ConcreteShellCommand extends ConcreteCoreCommand
{
    /**
     * artisan commands to include in the tinker shell.
     *
     * @var array
     */
    protected $commandWhitelist = [
        'concrete:config', 'concrete:clear-cache',
    ];

    /**
     * Configure the command.
     *
     * @return void
     */
    protected function configure()
    {
        $this
        ->setName('concrete:shell')
        ->setDescription('An interactive shell to tinker with your concrete5 installation.')
        ->setHelp('An interactive shell to tinker with your concrete5. installation')
        ->addOption('debug-boot', null, InputOption::VALUE_OPTIONAL, 'Enable error reporting during concrete5 boot.');
    }

    /**
     * Executes the current command.
     *
     * This method is not abstract because you can use this class
     * as a concrete class. In this case, instead of defining the
     * execute() method, you set the code to execute by passing
     * a Closure to the setCode() method.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return null|int null or 0 if everything went fine, or an error code
     *
     * @throws LogicException When this abstract method is not implemented
     *
     * @see setCode()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        // Set the base paths.
        $__DIR__ = $this->getApplication()->getConcretePath();
        define('DIR_BASE', realpath($__DIR__.'/../'));

        // Set the handler so that we can control and hide error messages.
        $old_error_handler = set_error_handler(function ($errno, $errstr, $errfile, $errline) use ($input) {
            if ($input->getOption('debug-boot')) {
                // Bubble errors up to exceptions.
                throw new \ErrorException($errstr, $errno, E_ERROR, $errfile, $errline);
            }
        });

        try {
            /**
             * ----------------------------------------------------------------------------
             * Set required constants, including directory names, attempt to include site configuration file with database
             * information, attempt to determine if we ought to skip to an updated core, etc...
             * ----------------------------------------------------------------------------.
             */
            require $__DIR__.'/bootstrap/configure.php';

            /**
             * ----------------------------------------------------------------------------
             * Include all autoloaders
             * ----------------------------------------------------------------------------.
             */
            require $__DIR__.'/bootstrap/autoload.php';

            /*
             * ----------------------------------------------------------------------------
             * Begin concrete5 startup.
             * ----------------------------------------------------------------------------
             */
            $cms = require $__DIR__.'/bootstrap/start.php';
        } catch (\Exception $ex) {
            // If we're in debug rethrow any exceptions.
            if ($input->getOption('debug-boot')) {
                throw $ex;
            }
        }

        // We can't continue without a valid  reference to the CMS.
        if (! isset($cms) || ! is_object($cms)) {
            throw new \Exception('Failed to boot concrete, please verify your installation in your browser.');
        }

        // Setup the shell.
        $config = new Configuration;
        /*$config->getPresenter()->addCasters(
            $this->getCasters()
        );*/
        $shell = new Shell($config);
        $shell->addCommands($this->getCommands());
        //$shell->setIncludes($this->argument('include'));
        $shell->run();
    }

    /**
     * Get artisan commands to pass through to PsySH.
     *
     * @return array
     */
    protected function getCommands()
    {
        $commands = [];
        foreach ($this->getApplication()->all() as $name => $command) {
            if (in_array($name, $this->commandWhitelist)) {
                $commands[] = $command;
            }
        }

        return $commands;
    }
}
