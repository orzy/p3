<?php
/**
 *	Last update 2013/03/22
 */

$this->param('title', 'P3_Form Sample');

if (!$_GET) {
	$this->param('txt', 'zip code');
	$this->param('hdn', 'secret');
}

$this->required(array('txt', 'rdo'), 'Required');

$this->rule('txt', 'trim');
$this->rule('txt', 'pattern', '[0-9]{3}-[0-9]{4}', 'Enter a zip code');
$this->rule('txt2', 'max_length', 5);
$this->rule('txt2', 'type', 'number');
$this->rule('txt2', 'zero', 5);
$this->rule('txt3', 'type', 'date', 'Enter a date');
$this->rule('pswd', 'type', 'integer', 'Enter an integer value');
$this->rule('txtara', 'type', 'regular');
$this->rule('txtara', 'char_height', 'upper');
$this->rule('txtara', 'min_length', 10, 'Min length is 10');
$this->rule('txtara', 'max_length', 20, 'Max length is 20');

$form = $this->form($_GET);
?>

<form>
<dl>
<dt>input type="text"</dt>
<dd><?php echo $form->text('txt', array('style' => 'font-weight: bold')) ?></dd>
<dd><?php echo $form->text('txt2', array('placeholder' => 'pad zero')) ?></dd>
<dd><?php echo $form->text('txt3', array('placeholder' => 'date')) ?></dd>

<dt>input type="password"</dt>
<dd><?php echo $form->password('pswd') ?></dd>

<dt>input type="checkbox"</dt>
<dd><?php echo $form->checkbox('chk', 'label') ?></dd>

<dt>input type="radio"</dt>
<dd><?php echo $form->radio('rdo', 'radio1', 'A') ?></dd>
<dd><?php echo $form->radio('rdo', 'radio2', 'B') ?></dd>

<dt>input type="file"</dt>
<dd><?php echo $form->file('fl', 2) ?></dd>

<dt>input type="submit"</dt>
<dd><?php echo $form->submit() ?></dd>

<dt>input type="reset"</dt>
<dd><?php echo $form->reset() ?></dd>

<dt>input type="button"</dt>
<dd><?php echo $form->button('btn', 'alert("clicked")') ?></dd>

<dt>input type="hidden"</dt>
<dd><?php echo $form->hidden('hdn') ?></dd>

<dt>textarea</dt>
<dd><?php echo $form->textarea('txtara') ?></dd>

<dt>select</dt>
<dd><?php echo $form->select('slct', array('A' => 'Option A', 'B' => 'Option B')) ?></dd>
</dl>
</form>

<hr />

<h2>Received data</h2>

<ul>
<?php
foreach ($_GET as $key => $value) {
	echo '<li>' . h($key) . ' = ' . h($value) . '</li>';
}
?>
</ul>
