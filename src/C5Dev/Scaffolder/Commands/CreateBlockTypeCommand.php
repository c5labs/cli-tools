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

class CreateBlockTypeCommand extends AbstractBusCommand
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
        $block_namespace = 'Application\\Block\\'.Str::studly($this->handle);

        // Create a package for the theme if requested
        if (true === $this->options['package_object']) {
            $package_handle = $this->handle.'_package';
            $package_namespace = 'Concrete\\Package\\'.Str::studly($package_handle);

            $options = [
                'substitutions' => [
                    'controller.php' => [
                        [
                            "\$pkg = parent::install();\n",
                            "\$pkg = parent::install();\n\n        \$theme = BlockType::installBlockType('".$this->handle."', \$pkg);\n",
                        ],
                        [
                            $package_namespace.";\n",
                            $package_namespace.";\n\nuse Concrete\Core\Block\BlockType\BlockType;",
                        ],
                    ],
                ],
            ];

            $package_handle = $this->createPackage($app, $package_handle, $options);
            $this->path = $this->makePath([$this->path, 'blocks', $this->handle]);
            $block_namespace = 'Concrete\\Package\\'.Str::studly($package_handle).'\\Block\\'.Str::studly($this->handle);
        }

        $substitutions = [
            'authorName' => ['Oliver Green', $this->author['name']],
            'authorEmail' => ['oliver@c5dev.com', $this->author['email']],
            'name' => [
                '$btName = \'Block Boilerplate\'',
                '$btName = \''.$this->name.'\'',
            ],
            'description' => [
                '$btDescription = \'A block boilerplate to start building from.\'',
                '$btDescription = \''.$this->description.'\'',
            ],
            'handle' => [
                '$btHandle = \'block-boilerplate\'',
                '$btHandle = \''.$this->handle.'\'',
            ],
            'namespace' => [
                'Application\\Block\\BlockBoilerplate',
                $block_namespace,
            ],
            'otherNameInstances' => ['Block Boilerplate', $this->name],
            'otherDescriptionInstances' => [
                'Start building standards complient concrete5 blocks from me.',
                $this->description,
            ],
        ];

        // Export the files
        $source = $this->makePath([$app->getAppBasePath(), 'application', 'blocks', 'block-boilerplate']);
        $exporter->setSubstitutions($substitutions);
        $exporter->export($source, $this->path);

        return true;
    }
}
