<?php

/*
 * This file is part of Cli.
 *
 * (c) Oliver Green <oliver@c5labs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace C5Labs\Cli\Commands;

use C5Labs\Cli\FileExporter\FileExporter;
use Illuminate\Contracts\Console\Application;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Contracts\Bus\SelfHandling;

class CreateBlockTypeTemplateCommand implements SelfHandling
{
    public function __construct($path, $block_type, $block_type_path, $name)
    {
        $this->path = $path;

        $this->block_type = $block_type;

        $this->block_type_path = $block_type_path;

        $this->name = $name;
    }

    /**
     * Handle the command.
     * 
     * @param  Application $app      
     * @param  FileExporter $exporter 
     * @return bool                                        
     */
    public function handle(Application $app, Filesystem $files)
    {
        $templates_directory = realpath($this->path.'/../');
        $template_directory = $this->path;
        $template_file_name = $templates_directory.'/'.$this->name.'.php';
        $source_template_name = $this->block_type_path.'/view.php';
        $destination_template_name = $template_directory.'/view.php';

        // Check the source template exists.
        if (! $files->exists($source_template_name)) {
            throw new \Exception(sprintf('The source template [%s] does not exist.', $source_template_name));
        }

        // Check that there aren't existing templates.
        if ($files->exists($template_directory) || $files->exists($template_file_name)) {
            throw new \Exception(sprintf('The template already exists in [%s].', $templates_directory));
        }

        // Make the templates directory.
        if (! $files->exists($template_directory)) {
            $files->makeDirectory($template_directory, 0755, true, true);
        }

        // Copy the view.php from the original block.
        $files->copy($source_template_name, $destination_template_name);
    }
}
