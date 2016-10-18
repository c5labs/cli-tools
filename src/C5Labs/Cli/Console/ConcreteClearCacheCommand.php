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
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConcreteClearCacheCommand extends ConcreteCoreCommand
{
    /**
     * Configure the command.
     *
     * @return void
     */
    protected function configure()
    {
        $this
        ->setName('concrete:clear-cache')
        ->setDescription('Clears the cache of the loaded concrete5 core.')
        ->setHelp('Clears the cache of the loaded concrete5 core.');
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

        $cache_path = realpath($this->getApplication()->getConcretePath().'/../application/files/cache');

        $output->writeln(sprintf("Removing cache files at <fg=green>%s</>\r\n", $cache_path));

        // Build a manifest
        $fs = $this->getApplication()->make('files');
        $manifest = $fs->allFiles($cache_path);

        $progress = new ProgressBar($output, count($manifest));
        $progress->start();

        foreach ($manifest as $file) {
            unlink($file->getRealPath());
            $progress->advance();
        }

        $progress->finish();

        $output->writeln("\r\n\r\n<fg=green>Cache removed.</>");
    }
}
