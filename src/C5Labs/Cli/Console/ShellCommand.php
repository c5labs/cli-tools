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

class ShellCommand extends ConcreteCoreCommand
{
    /**
     * commands to include in the shell.
     *
     * @var array
     */
    protected $commandWhitelist = ['config', 'info', 'clear-cache'];

    /**
     * Configure the command.
     *
     * @return void
     */
    protected function configure()
    {
        $this
        ->setName('shell')
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

        $cms = $this->getApplication()
            ->bootConcreteInstance($input->getOption('debug-boot'));

        // Setup the shell.
        $config = new Configuration;
        /*$config->getPresenter()->addCasters(
            // We could cast C5 object types here.
            $this->getCasters()
        );*/
        $shell = new Shell($config);
        $shell->addCommands($this->getCommands());
        $shell->setScopeVariables(['app' => $this->getCliApplication(), 'cms' => $cms]);
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
        foreach ($this->getCliApplication()->all() as $name => $command) {
            if (in_array($name, $this->commandWhitelist)) {
                $commands[] = $command;
            }
        }

        return $commands;
    }
}
