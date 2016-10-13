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

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Phar;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class ConcreteConfigurationCommand extends Command
{
    /**
     * Configure the command.
     *
     * @return void
     */
    protected function configure()
    {
        $this
        ->setName('concrete:config')
        ->setDescription('Shows configuration of the loaded concrete5 core.')
        ->setHelp('Shows configuration of the loaded concrete5 core.');
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

        $data = Arr::dot($this->getApplication()->getConcreteConfig());

        // Format the data for the Table helper.
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = [$key, Str::limit($value, 70)];
            } else {
                unset($data[$key]);
            }
        }

        // Render the table.
        $table = new Table($output);
        $table
            ->setHeaders(array('Key', 'Value'))
            ->setRows($data)
        ;
        $table->render();
    }
}
