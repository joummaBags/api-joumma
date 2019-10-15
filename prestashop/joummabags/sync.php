<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

include(dirname(__FILE__).'/../../config/config.inc.php');
include(dirname(__FILE__).'/jabandonecarts.php');


/**
 * Iniciamos la sincronizacion
 */
$modulo = new joummabags();
$modulo->disparo();