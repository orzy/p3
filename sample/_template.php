<?php
/**
 *	Last update 2013/03/14
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title><?php echo h($this->param('title')) ?></title>
<link rel="alternate" type="application/rss+xml" href="<?php echo $this->baseUrl(null, true) ?>/feed" title="P3_Feed Sample" />
<?php
$html = new P3_Html();
echo $html->css($this->baseUrl() . '/sample.css', '.');
?>
</head>
<body>

<h1><?php echo h($this->param('title')) ?></h1>

<?php echo $content ?>

<hr />

<a href="<?php echo $this->baseUrl() ?>/">Back home</a>

</body>
</html>
