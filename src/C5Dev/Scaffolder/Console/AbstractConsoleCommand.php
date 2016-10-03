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

use Illuminate\Support\Str;
use InvalidArgumentException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper as Helper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface as In;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface as Out;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class AbstractConsoleCommand extends Command
{
    /**
     * The name of the object that we are scaffolding.
     *
     * @var string
     */
    protected $object_name;

    /**
     * The name of the command to dispatch to create the object.
     * 
     * @var string
     */
    protected $command_name;

    /**
     * The destination path for files created.
     * 
     * @var string
     */
    protected $destination_path;

    /**
     * Indicates whether we are operating within a concrete5 installation.
     * @var bool
     */
    protected $at_concrete_root = false;

    /**
     * Get the object name.
     *
     * @return string
     */
    protected function getObjectName()
    {
        return $this->object_name;
    }

    /**
     * Get a lower case representation of the object name.
     *
     * @return string
     */
    protected function getLowerCaseObjectName()
    {
        return strtolower($this->object_name);
    }

    /**
     * Get a snake_cased representation of the object name.
     * 
     * @return string
     */
    protected function getSnakeCaseObjectName()
    {
        return Str::snake($this->getObjectName());
    }

    /**
     * Gets the name of the command to dispatch to generate the object.
     * 
     * @return string
     */
    protected function getCommandName()
    {
        return $this->command_name;
    }

    /**
     * Adds a default set of arguments & options to the command.
     *
     * @return void
     */
    protected function addDefaultArguments()
    {
        return $this
        ->addArgument('path', InputArgument::OPTIONAL, sprintf('The path to create the %s at.', $this->getLowerCaseObjectName()))
        ->addOption('handle', null, InputOption::VALUE_OPTIONAL, sprintf('%s Handle', $this->getObjectName()))
        ->addOption('name', null, InputOption::VALUE_OPTIONAL, sprintf('%s Name', $this->getObjectName()))
        ->addOption('description', null, InputOption::VALUE_OPTIONAL, sprintf('%s Description', $this->getObjectName()))
        ->addOption('author', null, InputOption::VALUE_OPTIONAL, sprintf('%s Author', $this->getObjectName()))
        ->addOption('uses-composer', null, InputOption::VALUE_OPTIONAL, sprintf('%s user composer', $this->getObjectName()), false)
        ->addOption('uses-providers', null, InputOption::VALUE_OPTIONAL, sprintf('%s user composer', $this->getObjectName()), false);
    }

    /**
     * Allows the implementation of custom questions for the object type we are creating.
     *
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @param  QuestionHelper  $helper
     * @param  array           $vars
     * @return array
     */
    protected function askCustomQuestions(In $input, Out $output, Helper $helper, array $vars)
    {
        return $vars;
    }

    /**
     * Asks whether to package the current object we are creating (for themes, block types, etc).
     *
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @param  QuestionHelper  $helper
     * @param  array           $vars
     * @return array
     */
    protected function askWhetherToPackageObject(In $input, Out $output, Helper $helper, array $vars)
    {
        global $argv;

        /*
         * Should we package the object?
         */
        if (! in_array('--package', $argv)) {
            $question = new ConfirmationQuestion(
                sprintf('Do you want to package the %s? [Y/N]:', $this->getLowerCaseObjectName()),
                false
            );
            $vars['options']['package_object'] = $helper->ask($input, $output, $question);
        } else {
            $vars['options']['package_object'] = true;
        }

        /*
         * Confirm destination overwrite
         */
        if (true === $vars['options']['package_object']) {
            if (! $this->at_concrete_root) {
                $path = $this->destination_path = $this->destination_path.'_package';
            } else {
                $package_install_path = $this->getApplication()->getDefaultInstallPath('package');
                $path = $this->destination_path = $this->getApplication()->make('export_path').DIRECTORY_SEPARATOR.$package_install_path.DIRECTORY_SEPARATOR.$vars['handle'].'_package';
            }

            if (file_exists($path)) {
                $question = new ConfirmationQuestion(
                    "The directory [$path] already exists, can we remove it to continue? [Y/N]:",
                    false
                );

                if ($helper->ask($input, $output, $question)) {
                    $files = $this->getApplication()->make('files');
                    $files->deleteDirectory(realpath($path));
                } else {
                    throw new \Exception('Cannot continue as output directory already exsits.');
                }
            }
        }

        return $vars;
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
    protected function execute(In $input, Out $output)
    {
        // Show the application banners.
        $output->write($this->getApplication()->getHelp()."\n");

        // Pipework
        $options = [];
        $helper = $this->getHelper('question');
        $app = $this->getApplication();
        $bus = $app->make(\Illuminate\Contracts\Bus\Dispatcher::class);

        // Set the destination path
        if (($path = $input->getArgument('path')) && ! empty($path)) {
            $this->destination_path = realpath($path);
        } else {
            $destination_path = $this->getApplication()->make('export_path');

            // Determine whether we're in a concrete installation or not, if we are we can 
            // put the object into the right location.
            $default_install_path = $app->getDefaultInstallPath($this->getSnakeCaseObjectName());

            $this->at_concrete_root = (is_dir($destination_path.'/concrete') || is_dir($destination_path.'/vendor/concrete5/concrete5'));

            if ($this->at_concrete_root && ! empty($default_install_path)) {
                $this->at_concrete_root = true;
                $this->destination_path = $destination_path.DIRECTORY_SEPARATOR.$default_install_path;
            } else {
                $this->destination_path = realpath($destination_path);
            }
        }

        if (! is_writable($this->destination_path)) {
            throw new InvalidArgumentException("The path [$this->destination_path] is not writable.");
        }

        /*
         * Package Handle
         */
        if (! $handle = $input->getOption('handle')) {
            $text = sprintf(
                'Please enter the handle of the %s [<comment>my_%s_name</comment>]:',
                $this->getLowerCaseObjectName(), $this->getSnakeCaseObjectName()
            );
            $question = new Question($text, sprintf('my_%s_name', $this->getSnakeCaseObjectName()));
            $handle = $helper->ask($input, $output, $question);
        }

        // Check the package handle conforms
        if (preg_replace('/[a-z_-]/i', '', $handle)) {
            throw new \RuntimeException(
                'The handle must only contain letters, underscores and hypens.'
            );
        }

        // Form the package path
        $this->destination_path = $this->destination_path.DIRECTORY_SEPARATOR.$handle;

        /*
         * Confirm destination overwrite
         */
        if (file_exists($this->destination_path)) {
            $question = new ConfirmationQuestion(
                "The directory [$this->destination_path] already exists, can we remove it to continue? [Y/N]:",
                false
            );

            if ($helper->ask($input, $output, $question)) {
                $app->make('files')->deleteDirectory(realpath($this->destination_path));
            } else {
                throw new \Exception('Cannot continue as output directory already exsits.');
            }
        }

        /*
         * Package Name
         */
        if (! $name = $input->getOption('name')) {
            $text = sprintf(
                'Please enter the name of the %s [<comment>My %s Name</comment>]:',
                $this->getLowerCaseObjectName(), $this->getObjectName()
            );
            $question = new Question($text, sprintf('My %s Name', $this->getObjectName()));
            $name = $helper->ask($input, $output, $question);
        }

        /*
         * Package Description
         */
        if (! $description = $input->getOption('description')) {
            $text = sprintf(
                'Please enter a description for the %s [<comment>A scaffolded %s.</comment>]:',
                $this->getLowerCaseObjectName(), $this->getLowerCaseObjectName()
            );
            $question = new Question($text, sprintf('A scaffolded %s.', $this->getLowerCaseObjectName()));
            $description = $helper->ask($input, $output, $question);
        }

        /*
         * Package Author
         */
        if (! $author = $input->getOption('author')) {
            $text = sprintf(
                'Please enter the author of the %s [<comment>Unknown <unknown@unknown.com></comment>]:',
                $this->getLowerCaseObjectName()
            );
            $question = new Question($text, 'Unknown <unknown@unknown.com>');
            $author = $helper->ask($input, $output, $question);
        }

        /*
         * Run any custom creation logic.
         */
        $vars = compact('handle', 'name', 'description', 'author', 'options');
        $vars = $this->askCustomQuestions($input, $output, $helper, $vars);

        /*
         * Dispatch the package creation command & send the result to the console.
         */
        $bus->dispatch(new $this->command_name(
            $this->destination_path, $vars['handle'], $vars['name'],
            $vars['description'], $vars['author'], $vars['options']
        ));

        if (file_exists($this->destination_path)) {
            $path = realpath($this->destination_path);
            $output->writeln(sprintf(
                "\n<fg=green>The %s was created at %s.</>\n",
                $this->getLowerCaseObjectName(), $this->destination_path
            ));
        } else {
            $output->writeln(sprintf(
                "\n<fg=red>The %s could not be created at %s.</>\n",
                $this->getLowerCaseObjectName(),
                $this->destination_path
            ));
        }
    }
}
