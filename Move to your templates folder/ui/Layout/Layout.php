<!doctype html>
<html lang="en">
<head>
    <meta http-equiv="content-type" content="text/html; charset=utf-8" />

    <title><?= $browserTitle ?></title>

    <meta name="viewport" content="width=device-width, initial-scale=1" /> <!--Responsive-->
    <meta name="description" content="<?= $page->meta_description ?>" />
	<meta name="generator" content="ProcessWire">

    <!-- Favicons - http://www.favicomatic.com -->
	<link rel="apple-touch-icon-precomposed" sizes="144x144" href="<?= $config->urls->templates ?>Layout/images/favicon/apple-touch-icon-144x144.png" />
	<link rel="apple-touch-icon-precomposed" sizes="152x152" href="<?= $config->urls->templates ?>Layout/images/favicon/apple-touch-icon-152x152.png" />
	<link rel="icon" type="image/png" href="<?= $config->urls->templates ?>Layout/images/favicon/favicon-32x32.png" sizes="32x32" />
	<link rel="icon" type="image/png" href="<?= $config->urls->templates ?>Layout/images/favicon/favicon-16x16.png" sizes="16x16" />
	<meta name="application-name" content="&nbsp;"/>
	<meta name="msapplication-TileColor" content="#FFFFFF" />
	<meta name="msapplication-TileImage" content="<?= $config->urls->templates ?>Layout/images/favicon/mstile-144x144.png" />

	<!-- Styles -->
    <? foreach($styles as $file): ?>
        <link rel="stylesheet" href="<?= $file ?>" />
    <? endforeach ?>

    <!-- Scripts -->
    <? foreach($headScripts as $file): ?>
        <script src="<?= $file ?>"></script>
    <? endforeach ?>

	<? if($input->get->modal): ?>
		<base target="_parent" />
	<? endif ?>

    <!-- Analytics -->

    <!--
    This website is powered by ProcessWire CMF/CMS.
    ProcessWire is a free open source content management framework licensed under the GNU GPL.
    ProcessWire is Copyright 2014 by Ryan Cramer / Ryan Cramer Design, LLC.
    Learn more about ProcessWire at: http://processwire.com
    -->
</head>

<body class="page_<?= $page->name ?> template_<?= $page->template ?> <?= $modal ? 'layout_modal' : '' ?>">
<a name="top" id="top"></a>

<? if(!$modal): ?>
	<header class="header">
		<div class="container">Header</div>
	</header>
<? endif ?>

<main class="main">
    <div class="container"><?= $template ?></div>
</main>

<? if(!$modal): ?>
	<footer class="footer">
		<div class="container">Footer</div>
	</footer>
<? endif ?>

<!-- Scripts -->
<? foreach($footScripts as $file): ?>
    <script src="<?= $file ?>"></script>
<? endforeach ?>

</body>
</html>