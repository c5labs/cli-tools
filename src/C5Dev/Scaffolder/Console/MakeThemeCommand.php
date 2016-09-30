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

use C5Dev\Scaffolder\Commands\CreateThemeCommand;
use Symfony\Component\Console\Helper\QuestionHelper as Helper;
use Symfony\Component\Console\Input\InputInterface as In;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface as Out;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class MakeThemeCommand extends AbstractConsoleCommand
{
    /**
     * The name of the object that we are scaffolding.
     *
     * @var string
     */
    protected $object_name = 'Theme';

    /**
     * The name of the command to dispatch to create the object.
     * 
     * @var string
     */
    protected $command_name = CreateThemeCommand::class;

    /**
     * Configure the command.
     *
     * @return void
     */
    protected function configure()
    {
        $this
        ->setName('make:theme')
        ->setDescription('Generates boilerplate theme code.')
        ->setHelp('This command allows you to create themes.')
        ->addDefaultArguments()
        ->addOption('package-theme', null, InputOption::VALUE_OPTIONAL, 'Package the theme?', false);
    }

    /**
     * Custom questions for Package creation.
     *
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @param  QuestionHelper  $helper
     * @param  array           $vars
     * @return array
     */
    protected function askCustomQuestions(In $input, Out $output, Helper $helper, array $vars)
    {
        /*
         * Should we package the theme?
         */
        if (! $vars['options']['package_theme'] = $input->getOption('package-theme')) {
            $question = new ConfirmationQuestion(
                'Do you want to package the theme? [Y/N]:',
                false
            );
            $vars['options']['package_theme'] = $helper->ask($input, $output, $question);
        }

        /*
         * Confirm destination overwrite
         */
        if (true === $vars['options']['package_theme']) {
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
}
