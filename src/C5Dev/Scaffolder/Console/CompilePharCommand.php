<?php

/*
 * This file is part of Scaffolder.
 *
 * (c) Oliver Green <oliver@c5dev.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace C5Dev\Scaffolder\Console;

use Phar;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CompilePharCommand extends Command
{
    /**
     * Configure the command.
     *
     * @return void
     */
    protected function configure()
    {
        $this
        ->setName('pharize')
        ->setDescription('Compliles the application into a PHAR archive.')
        ->setHelp('Compliles the application into a PHAR archive.');
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
        $path = $this->getApplication()->getAppBasePath();
        $fs = $this->getApplication()->make('files');

        // Cleanup
        $fs->delete([$path.'/scaffolder.phar']);

        // Show the application banners.
        $output->write($this->getApplication()->getHelp()."\n");

        $output->writeln('Standby, creating PHAR...');
        $phar = new Phar('scaffolder.phar', 0, 'scaffolder.phar');
        $phar->startBuffering();
        $phar->buildFromDirectory($path);
        $default_stub = $phar->createDefaultStub();
        $stub = "#!/usr/bin/env php\n".$default_stub;
        $phar->setStub($stub);
        $phar->stopBuffering();

        @chmod($path.'/scaffolder.phar', 0755);

        $output->writeln(sprintf('<fg=green>Done! PHAR created at %s', $path.DIRECTORY_SEPARATOR.'scaffolder.phar</>'));
    }
}
