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

use C5Labs\Cli\Commands\CreateBlockTypeTemplateCommand;
use Illuminate\Support\Str;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface as In;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface as Out;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

class MakeBlockTypeTemplateCommand extends AbstractConsoleCommand
{
    /**
     * The name of the object that we are scaffolding.
     *
     * @var string
     */
    protected $object_name = 'Block Type Template';

    /**
     * The name of the command to dispatch to create the object.
     * 
     * @var string
     */
    protected $command_name = CreateBlockTypeTemplateCommand::class;

    /**
     * Configure the command.
     *
     * @return void
     */
    protected function configure()
    {
        $this
        ->setName('make:block-template')
        ->setDescription('Generates boilerplate block type template code.')
        ->setHelp('This command allows you to create block type templates.')
        ->addArgument('path', InputArgument::OPTIONAL, sprintf('The path to create the %s at.', $this->getLowerCaseObjectName()))
        ->addOption('name', null, InputOption::VALUE_OPTIONAL, sprintf('%s Name', $this->getObjectName()))
        ->addOption('block-type', null, InputOption::VALUE_OPTIONAL, 'Block type to create a template for.');
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
        $blocks = [];

        // List concrete5 installation location & version
        if ($concrete_path = $this->getApplication()->getConcretePath()) {
            $config = $this->getApplication()->getConcreteConfig();

            $output->writeln(
                sprintf(
                    "Using concrete5 [%s] core files at: %s\r\n",
                    isset($config['version']) ? $config['version'] : 'Unknown Version',
                    $concrete_path
                )
            );
        }

        // Only allow block templating if we have a valid concrete core location.
        if (empty($this->getApplication()->getConcretePath())) {
            throw new \Exception(
                'To create block templates you must run this command within a concrete5 installation directory.'
            );
        }

        // Generate the list of blocks
        $root = $this->getApplication()->getConcretePath();

        // Default block paths
        $paths = [
            realpath($root.'/../application/blocks'),
            realpath($root.'/../concrete/blocks'),
        ];

        // Package blocks
        $packages = $this->files->directories(realpath($root.'/../packages'));
        foreach ($packages as $package_path) {
            if ($this->files->exists($package_path.'/blocks')) {
                $paths[] = $package_path.'/blocks';
            }
        }

        // Get all of the block names.
        foreach ($paths as $block_path) {
            $block_paths = $this->files->directories($block_path);
            foreach ($block_paths as $block) {
                $parts = explode(DIRECTORY_SEPARATOR, $block);
                $name = array_pop($parts);

                if ('core_' !== substr($name, 0, 5) && 'dashboard_' !== substr($name, 0, 10)) {
                    $blocks[$block] = $name;
                }
            }
        }

        // Ensure we have only unique values
        $blocks = array_unique($blocks);

        // If we're not in a block type directory...
        if ('block_type' !== $this->getApplication()->getWorkingDirectoryType()) {
            /*
             * Template Block Name
             */
            if (! $this->parameters['block_type'] = $input->getOption('block-type')) {
                $helper = $this->getHelper('question');
                $question = new ChoiceQuestion(
                    'Please select the block type you would like to template:',
                    array_values($blocks),
                    0
                );

                $this->parameters['block_type'] = $helper->ask($input, $output, $question);
            }
        }

        // If we are in a block type directory we get the handle
        else {
            $current_block_handle = explode('/', $this->getApplication()->getCurrentWorkingDirectory());
            $this->parameters['block_type'] = array_pop($current_block_handle);
        }

        // Validate the selection.
        if (! in_array($this->parameters['block_type'], $blocks)) {
            throw new \InvalidArgumentException(
                sprintf('The block type [%s] could not be found.', $this->parameters['block_type'])
            );
        }

        // Add the block type path.
        $this->parameters['block_type_path'] = array_search($this->parameters['block_type'], $blocks);

        /*
         * Template Name
         */
        if (! $this->parameters['name'] = $input->getOption('name')) {
            $text = sprintf(
                'Please enter the name of the %s [<comment>Sidebar Template</comment>]:',
                $this->getLowerCaseObjectName(), $this->getObjectName()
            );
            $question = new Question($text, 'Sidebar Template');
            $this->parameters['name'] = $this->getHelper('question')->ask($input, $output, $question);
        }

        $this->parameters['handle'] = $this->parameters['name'] = Str::slug($this->parameters['name'], '_');
    }

    /**
     * Generates the destination path to be used 
     * during object creation.
     * 
     * @return string
     */
    protected function generateDestinationPath()
    {
        $template_path = $this->getApplication()->getDefaultInstallPath('block_type_template');

        return $this->destination_path.'/'.$this->parameters['block_type'].'/'.$template_path.'/'.$this->parameters['name'];
    }

    /**
     * Dispatches the create object command to the bus.
     * 
     * @return bool
     */
    protected function dispatchCreationCommand()
    {
        /*
         * Dispatch the package creation command & send the result to the console.
         */
        $this->bus->dispatch(new $this->command_name(
            $this->destination_path, $this->parameters['block_type'], $this->parameters['block_type_path'], $this->parameters['name']
        ));

        return file_exists($this->destination_path);
    }
}
