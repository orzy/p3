<?php
/**
 *	Last update 2013/04/10
 */

$this->param('title', 'P3 Sample');
?>

<ul>
<li><a href="cache">Cache</a></li>
<li><a href="db">DB</a></li>
<li><a href="feed">Feed</a></li>
<li><a href="form">Form</a></li>
<li><a href="filter">Filter</a></li>
<li><a href="http?param=123">Http</a></li>
<li><a href="url/<?php echo ue('日本語') ?>">Get a value in URL</a></li>
<li>
	Session
	<ul>
	<li><a href="session">Session</a></li>
	<li><a href="login">Login</a></li>
	</ul>
</li>
<li>
	Error
	<ul>
	<li><a href="404-not-found">404 Not Found</a></li>
	<li><a href="500error">500 Internal Server Error</a></li>
	</ul>
</li>
</ul>
