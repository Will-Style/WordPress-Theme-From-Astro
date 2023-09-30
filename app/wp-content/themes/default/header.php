<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="index,follow">
	<meta name="format-detection" content="telephone=no">
<?php include( 'components/Meta.php'); ?>
<?php include( 'components/Favicons.php'); ?>
    
	<link rel="alternate" type="application/rss+xml" title="<?php bloginfo('name');?> &raquo; フィード" href="<?php echo home_url('/');?>feed/" />
    <?php wp_head();?>
	<link rel="stylesheet" href="<?php echo home_url('/');?>assets/css/style.css?v=<?php echo VERSION;?>">
	<script src="<?php echo home_url('/');?>assets/js/main.js?v=<?php echo VERSION;?>" type="module"></script>
</head>
<body>
<div id="page">
    <?php include( 'components/SkipContents.php'); ?>
    <?php include( 'components/Intro.php'); ?>
    <?php $namespace = get_namespace();?>
    <div id="content">
        <?php
            if(!is_blank()) : 
                include( 'components/Drawer.php'); 
                include( 'components/Header.php');
            endif;
        ?>