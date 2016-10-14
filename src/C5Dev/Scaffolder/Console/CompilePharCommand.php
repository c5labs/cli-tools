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
use Symfony\Component\Process\Process;

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
        date_default_timezone_set('GMT');

        $path = $this->getApplication()->getAppBasePath();
        $fs = $this->getApplication()->make('files');

        // Cleanup
        $fs->delete([$path.'/scaffolder.phar', $path.'/build.json']);

        // Show the application banners.
        $output->write($this->getApplication()->getHelp()."\r\n\r\n");

        // Set build information.
        $process = new Process('git log --pretty="%H" -n1 HEAD', __DIR__);
        if ($process->run() != 0) {
            throw new \RuntimeException('Can\'t run git log. You must ensure to run compile from git repository clone and that git binary is available.');
        }

        file_put_contents($path.'/build.json', json_encode([
            'date' => (new \DateTime())->format('r'),
            'version' => $this->getApplication()->getVersion(),
            'build' => trim($process->getOutput()),
        ]));

        $output->writeln("Standby, creating PHAR...\r\n");
        $phar = new Phar('scaffolder.phar', 0, 'scaffolder.phar');
        $phar->startBuffering();
        $phar->buildFromDirectory($path);
        $default_stub = $phar->createDefaultStub('bootstraps/start.php');
        $stub = "#!/usr/bin/env php\n".$default_stub;
        $phar->setStub($stub);
        $phar->stopBuffering();

        @chmod($path.'/scaffolder.phar', 0755);

        $fs->delete($path.'/build.json');

        $output->writeln(sprintf('<fg=green>Done! PHAR created at %s', $path.DIRECTORY_SEPARATOR.'scaffolder.phar</>'));
    }
}
