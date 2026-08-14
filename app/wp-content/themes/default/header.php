<!DOCTYPE html>
<html <?php language_attributes(); ?> class="theme:light">
<head>
	<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index,follow">
    <meta name="view-transition" content="same-origin">
	<meta name="format-detection" content="telephone=no">
<?php include( 'components/Meta.php'); ?>
<?php include( 'components/Favicons.php'); ?>
    
	<link rel="alternate" type="application/rss+xml" title="<?php bloginfo('name');?> &raquo; フィード" href="<?php echo home_url('/');?>feed/" />
    <?php wp_head();?>
	<link rel="stylesheet" href="/assets/css/style.css?v=<?php echo VERSION;?>">
  
	<script src="/assets/js/main.js" type="module"></script>
</head>
<?php $namespace = get_namespace();?>
<body>
<div id="page">
    <?php if(!is_blank()) : ?>
        <canvas id="gl"></canvas>
        <?php include( 'components/SVG.php'); ?>
        <?php include( 'components/MouseStalker.php'); ?>
        <?php include( 'components/SkipContents.php'); ?>
        <?php include( 'components/Intro.php'); ?>
        <?php include( 'components/Drawer.php'); ?>
        <?php include( 'components/Header.php'); ?>
    <?php endif;?>
    <div id="content">
                       