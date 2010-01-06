<?php defined('SYSPATH') or die('No direct script access.') ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title><?php echo $title; ?></title>
	<script type="text/javascript" src="<?php echo $filepaths['jquery']; ?>"></script>
	<script type="text/javascript" src="<?php echo $filepaths['flot']; ?>"></script>
	<!--[if IE]><script language="javascript" type="text/javascript" src="<?php echo $filepaths['excanvas']; ?>"></script><![endif]-->
	<link rel="stylesheet" type="text/css" href="<?php echo $filepaths['css']; ?>"  />
</head>             
<body>
<h1><?php echo $title; ?></h1>
<div style="font-size: 150%;">
<a href="<?php echo $uri['visitors']; ?>">Visitors</a>
 -
<a href="<?php echo $uri['pagehits']; ?>">Page Hits</a>
 - 
<a href="<?php echo $uri['errorhits']; ?>">Error Hits</a>
 -
<a href="<?php echo $uri['referrals']; ?>">Referrals</a>
<?php if (isset($uri['trails'])): ?>
 - 
<a href="<?php echo $uri['trails']; ?>">Trails</a>
<?php endif; ?>
</div>
<?php echo $content; ?>
<hr style="margin-top: 32px; "/>
<div style="padding: 5px 0;">
	Website time now is: <?php echo date('Y/m/d h:ia O');?>. Powered by HitLogger module for KohanaPHP 3.x. &copy; 2009 <a href="http://www.nusantarasoftware.com">Nusantara Software</a>.
</div>
</body>
</html>