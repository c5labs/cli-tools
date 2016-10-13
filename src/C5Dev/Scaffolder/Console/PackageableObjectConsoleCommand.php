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

use Symfony\Component\Console\Helper\QuestionHelper as Helper;
use Symfony\Component\Console\Input\InputInterface as In;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface as Out;
use Symfony\Component\Console\Question\ConfirmationQuestion;

abstract class PackageableObjectConsoleCommand extends AbstractConsoleCommand
{
    /**
     * Adds a default set of arguments & options to the command.
     *
     * @return void
     */
    protected function addDefaultArguments()
    {
        return parent::addDefaultArguments()
            ->addOption('package', null, InputOption::VALUE_OPTIONAL, 'Package the theme?', false);
    }

    /**
     * Asks whether to package the current object we are creating (for themes, block types, etc).
     *
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * 
     * @return array
     */
    protected function askWhetherToPackageObject(In $input, Out $output)
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
            $this->parameters['options']['package_object'] = $this->getHelper('question')->ask($input, $output, $question);
        } else {
            $this->parameters['options']['package_object'] = true;
        }
    }

    protected function generateDestinationPath()
    {
        /*
         * Set the destrination path.
         */
        if (true === $this->parameters['options']['package_object']) {
            if ('concrete' === $this->getApplication()->getWorkingDirectoryType()) {
                return $this->getApplication()->getObjectInstallPath('package').DIRECTORY_SEPARATOR.$this->parameters['handle'].'_package';
            } else {
                return $this->destination_path.DIRECTORY_SEPARATOR.$this->parameters['handle'].'_package';
            }
        }

        return $this->destination_path.DIRECTORY_SEPARATOR.$this->parameters['handle'];
    }

    /**
     * Custom questions for Package creation.
     *
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * 
     * @return array
     */
    protected function askQuestions(In $input, Out $output)
    {
        parent::askQuestions($input, $output);

        if (in_array($this->getApplication()->getWorkingDirectoryType(), ['generic', 'concrete'])) {
            $this->askWhetherToPackageObject($input, $output);
        } else {
            $this->parameters['options']['package_object'] = false;
        }
    }
}
