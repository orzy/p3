<?php
/**
 *	Last update 2013/04/05
 */

ini_set('error_reporting', E_ALL);

require('P3/functions.php');

$con = new P3_Controller();

$con->debug();

$con->baseUrl('/sample');

$con->cache('cache', 'tmp', 10);

$con->template();

$con->errorPage(404);

$con->run();
