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

use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class MakePackageCommand extends Command
{
    /**
     * Configure the command.
     * 
     * @return void
     */
    protected function configure()
    {
        $this
        ->setName('make:package')
        ->setDescription('Creates a new package.')
        ->setHelp('This command allows you to create upackages.')
        ->addArgument('path', InputArgument::REQUIRED, 'The path to create the package at.')
        ->addOption('handle', null, InputOption::VALUE_OPTIONAL, 'Package Handle')
        ->addOption('name', null, InputOption::VALUE_OPTIONAL, 'Package Name')
        ->addOption('description', null, InputOption::VALUE_OPTIONAL, 'Package Description')
        ->addOption('author', null, InputOption::VALUE_OPTIONAL, 'Package Author')
        ->addOption('uses_composer', null, InputOption::VALUE_OPTIONAL, 'Package user composer', false)
        ->addOption('uses_providers', null, InputOption::VALUE_OPTIONAL, 'Package user composer', false);
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
        $package_options = [];
        $destination_path = realpath($input->getArgument('path'));
        $helper = $this->getHelper('question');
        $bus = $this->getApplication()->make(\Illuminate\Contracts\Bus\Dispatcher::class);
        $files = $this->getApplication()->make('files');

        if (! is_writable($destination_path)) {
            throw new InvalidArgumentException("The path [$destination_path] is not writable.");
        }

        /*
         * Package Handle
         */
        if (! $package_handle = $input->getOption('handle')) {
            $question = new Question('Please enter the handle of the package [<comment>my_package_name</comment>]:', 'my_package_name');
            $package_handle = $helper->ask($input, $output, $question);
        }

        // Check the package handle conforms
        if (preg_replace('/[a-z_-]/i', '', $package_handle)) {
            throw new \RuntimeException(
                'The package handle must only contain letters, underscores and hypens.'
            );
        }

        // Form the package path
        $package_path = $destination_path.DIRECTORY_SEPARATOR.$package_handle;

        /*
         * Confirm destination overwrite
         */
        if (file_exists($package_path)) {
            $question = new ConfirmationQuestion("The package directory [$package_path] already exists, can we remove it to continue? [Y/N]:", false);
            if ($helper->ask($input, $output, $question)) {
                $files->deleteDirectory(realpath($package_path));
            } else {
                throw new \Exception('Cannot continue as output directory already exsits.');
            }
        }

        /*
         * Package Name
         */
        if (! $package_name = $input->getOption('name')) {
            $question = new Question('Please enter the name of the package [<comment>My Package Name</comment>]:', 'My Package Name');
            $package_name = $helper->ask($input, $output, $question);
        }

        /*
         * Package Description
         */
        if (! $package_description = $input->getOption('description')) {
            $question = new Question('Please enter a description for the package [<comment>A scaffolded package.</comment>]:', 'A scaffolded package.');
            $package_description = $helper->ask($input, $output, $question);
        }

        /*
         * Package Author
         */
        if (! $package_author = $input->getOption('author')) {
            $question = new Question('Please enter the author of the package [<comment>Unknown <unknown@unknown.com></comment>]:', 'Unknown <unknown@unknown.com>');
            $package_author = $helper->ask($input, $output, $question);
        }

        /*
         * Package Service Providers?
         */
        if (! $package_options['uses_service_providers'] = $input->getOption('uses_providers')) {
            $question = new ConfirmationQuestion('Will this package expose any services via service providers to the core? [Y/N>]:', false);
            $package_options['uses_service_providers'] = $helper->ask($input, $output, $question);
        }

        /*
         * Composer compatibility?
         */
        if (! $package_options['uses_composer'] = $input->getOption('uses_composer')) {
            $question = new ConfirmationQuestion('Will you use composer to manage this packages dependencies? [Y/N]:', false);
            $package_options['uses_composer'] = $helper->ask($input, $output, $question);
        }

        /*
         * Dispatch the package creation command & send the result to the console. 
         */
        $bus->dispatch(new \C5Dev\Scaffolder\Commands\CreatePackageCommand(
            $package_path, $package_handle, $package_name, $package_description, $package_author, $package_options
        ));

        if (file_exists($package_path)) {
            $package_path = realpath($package_path);
            $output->writeln("<fg=green>The package was created at $package_path.</>");
        } else {
            $output->writeln("<fg=red>The package could not be created at $package_path.</>");
        }
    }
}
