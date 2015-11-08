<?php
/**
 *	Last update 2013/04/05
 */

$this->param('title', 'P3_Session Login Sample');

$session = new P3_Session();
$session->check('session');
$session->logout();
?>
This page is shown if you have logined.
