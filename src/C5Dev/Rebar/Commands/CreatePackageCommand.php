<?php
namespace C5Dev\Rebar\Commands;

use Illuminate\Support\Str;

class CreatePackageCommand
{
    protected $handle;
    
    protected $name;
    
    protected $description;
    
    protected $author;
    
    protected $options;
    
    protected $package_path;

    public function __construct($package_path, $handle, $name, $description, $author, $options = null)
    {
        $this->handle = $handle;

        $this->name = $name;

        $this->description = $description;

        $this->author = $author;

        $this->options = $options ?: [];

        $this->package_path = $package_path;
    }

    public function handle(\C5Dev\Rebar\Application $app, \C5Dev\Rebar\FileExporter\FileExporter $exporter)
    {
        $substitutions = [
            'packageAuthor' => ['Oliver Green <oliver@c5dev.com>', $this->author],
            'packageName' => ['$pkgName = \'Rebar\'', '$pkgName = \''.$this->name.'\''],
            'packageDescription' => ['$pkgDescription = \'A boilerplate kind of thing.\'', '$pkgDescription = \''.$this->description.'\''],
            'packageHandle' => ['$pkgHandle = \'rebar\'', '$pkgHandle = \''.$this->handle.'\''],
            'namespace' => ['Concrete\\Package\\Rebar', 'Concrete\\Package\\'.Str::studly($this->handle)],
            'otherNameInstances' => ['Rebar', $this->name],
            'otherDescriptionInstances' => ['A boilerplate kind of thing.', $this->description],
        ];

        $exclusions = [];

        if (! isset($this->options['uses_composer']) || false === $this->options['uses_composer']) {
            $exporter->setExclusions([
                'controller.php' => [[119, 15],[155, 3]]
            ]);
        }

        if (! isset($this->options['uses_service_providers']) || false === $this->options['uses_service_providers']) {
            $exporter->setExclusion('src/Helpers/DemoHelper.php', true);
            $exporter->setExclusion('src/Providers/HelperServiceProvider.php', true);
            $exporter->setExclusion('controller.php', [[109, 10],[134, 14],[158,3]]);
        }

        // Export the files
        $source = $app->make('base_path').DIRECTORY_SEPARATOR.'packages'.DIRECTORY_SEPARATOR.'rebar';
        $exporter->setSubstitutions($substitutions);
        $exporter->export($source, $this->package_path);

        return true;
    }
}