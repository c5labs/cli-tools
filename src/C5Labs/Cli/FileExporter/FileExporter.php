<?php

/*
 * This file is part of Cli.
 *
 * (c) Oliver Green <oliver@c5labs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace C5Labs\Cli\FileExporter;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use InvalidArgumentException;
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
    protected $mutators = ['processExclusionTags', 'substitute', 'removeTemplateTags'];

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
     * Remove any remaining template tags from the code.
     *
     * @param  string $pathname
     * @param  string $contents
     * @return string
     */
    protected function removeTemplateTags($pathname, $contents)
    {
        $tagPattern = '/[\s]*\/\*[\s]*@section[\s]*([a-z-_]*)[\s]*\*\/';
        $tagPattern .= '|[\s]*\/\*[\s]*@endsection[\s]*[a-z-_]*[\s]*\*\//';
        return preg_replace($tagPattern, '', $contents);
    }

    /**
     * Remove the template tag sections scheduled for removal.
     *
     * @param  string $pathname
     * @param  string $contents
     * @return string
     */
    public function processExclusionTags($pathname, $contents)
    {
        if (! empty($this->exclusions[$pathname]) && is_array($this->exclusions[$pathname])) {

            $tagPattern = '/([\s]*\/\*[\s]*@section[\s]*([a-z-_]*)[\s]*\*\/)';
            $tagPattern .= '[\S\s]*?';
            $tagPattern .= '(=?\/\*[\s]*@endsection[\s]*[a-z-_]*[\s]*\*\/)/im';

            $exclusions = $this->exclusions[$pathname];
            $offsets = [];
            $position = 0;

            // Match ALL template tags.
            if (preg_match_all($tagPattern, $contents, $matches, PREG_SET_ORDER|PREG_OFFSET_CAPTURE)) {
                foreach ($matches as $match) {
                    // Check that the template tags name parameter is in the exclusion
                    // list that we have been asked to remove.
                    if (in_array($match[2][0], $exclusions)) {
                        // Add the start & end byte offsets to an array for removal later.
                        $offsets[] = [$match[1][1], $match[3][1] + strlen($match[3][0])];
                    }
                }
            }

            // Remove the calculated offsets from the document.
            foreach ($offsets as $offset) {
                $str = substr($contents, 0, $offset[0] - $position);
                $str .= substr($contents, $offset[1] - $position);
                $position += ($offset[1] - $offset[0]);
                $contents = $str;
                $str = null;
            }

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
        foreach ($this->substitutions as $key => $substitution) {

            // File specific substitutions
            if (Str::is($key, $pathname)) {
                foreach ($substitution as $file_substitution) {
                    $contents = str_replace($file_substitution[0], $file_substitution[1], $contents);
                }
            }

            // Substituions to be performed on all files
            else {
                $contents = str_replace($substitution[0], $substitution[1], $contents);
            }
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
