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

use Illuminate\Support\Str;

class CreatePackageCommand
{
    /**
     * Handle.
     *
     * @var string
     */
    protected $handle;

    /**
     * Name.
     *
     * @var string
     */
    protected $name;

    /**
     * Description.
     *
     * @var string
     */
    protected $description;

    /**
     * Author.
     *
     * @var string
     */
    protected $author;

    /**
     * Options.
     *
     * uses_composer | bool
     * uses_service_providers | bool
     *
     * @var array
     */
    protected $options;

    /**
     * Package Path.
     *
     * @var string
     */
    protected $package_path;

    /**
     * Constructor.
     *
     * @param string $package_path
     * @param string $handle
     * @param string $name
     * @param string $description
     * @param string $author
     * @param array $options
     */
    public function __construct($package_path, $handle, $name, $description, $author, $options = null)
    {
        $this->handle = $handle;

        $this->name = $name;

        $this->description = $description;

        $this->author = $author;

        $this->options = $options ?: [];

        $this->package_path = $package_path;
    }

    /**
     * Handle the command.
     * 
     * @param  \C5Dev\Scaffolder\Application               $app      
     * @param  \C5Dev\Scaffolder\FileExporter\FileExporter $exporter 
     * @return bool                                        
     */
    public function handle(\C5Dev\Scaffolder\Application $app, \C5Dev\Scaffolder\FileExporter\FileExporter $exporter)
    {
        $substitutions = [
            'packageAuthor' => ['Oliver Green <oliver@c5dev.com>', $this->author],
            'packageName' => [
                '$pkgName = \'Package Boilerplate\'',
                '$pkgName = \''.$this->name.'\'',
            ],
            'packageDescription' => [
                '$pkgDescription = \'Start building standards complient concrete5 pacakges from me.\'',
                '$pkgDescription = \''.$this->description.'\'',
            ],
            'packageHandle' => [
                '$pkgHandle = \'package-boilerplate\'',
                '$pkgHandle = \''.$this->handle.'\'',
            ],
            'namespace' => [
                'Concrete\\Package\\PackageBoilerplate',
                'Concrete\\Package\\'.Str::studly($this->handle),
            ],
            'otherNameInstances' => ['Package Boilerplate', $this->name],
            'otherDescriptionInstances' => [
                'Start building standards complient concrete5 pacakges from me.',
                $this->description,
            ],
        ];

        $exclusions = [];

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
        $source = $app->make('base_path').DIRECTORY_SEPARATOR.'packages'.DIRECTORY_SEPARATOR.'package-boilerplate';
        $exporter->setSubstitutions($substitutions);
        $exporter->export($source, $this->package_path);

        return true;
    }
}
