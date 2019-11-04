<!doctype html>
<html lang="en">
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />

    <title><?= $browserTitle ?></title>

    <meta name="viewport" content="width=device-width, initial-scale=1" />
	<meta name="generator" content="ProcessWire">

	<!-- Styles -->
    <?= $modules->UiBlocks->styles() ?>

	<!-- Scripts -->
	<?= $modules->UiBlocks->headScripts() ?>

</head>

<body>

	<main class="main">
		<?= $main ?>
	</main>

	<!-- Scripts -->
	<?= $modules->UiBlocks->footScripts() ?>

</body>
</html>