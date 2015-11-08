<?php
$this->template(false);

$url = 'http://' . $_SERVER['HTTP_HOST'] . '/sample';

$feed = new P3_Feed($url, 'P3_Feed Sample');

$feed->add($url . '/', 'P3 Sample');
$feed->add($url . '/form', 'P3_Form Sample', 'This is a sample page of P3_Form.');

$feed->feed();
