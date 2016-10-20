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
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class InfoCommand extends ConcreteCoreCommand
{
    /**
     * Configure the command.
     *
     * @return void
     */
    protected function configure()
    {
        $this
        ->setName('info')
        ->setDescription('Shows info about the applications configuration, build, etc.')
        ->setHelp('Shows info about the applications configuration, build, etc.');
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
        $app = $this->getCliApplication();

        // Show the application banners.
        $output->write($app->getHelp()."\r\n");

        // Paths
        $this->outputTitle($output, 'Paths');
        $output->writeln('Application Path: '.$app->getAppBasePath());
        $output->writeln('Composer Application Base Path: '.$app->getComposerAppBasePath());
        $output->writeln('Current Working Directory: '.$app->getCurrentWorkingDirectory());
        $output->writeln('Current Working Type: '.$app->getWorkingDirectoryType());

        // Build
        $this->outputTitle($output, 'Build');
        $output->writeln('Date: '.$app->getBuildDate());
        $output->writeln('Version: '.$app->getVersion());
        $output->writeln('Commit: '.$app->getLongbuild());

        // Concrete5
        $path = $app->getConcretePath();
        $config = $app->getConcreteConfig('concrete');
        $this->outputTitle($output, 'concrete5 Core');
        $output->writeln('Auto Discovered Path: '.(empty($path) ? 'Not found' : $path));

        if (! empty($path)) {
            $output->writeln('Site Name: '.(isset($config['site']) ? $config['site'] : 'Unknown'));
            $output->writeln('Version: '.(isset($config['version']) ? $config['version'] : 'Unknown'));
        }

        $output->writeln("\r\n<fg=green>Command complete.</>\r\n");
    }
}
