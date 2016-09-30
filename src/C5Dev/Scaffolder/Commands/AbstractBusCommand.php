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

use C5Dev\Scaffolder\Application;
use C5Dev\Scaffolder\FileExporter\FileExporter;

abstract class AbstractBusCommand
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
     * @var array
     */
    protected $options;

    /**
     * Path.
     *
     * @var string
     */
    protected $path;

    /**
     * Constructor.
     *
     * @param string $path
     * @param string $handle
     * @param string $name
     * @param string $description
     * @param string $author
     * @param array $options
     */
    public function __construct($path, $handle, $name, $description, $author, $options = null)
    {
        $this->handle = $handle;

        $this->name = $name;

        $this->description = $description;

        $this->author = $author;

        $this->options = $options ?: [];

        $this->path = $path;
    }

    /**
     * Forms a path from an array of parts.
     * 
     * @param  array  $parts
     * @return string
     */
    protected function makePath(array $parts)
    {
        return implode(DIRECTORY_SEPARATOR, $parts);
    }

    /**
     * Handle the command.
     * 
     * @param  Application $app      
     * @param  FileExporter $exporter 
     * @return bool                                        
     */
    abstract public function handle(Application $app, FileExporter $exporter);
}
