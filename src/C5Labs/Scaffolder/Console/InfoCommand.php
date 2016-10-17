<?php

/*
 * This file is part of Scaffolder.
 *
 * (c) Oliver Green <oliver@c5labs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace C5Labs\Scaffolder\Console;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
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
        parent::execute($input, $output);

        $app = $this->getApplication();

        // Paths
        $output->writeln('<fg=yellow>Paths</>');
        $output->writeln('<fg=yellow>--------------------</>');
        $output->writeln('Application Path: '.$app->getAppBasePath());
        $output->writeln('Composer Application Base Path: '.$app->getComposerAppBasePath());
        $output->writeln('Current Working Directory: '.$app->getCurrentWorkingDirectory());
        $output->writeln('Current Working Type: '.$app->getWorkingDirectoryType());

        // Build
        $output->writeln("\r\n<fg=yellow>Build</>");
        $output->writeln('<fg=yellow>--------------------</>');
        $output->writeln('Date: '.$app->getBuildDate());
        $output->writeln('Version: '.$app->getVersion());
        $output->writeln('Commit: '.$app->getLongbuild());

        // Concrete5 Path
        $config = $this->getApplication()->getConcreteConfig();
        $output->writeln("\r\n<fg=yellow>concrete5 Core</>");
        $output->writeln('<fg=yellow>--------------------</>');
        $output->writeln('Auto Discovered Path: '.$app->getConcretePath());
        $output->writeln('Site Name: '.isset($config['site']) ? $config['site'] : 'Unknown');
        $output->writeln('Version: '.$config['version']);

        $output->writeln("\r\n<fg=green>Command complete.</>\r\n");
    }
}
