<?php
include_once('config.php');

/*
 * Executes each migration contained in config.php
 */
foreach( $migrations as $migration ) {
    if ( ! array_key_exists('origin', $migration) ) throw new \RuntimeExcetion('Origin information is requered.');

    /*
     * Builds the dump command
     */
    $origin = $migration['origin'];
    $dumpCommand = buildDumpCommand( $origin );
    echo "Executing dump command $dumpCommand \n";
    exec( $dumpCommand );


    /*
     * Builds the restore command
     */
    $dumpDir = sprintf("./dump/%s", $origin['database']);

    $destination = $migration['destination'];
    $destination['dir'] = $dumpDir;
    $restoreCommand = buildRestoreCommand(  $destination );
    echo "Executing restore command $restoreCommand \n";
    exec( $restoreCommand );
}


/**
 * Builds the command line for dumping a database
 * @param array $origin origin parameters as follows:
 *
 * <code>
 * [
 *  'host' => '',
 *  'username' => '',
 *  'password' => '',
 *  'port' => '',
 *  'database' => '',
 *  'exclude-collection' => ''
 * ]
 * </code>
 *
 * @return string
 */
function buildDumpCommand( Array $origin ) {
    $command = "mongodump";

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
    if ( array_key_exists('exclude-collection', $origin ) ) {
        $command .= sprintf(" --excludeCollection %s", $origin['exclude-collection']);
    }

   return $command;
}



/**
 * Builds the command line for restore a database
 *
 * @param array $destination as follows
 * <code>
 * [
 * 'database' => '',
 * 'username' => '',
 * 'password' => '',
 * 'dir' => ''
 * ]
 * </code>
 *
 * @return string
 */
function buildRestoreCommand( Array $destination ) {
    $command = "mongorestore --drop";

    if ( array_key_exists('host', $destination) ) {
        $command .= sprintf(" --host %s", $destination['host']);
    }

    if ( array_key_exists('username', $destination) ) {
        $command .= sprintf(" --username %s", $destination['username']);
    }

    if ( array_key_exists('password', $destination ) ) {
        $command .= sprintf(" --password '%s'", $destination['password']);
    }

    if ( array_key_exists('database', $destination) ) {
        $command .= sprintf(" --db %s", $destination['database']);
    }

    if ( array_key_exists('dir', $destination) ) {
        $command .= sprintf(" --dir %s", $destination['dir']);
    }

    return $command;
}

