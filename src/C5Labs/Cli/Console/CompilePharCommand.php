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
        $phar_path = $path.'/bin/concrete.phar';
        $build_json = $path.'/bin/build.json';

        // Cleanup
        $fs->delete([$phar_path, $build_json]);

        // Show the application banners.
        $output->write($this->getApplication()->getHelp()."\r\n\r\n");

        // Set build information.
        $process = new Process('git log --pretty="%H" -n1 HEAD', __DIR__);
        if ($process->run() != 0) {
            throw new \RuntimeException('Can\'t run git log. You must ensure to run compile from git repository clone and that git binary is available.');
        }

        $build_meta = [
            'date' => (new \DateTime())->format('r'),
            'version' => $this->getApplication()->getVersion(),
            'build' => trim($process->getOutput()),
        ];

        file_put_contents($build_json, json_encode($build_meta));

        $output->writeln("Standby, creating PHAR...\r\n");
        $phar = new Phar($phar_path, 0, 'concrete.phar');
        $phar->startBuffering();
        $phar->buildFromDirectory($path);
        $default_stub = $phar->createDefaultStub('bootstraps/start.php');
        $stub = "#!/usr/bin/env php\n".$default_stub;
        $phar->setStub($stub);
        $phar->stopBuffering();

        @chmod($phar_path, 0755);

        $output->writeln('Version: '.$build_meta['version']);
        $output->writeln('Build: '.$build_meta['build']."\r\n");

        $output->writeln(sprintf('<fg=green>Done! PHAR created at %s</>', $phar_path));
    }
}
