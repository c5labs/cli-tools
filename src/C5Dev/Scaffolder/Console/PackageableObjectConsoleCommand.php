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
         * Set the destrination path.
         */
        if (true === $vars['options']['package_object']) {
            if ('concrete' !== $this->getApplication()->getWorkingDirectoryType() || ($path = $input->getArgument('path')) && ! empty($path)) {
                $this->destination_path = $this->destination_path.'_package';
            } else {
                $this->destination_path = $this->getApplication()->getObjectInstallPath('package');
                $this->destination_path .= DIRECTORY_SEPARATOR.$vars['handle'].'_package';
            }
        }

        return $vars;
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
        if (in_array($this->getApplication()->getWorkingDirectoryType(), ['generic', 'concrete'])) {
            return $this->askWhetherToPackageObject($input, $output, $helper, $vars);
        } else {
            $vars['options']['package_object'] = false;

            return $vars;
        }
    }
}
