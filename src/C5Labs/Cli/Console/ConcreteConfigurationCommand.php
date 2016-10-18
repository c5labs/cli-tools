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

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ConcreteConfigurationCommand extends ConcreteCoreCommand
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
        parent::execute($input, $output);

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
            ->setHeaders(['Key', 'Value'])
            ->setRows($data);
        $table->render();

        $output->writeln("\r\n<fg=green>Command complete.</>\r\n");
    }
}
