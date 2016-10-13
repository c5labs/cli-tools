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

use Illuminate\Contracts\Bus\Dispatcher;
use Illuminate\Filesystem\Filesystem;
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
     * Question helper instance.
     * 
     * @var QuestionHelper
     */
    protected $helper;

    /**
     * Bus instance.
     * 
     * @var \Illuminate\Contracts\Bus\Dispatcher
     */
    protected $bus;

    /**
     * File system instance.
     * 
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $files;

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
     * Parameters to be collected and passed to 
     * the create object command.
     * 
     * @var array
     */
    protected $parameters = ['options' => []];

    /**
     * Constructor
     * 
     * @param string     $name  
     * @param \Illuminate\Filesystem\Filesystem $files 
     * @param \Illuminate\Contracts\Bus\Dispatcher $bus   
     */
    public function __construct($name = null, Filesystem $files, Dispatcher $bus)
    {
        $this->files = $files;
        $this->bus = $bus;

        parent::__construct($name);
    }

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
        ->addOption('author-name', null, InputOption::VALUE_OPTIONAL, sprintf('%s Author', $this->getObjectName()))
        ->addOption('author-email', null, InputOption::VALUE_OPTIONAL, sprintf('%s Author', $this->getObjectName()))
        ->addOption('uses-composer', null, InputOption::VALUE_OPTIONAL, sprintf('%s user composer', $this->getObjectName()), false)
        ->addOption('uses-providers', null, InputOption::VALUE_OPTIONAL, sprintf('%s user composer', $this->getObjectName()), false);
    }

    /**
     * Generates the destination path to be used 
     * during object creation.
     * 
     * @return string
     */
    protected function generateDestinationPath()
    {
        return $this->destination_path.DIRECTORY_SEPARATOR.$this->parameters['handle'];
    }

    /**
     * The default set of questions to be asked.
     * 
     * @param  In     $input  
     * @param  Out    $output 
     * @return void 
     */
    protected function askQuestions(In $input, Out $output)
    {
        /*
         * Package Handle
         */
        if (! $this->parameters['handle'] = $input->getOption('handle')) {
            $text = sprintf(
                'Please enter the handle of the %s [<comment>my_%s_name</comment>]:',
                $this->getLowerCaseObjectName(), $this->getSnakeCaseObjectName()
            );
            $question = new Question($text, sprintf('my_%s_name', $this->getSnakeCaseObjectName()));
            $this->parameters['handle'] = $this->getHelper('question')->ask($input, $output, $question);
        }

        // Check the package handle conforms
        if (preg_replace('/[a-z_-]/i', '', $this->parameters['handle'])) {
            throw new \RuntimeException(
                'The handle must only contain letters, underscores and hypens.'
            );
        }

        /*
         * Package Name
         */
        if (! $this->parameters['name'] = $input->getOption('name')) {
            $text = sprintf(
                'Please enter the name of the %s [<comment>My %s Name</comment>]:',
                $this->getLowerCaseObjectName(), $this->getObjectName()
            );
            $question = new Question($text, sprintf('My %s Name', $this->getObjectName()));
            $this->parameters['name'] = $this->getHelper('question')->ask($input, $output, $question);
        }

        /*
         * Package Description
         */
        if (! $this->parameters['description'] = $input->getOption('description')) {
            $text = sprintf(
                'Please enter a description for the %s [<comment>A scaffolded %s.</comment>]:',
                $this->getLowerCaseObjectName(), $this->getLowerCaseObjectName()
            );
            $question = new Question($text, sprintf('A scaffolded %s.', $this->getLowerCaseObjectName()));
            $this->parameters['description'] = $this->getHelper('question')->ask($input, $output, $question);
        }

        /*
         * Package Author Name
         */
        if (! $this->parameters['author']['name'] = $input->getOption('author-name')) {
            $text = sprintf(
                'Please enter the author of the %s [<comment>Unknown</comment>]:',
                $this->getLowerCaseObjectName()
            );
            $question = new Question($text, 'Unknown');
            $this->parameters['author']['name'] = $this->getHelper('question')->ask($input, $output, $question);
        }

        /*
         * Package Author Email
         */
        if (! $this->parameters['author']['email'] = $input->getOption('author-email')) {
            $text = sprintf(
                'Please enter the authors email of the %s [<comment>unknown@unknown.net</comment>]:',
                $this->getLowerCaseObjectName()
            );
            $question = new Question($text, 'unknown@unknown.net');
            $this->parameters['author']['email'] = $this->getHelper('question')->ask($input, $output, $question);
        }
    }

    /**
     * Dispatches the create object command to the bus.
     * 
     * @return boolean
     */
    protected function dispatchCreationCommand()
    {
        /*
         * Dispatch the package creation command & send the result to the console.
         */
        $this->bus->dispatch(new $this->command_name(
            $this->destination_path, $this->parameters['handle'], $this->parameters['name'],
            $this->parameters['description'], $this->parameters['author'], $this->parameters['options']
        ));

        return file_exists($this->destination_path);
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

        // Set the destination path
        if (($path = $input->getArgument('path')) && ! empty($path)) {
            $this->destination_path = realpath($path);
            $this->getApplication()->setWorkingDirectoryType('generic');
        } else {
            $this->destination_path = $this->getApplication()->getObjectInstallPath(
                $this->getSnakeCaseObjectName()
            );
        }

        if (! is_writable($this->destination_path)) {
            throw new InvalidArgumentException("The path [$this->destination_path] is not writable.");
        }

        // Ask the questions.
        $this->askQuestions($input, $output);

        // Form the path
        $this->destination_path = $this->generateDestinationPath();

        /*
         * Confirm destination overwrite
         */
        if (file_exists($this->destination_path)) {
            $question = new ConfirmationQuestion(
                "The directory [$this->destination_path] already exists, can we remove it to continue? [Y/N]:",
                false
            );

            if ($this->getHelper('question')->ask($input, $output, $question)) {
                $this->files->deleteDirectory(realpath($this->destination_path));
            } else {
                throw new \Exception('Cannot continue as output directory already exsits.');
            }
        }

        if ($this->dispatchCreationCommand()) {
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
