<?php
namespace C5Dev\Rebar\FileExporter;

use InvalidArgumentException;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\SplFileInfo;

class FileExporter
{
    protected $filesystem;

    protected $exclusions = [];

    protected $substitutions = [];

    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    public function setExclusions(array $exclusions)
    {
        $this->exclusions = $exclusions;
    }

    public function setExclusion($pathname, $exclusions)
    {
        if (! is_array($exclusions)) {
            $this->exclusions[$pathname] = true;
        } 

        elseif (isset($this->exclusions[$pathname]) && is_array($this->exclusions[$pathname])) {
            $this->exclusions[$pathname] = $this->exclusions[$pathname] + $exclusions;
        } 

        else {
            $this->exclusions[$pathname] = $exclusions;
        }
    }

    public function setSubstitutions(array $substitutions)
    {
        $this->substitutions = $substitutions;
    }

    protected function shouldExcludeEntirely($path)
    {
        return ! empty($this->exclusions[$path]) &&  true === $this->exclusions[$path];
    }

    protected function prepare(SplFileInfo $file)
    {
        $contents = $this->substitute($file->getContents());

        if (! empty($this->exclusions[$file->getRelativePathname()])) {
            $contents = $this->processLineExclusions(
                $file->getRelativePathname(), $contents
            );
        }

        return $contents;
    }

    protected function processLineExclusions($pathname, $contents)
    {
        if (! empty($this->exclusions[$pathname]) && is_array($this->exclusions[$pathname])) {
            $line_exclusions = $this->exclusions[$pathname];
            $lines = explode("\n", $contents);
            $count = 0;

            foreach ($this->exclusions[$pathname] as $range) {
                array_splice($lines, ($range[0] - $count) - 1, $range[1]);
                $count += $range[1];
            }

            $contents = implode("\n", $lines);
        }

        return $contents;
    }

    protected function substitute($contents)
    {
        foreach ($this->substitutions as $substitution) {
            $contents = str_replace($substitution[0], $substitution[1], $contents);
        }

        return $contents;
    }

    public function export($source_path, $destination_path)
    {
        $source_files = $this->filesystem->allFiles($source_path, true);
        $base_path = dirname($destination_path);

        // Make the directory
        if (! $this->filesystem->isWritable(dirname($base_path))) {
            throw new InvalidArgumentException("Path [$base_path] is not writable.");
        }

        $this->filesystem->makeDirectory($destination_path, 0755, true);

        // Copy the files
        foreach ($source_files as $file) {
            $new_pathname = $destination_path.DIRECTORY_SEPARATOR.$file->getRelativePathName();
            $new_path = $destination_path.DIRECTORY_SEPARATOR.$file->getRelativePath();

            if (! $this->shouldExcludeEntirely($file->getRelativePathname())) {
                // Substitutions & Line Exclusions
                $contents = $this->prepare($file);

                // Make the files directory if needed
                if (! file_exists($new_path)) {
                    $this->filesystem->makeDirectory($new_path, 0755, true);
                }

                $this->filesystem->put($new_pathname, $contents);
            }
        }
    }
}