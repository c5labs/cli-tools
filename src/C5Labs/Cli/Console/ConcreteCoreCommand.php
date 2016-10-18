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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as In;
use Symfony\Component\Console\Output\OutputInterface as Out;

abstract class ConcreteCoreCommand extends Command
{
    /**
     * Get the instance of the current CLI app.
     * 
     * We use this accessor to maintain access to the correct 
     * application instance while in the Psy shell.
     * 
     * @return Application
     */
    public function getCliApplication()
    {
        $app = parent::getApplication();

        if (! ($app instanceof \C5Labs\Cli\Application)) {
            $app = $app->getScopeVariable('app');
        }

        return $app;
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
    protected function execute(In $input, Out $output)
    {
        $app = $this->getCliApplication();

        // Show the application banners.
        $output->write($app->getHelp()."\r\n");

        // List concrete5 installation location & version
        if ($concrete_path = $app->getConcretePath()) {
            $config = $app->getConcreteConfig();

            $output->writeln(
                sprintf(
                    "\r\nUsing concrete5 [<fg=green>%s</>] core files at: <fg=green>%s</>\r\n",
                    isset($config['version']) ? $config['version'] : 'Unknown Version',
                    $concrete_path
                )
            );
        }

        if (! file_exists($concrete_path)) {
            throw new \Exception('An instance of the concrete5 core could not be found.');
        }
    }
}
