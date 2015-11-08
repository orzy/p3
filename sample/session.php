<?php
/**
 *	Last update 2013/03/27
 */

$this->param('title', 'P3_Session Sample');


$session = new P3_Session('a8+S#XC1Uv%eagN+B17VYti:7fyNrie9');


// The normal session value.

$last = $session->get('last');

if ($last) {
	echo "The last visit is $last.";
} else {
	echo 'This is the first time.';
}

$session->set('last', now());

echo '<hr />';


// The one-time session value.

$flash = $session->flash();

if ($flash) {
	echo $flash;
} else {
	$session->flash('Flash message.');
}

echo '<hr />';


// The token of a form.

$this->token('Maybe CSRF?');

$form = $this->form($_POST);
?>
<form method="POST" action="session">
<?php echo $form->token() ?>
<input type="submit" name="token_test" value="With a token" />
</form>

<form method="POST" action="session">
<input type="submit" name="token_test" value="Without a token" />
</form>
<?php

echo '<hr />';


// Login

if ($this->param('pswd')) {
	$hash = $session->hash('hirake goma');
	
	if ($session->login($hash, $this->param('pswd'))) {
		echo 'Valid password';
	} else {
		echo "Invalid password";
	}
}
?>
<form method="POST" action="session">
Password <?php echo $form->password('pswd') ?>
<input type="submit" name="login_test" value="Login" />
</form>
