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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface as In;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface as Out;
use Symfony\Component\Console\Question\ConfirmationQuestion;

abstract class ConcreteCoreCommand extends Command
{
    protected function execute(In $input, Out $output)
    {
        // Show the application banners.
        $output->write($this->getApplication()->getHelp());

        // List concrete5 installation location & version
        if ($concrete_path = $this->getApplication()->getConcretePath()) {
            $config = $this->getApplication()->getConcreteConfig();

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
