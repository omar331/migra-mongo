<?php
$loader = require_once __DIR__ . '/vendor/autoload.php';

include_once('./src/MongooUtils/Backup.php');

include_once('config.php');

$backup = new MongooUtils\Backup( $backupConfig );

$backup->run();

