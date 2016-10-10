<?php

/*
 * This file is part of Scaffolder.
 *
 * (c) Oliver Green <oliver@c5dev.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace C5Dev\Scaffolder\Commands;

use Illuminate\Contracts\Console\Application;
use C5Dev\Scaffolder\FileExporter\FileExporter;
use Illuminate\Support\Str;

class CreatePackageCommand extends AbstractBusCommand
{
    /**
     * Handle the command.
     * 
     * @param  Application $app      
     * @param  FileExporter $exporter 
     * @return bool                                        
     */
    public function handle(Application $app, FileExporter $exporter)
    {
        $substitutions = [
            'authorName' => ['Oliver Green', $this->author['name']],
            'authorEmail' => ['oliver@c5dev.com', $this->author['email']],
            'name' => [
                '$pkgName = \'Package Boilerplate\'',
                '$pkgName = \''.$this->name.'\'',
            ],
            'description' => [
                '$pkgDescription = \'Start building standards complient concrete5 pacakges from me.\'',
                '$pkgDescription = \''.$this->description.'\'',
            ],
            'handle' => [
                '$pkgHandle = \'package-boilerplate\'',
                '$pkgHandle = \''.$this->handle.'\'',
            ],
            'namespace' => [
                'Concrete\\Package\\PackageBoilerplate',
                'Concrete\\Package\\'.Str::studly($this->handle),
            ],
            'otherNameInstances' => ['Package Boilerplate', $this->name],
            'otherDescriptionInstances' => [
                'Start building standards complient concrete5 packages from me.',
                $this->description,
            ],
            'otherHandleInstances' => ['package-boilerplate', $this->handle]
        ];

        // Add any substitutions from the options array
        if (! empty($this->options['substitutions'])) {
            $substitutions = array_merge($substitutions, $this->options['substitutions']);
        }

        // Remove uneeded composer lines from the contoller file.
        if (! isset($this->options['uses_composer']) || false === $this->options['uses_composer']) {
            $exporter->setExclusions([
                'controller.php' => [[115, 15], [151, 3]],
            ]);
        }

        // Remove uneeded service provider related files & lines from the controller file.
        if (! isset($this->options['uses_service_providers']) || false === $this->options['uses_service_providers']) {
            $exporter->setExclusion('src/Helpers/DemoHelper.php', true);
            $exporter->setExclusion('src/Providers/DemoHelperServiceProvider.php', true);
            $exporter->setExclusion('controller.php', [
                [105, 10],
                [130, 14],
                [154, 3],
            ]);
        }

        // Export the files
        $source = $this->makePath([$app->make('base_path'), 'packages', 'package-boilerplate']);
        $exporter->setSubstitutions($substitutions);
        $exporter->export($source, $this->path);

        return true;
    }
}
