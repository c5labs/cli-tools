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

class CreateThemeCommand extends AbstractBusCommand
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
        $theme_namespace = 'Concrete\\Theme\\'.Str::studly($this->handle);

        // Create a package for the theme if requested
        if (true === $this->options['package_object']) {
            $package_handle = $this->handle.'_package';
            $package_namespace = 'Concrete\\Package\\'.Str::studly($package_handle);

            $options = [
                'substitutions' => [
                    'controller.php' => [
                        [
                            "\$pkg = parent::install();\n",
                            "\$pkg = parent::install();\n\n        \$theme = Theme::add('".$this->handle."', \$pkg);\n",
                        ],
                        [
                            $package_namespace.";\n",
                            $package_namespace.";\n\nuse Concrete\Core\Page\Theme\Theme;",
                        ],
                    ],
                ],
            ];

            $package_handle = $this->createPackage($app, $package_handle, $options);
            $this->path = $this->makePath([$this->path, 'themes', $this->handle]);
            $theme_namespace = 'Concrete\\Package\\'.Str::studly($package_handle).'\\Theme\\'.Str::studly($this->handle);
        }

        $substitutions = [
            'authorName' => ['Oliver Green', $this->author['name']],
            'authorEmail' => ['oliver@c5dev.com', $this->author['email']],
            'name' => [
                '$pThemeName = \'Theme Boilerplate\'',
                '$pThemeName = \''.$this->name.'\'',
            ],
            'description' => [
                '$pThemeDescription = \'A theme boilerplate to start building from.\'',
                '$pThemeDescription = \''.$this->description.'\'',
            ],
            'handle' => [
                '$pThemeHandle = \'theme-boilerplate\'',
                '$pThemeHandle = \''.$this->handle.'\'',
            ],
            'namespace' => [
                'Concrete\\Theme\\ThemeBoilerplate',
                $theme_namespace,
            ],
            'otherHandleInstances' => ['theme-boilerplate', $this->handle],
            'otherNameInstances' => ['Theme Boilerplate', $this->name],
            'otherDescriptionInstances' => [
                'A theme boilerplate to start building from.',
                $this->description,
            ],
        ];

        // Export the files
        $source = $this->makePath([$app->getAppBasePath(), 'application', 'themes', 'theme-boilerplate']);
        $exporter->setSubstitutions($substitutions);
        $exporter->export($source, $this->path);

        return true;
    }
}
