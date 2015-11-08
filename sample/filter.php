<?php
/**
 *	Last update 2013/04/02
 */

$this->param('title', 'P3_Filter Sample');

$filter = new P3_Filter();
echo $filter->rule(date('Y-m-d'), 'youbi');
