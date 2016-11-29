<?php
namespace MongooUtils;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;


class Backup {
    /** @var  Array $config */
    protected $config;

    /** @var  Logger $logger */
    protected $logger;

    /** @var  FileSystem $fileSystem */
    protected $fileSystem;


    public function __construct( Array $config = [] ) {
        $this->config = $config;

        // create a log channel
        $this->logger = new Logger('name');
        $this->logger->pushHandler(new StreamHandler( $config['logfile'], Logger::INFO));

        $this->filesystem = new Filesystem();
        $this->finder = new Finder();
    }


    /**
     * Run backup process
     */
    public function run() {
        $this->logger->info('Starting backup process');

        //
        // Execute mongodb dump
        // Backup files are placed into data folder
        //
        try {
            $this->logger->info('Dump a mongodb');

            $this->executeDump( $this->config );
        } catch ( ProcessFailedException $e ) {
            $this->logger->crit('failed to run mongodump command');
            return;
        }

        $this->logger->info('Removing older backups');
        $this->prune( $this->config['keep_latest'] );

        $this->logger->info('Finishing process');
    }




    /**
     * Execute mongodump and save its output
     * @param $config
     */
    public function executeDump( $config ) {
        // build the command line
        $dumpCommand = $this->buildDumpCommand( $this->config );

        $process = new Process( $dumpCommand );
        $process->run();

        // executes after the command finishes
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }




    /**
     * Builds mongodump command line
     *
     * @param array $configs
     * @return string
     */
    public function buildDumpCommand( $configs = [] ) {
        $command = $this->config['mongodump-command'];

        if ( array_key_exists('origin', $configs ) ) {
            $origin = $configs['origin'];
        }

        if ( ! $origin ) {
            throw new \RuntimeException('Origin server is not defined. Check your configuration file.');
        }


        if ( array_key_exists('host', $origin ) ) {
            $command .= sprintf(" --host %s", $origin['host']);
        }
        if ( array_key_exists('username', $origin ) ) {
            $command .= sprintf(" --username %s", $origin['username']);
        }
        if ( array_key_exists('password', $origin ) ) {
            $command .= sprintf(" --password %s", $origin['password']);
        }
        if ( array_key_exists('port', $origin ) ) {
            $command .= sprintf(" --port %s", $origin['port']);
        }
        if ( array_key_exists('database', $origin ) ) {
            $command .= sprintf(" --db %s", $origin['database']);
        }

        if ( array_key_exists('authentication-database', $origin ) ) {
            $command .= sprintf(" --authenticationDatabase %s", $origin['authentication-database']);
        }

        if ( array_key_exists('exclude-collection', $origin ) ) {
            $command .= sprintf(" --excludeCollection %s", $origin['exclude-collection']);
        }


        // Defines outdir
        $backupSubdir = $this->generateBackupName();
        $backupFullpath = $this->getBackupFullPath( $backupSubdir );


        $command .= sprintf(" --out=%s", $backupFullpath );

        return $command;
    }





    /**
     * Generate a name for backup
     * @param string $prefix
     *
     * @return string
     */
    public function generateBackupName( $prefix = "mongodb-backup" ) {
        return sprintf("%s-%04d-%02d-%02d-%02d-%02d-%02d",
                    $prefix,
                    Date('Y'),
                    Date('m'),
                    Date('d'),
                    Date('H'),
                    Date('i'),
                    Date('s')
            );
    }


    /**
     * Get backup full path
     * @param $subdir
     * @return string
     */
    public function getBackupFullPath( $subdir ) {
        return sprintf("%s%s%s", $this->config['datadir'], DIRECTORY_SEPARATOR, $subdir );
    }



    /**
     * Remove a certain backup
     * @param $subdir
     */
    public function removeBackup( $subdir ) {
        $fullpath = $this->getBackupFullPath($subdir);
        $this->rmdir($fullpath);
    }

    /**
     * Removes a directory recursively
     * @param $dir full path to directory
     *
     */
    protected function rmdir( $dir ) {
        system("rm -rf ".escapeshellarg($dir));
    }


    /**
     * Removes oldest backups in datadir
     * @param int $keep amount of latest backups to kept
     *
     */
    public function prune( $keep = 5 ) {

        $oldest = $this->getOldestBackups( $keep );
        foreach( $oldest as  $backupSubdir ) {
            $this->removeBackup($backupSubdir);
        }
    }


    /**
     * Get the oldest backups
     *
     * @param int $keep
     * @return array
     */
    public function getOldestBackups( $keep = 5 ) {
        $backups = $this->getBackupList();

        $oldest = [];
        $n = 0;
        foreach( $backups as $subdir ) {
            $n++;
            if ( $n <= $keep ) continue;

            $oldest[] = $subdir;
        }

        return $oldest;
    }



    /**
     * Get the list of all backup within main backup directory,
     * sorted from lastest to oldest
     *
     * @return array
     */
    protected function getBackupList()
    {
        $backupDataDir = $this->config['datadir'];

        $files = [];

        if ($dh = opendir($backupDataDir)) {
            while (($file = readdir($dh)) !== false) {
                if ( preg_match('/^\./', $file) ) continue;
                $files[] = $file;
            }
            closedir($dh);
        }

        rsort($files);

        return $files;
      }

}