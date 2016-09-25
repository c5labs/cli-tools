<?php

/*
 * This file is part of Rebar.
 *
 * (c) Oliver Green <oliver@c5dev.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace C5Dev\Rebar\FileExporter;

use InvalidArgumentException;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Finder\SplFileInfo;

class FileExporter
{
    /**
     * Filesystem.
     *
     * @var \Illuminate\Filesystem\Filesystem
     */
    protected $filesystem;

    /**
     * Source path exclusions.
     *
     * Pathnames listed here will not be copied from the
     * source to the destination or will have line ranges removed.
     *
     * @var array
     */
    protected $exclusions = [];

    /**
     * File content string substitutions.
     *
     * @var array
     */
    protected $substitutions = [];

    /**
     * File content mutators.
     * 
     * @var array
     */
    protected $mutators = ['processLineExclusions', 'substitute'];

    /**
     * Constructor.
     *
     * @param \Illuminate\Filesystem\Filesystem $filesystem
     */
    public function __construct(Filesystem $filesystem)
    {
        $this->filesystem = $filesystem;
    }

    /**
     * Set the exclusion array.
     *
     * @param array $exclusions
     */
    public function setExclusions(array $exclusions)
    {
        $this->exclusions = $exclusions;
    }

    /**
     * Set an exclusion entry.
     *
     * @param string $pathname
     * @param bool|array $exclusions
     */
    public function setExclusion($pathname, $exclusions)
    {
        if (! is_array($exclusions)) {
            $this->exclusions[$pathname] = true;
        } elseif (isset($this->exclusions[$pathname]) && is_array($this->exclusions[$pathname])) {
            $this->exclusions[$pathname] = array_merge($this->exclusions[$pathname], $exclusions);
        } else {
            $this->exclusions[$pathname] = $exclusions;
        }
    }

    /**
     * Set the subsitution array.
     *
     * @param array $substitutions
     */
    public function setSubstitutions(array $substitutions)
    {
        $this->substitutions = $substitutions;
    }

    /**
     * Should a pathname be excluded from the export?
     *
     * @param  string $path
     * @return bool
     */
    protected function shouldExcludeEntirely($path)
    {
        return ! empty($this->exclusions[$path])
            &&  true === $this->exclusions[$path];
    }

    /**
     * Perform mutations on a files content and return the result.
     *
     * @param  SplFileInfo $file
     * @return string
     */
    protected function prepare(SplFileInfo $file)
    {
        $contents = $file->getContents();

        foreach ($this->mutators as $mutator) {
            $contents = call_user_func_array(
                [$this, $mutator],
                [$file->getRelativePathname(), $contents]
            );
        }

        return $contents;
    }

    /**
     * Process line exlusions for a given path on the given content.
     *
     * @param  string $pathname
     * @param  string $contents
     * @return string
     */
    protected function processLineExclusions($pathname, $contents)
    {
        if (! empty($this->exclusions[$pathname]) && is_array($this->exclusions[$pathname])) {
            $line_exclusions = $this->exclusions[$pathname];
            uasort($line_exclusions, function ($a, $b) {
                return $a[0] > $b[0];
            });

            $lines = explode("\n", $contents);
            $count = 0;

            foreach ($line_exclusions as $range) {
                array_splice($lines, ($range[0] - $count) - 1, $range[1]);
                $count += $range[1];
            }

            $contents = implode("\n", $lines);
        }

        return $contents;
    }

    /**
     * Perform string substitutions on content.
     *
     * @param  string $contents
     * @return string
     */
    protected function substitute($pathname, $contents)
    {
        foreach ($this->substitutions as $substitution) {
            $contents = str_replace($substitution[0], $substitution[1], $contents);
        }

        return $contents;
    }

    /**
     * Export files from a source path to a destination path.
     *
     * @param  string $source_path
     * @param  string $destination_path
     * @return bool
     */
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
