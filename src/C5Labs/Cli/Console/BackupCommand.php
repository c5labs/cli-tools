<?php

/*
 * This file is part of Cli.
 *
 * (c) Oliver Green <oliver@c5labs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace C5Labs\Cli\Console;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class BackupCommand extends ConcreteCoreCommand
{
    /**
     * Configure the command.
     *
     * @return void
     */
    protected function configure()
    {
        $this
        ->setName('backup')
        ->setDescription('Backs the current site up.')
        ->setHelp('Backs the current site up.');
    }

    /**
     * Executes the current command.
     *
     * This method is not abstract because you can use this class
     * as a concrete class. In this case, instead of defining the
     * execute() method, you set the code to execute by passing
     * a Closure to the setCode() method.
     *
     * @param InputInterface  $input  An InputInterface instance
     * @param OutputInterface $output An OutputInterface instance
     *
     * @return null|int null or 0 if everything went fine, or an error code
     *
     * @throws LogicException When this abstract method is not implemented
     *
     * @see setCode()
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        parent::execute($input, $output);

        $app = $this->getCliApplication();
        $config = $app->getConcreteConfig('concrete');
        $path = realpath($app->getConcretePath().'/../');
        $output_file = $app->getCurrentWorkingDirectory().'/backup-'.time().'.zip';

        $output->writeln(sprintf(
            "Starting backup of <fg=green>%s</> from %s\r\n",
            (isset($config['site']) ? $config['site'] : 'Unknown'),
            $path
        ));

        // Build a manifest
        //$fs = $app->make('files');
        //$manifest = $fs->allFiles($path);

        $finder = new \Symfony\Component\Finder\Finder();
        $finder->notPath('application/files/cache');
        $finder->notPath('application/files/tmp');
        $finder->followLinks();
        $manifest = $finder->in($path);

        $progress = new ProgressBar($output, count($manifest));
        $progress->start();

        // Create the ZIP
        $z = new \ZipArchive();
        $z->open($output_file, \ZIPARCHIVE::CREATE);

        // Add each file to the ZIP
        foreach ($manifest as $file) {
            if (! $file->isDir()) {
                $z->addFile($file->getPathname(), $file->getRelativePathname());
            } else {
                $z->addEmptyDir($file->getRelativePathname());
            }

            $progress->advance();
        }

        $progress->finish();

        $sql = $this->backup_tables($output);
        $z->addFromString('database-backup.sql', $sql);

        $output->writeln(sprintf("\r\n\r\nPacking ZIP file...", $output_file));

        $z->close();

        $output->writeln(sprintf(
            "\r\n<fg=green>Backup ZIP created at %s.</>\r\n", $output_file)
        );
    }

    /* backup the db OR just a table
     * See original: https://davidwalsh.name/backup-mysql-database-php
     */

    function backup_tables($output, $tables = '*')
    {
        $config = $this->getApplication()->getConcreteConfig('database');
        $connection = $config['connections'][$config['default-connection']];
        extract($connection);

        $output->writeln(sprintf(
            "\r\n\r\nBacking up database '<fg=green>%s</>' from <fg=green>%s</>\r\n",
            $database, $server
        ));

        $link = mysqli_connect($server,$username,$password,$database);
        $return = '';

        //get all of the tables
        if($tables == '*')
        {
            $tables = array();
            $result = $link->query('SHOW TABLES');
            while($row = $result->fetch_row())
            {
                $tables[] = $row[0];
            }
        }
        else
        {
            $tables = is_array($tables) ? $tables : explode(',',$tables);
        }


        $progress = new ProgressBar($output, count($tables));
        $progress->start();

        //cycle through
        foreach($tables as $table)
        {
            $result = $link->query('SELECT * FROM '.$table);
            $num_fields = $result->field_count;

            $return.= 'DROP TABLE '.$table.';';
            $row2 = $link->query('SHOW CREATE TABLE '.$table);
            $row2 = $row2->fetch_array();
            $return.= "\n\n".$row2[1].";\n\n";

            for ($i = 0; $i < $num_fields; $i++)
            {
                while($row = $result->fetch_array())
                {
                    $return.= 'INSERT INTO '.$table.' VALUES(';
                    for($j=0; $j < $num_fields; $j++)
                    {
                        $row[$j] = addslashes($row[$j]);
                        $row[$j] = str_replace("\n","\\n",$row[$j]);
                        if (isset($row[$j])) { $return.= '"'.$row[$j].'"' ; } else { $return.= '""'; }
                        if ($j < ($num_fields-1)) { $return.= ','; }
                    }
                    $return.= ");\n";
                }
            }
            $return.="\n\n\n";
            $progress->advance();
        }

        $progress->finish();

        return $return;
    }
}
