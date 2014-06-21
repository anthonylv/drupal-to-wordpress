<?php

/* 
################################################
# Outputs HTML header and footer 
################################################
*/
function showHTMLHeader($heading="", $errors)
{
	global $step;
	@header('Content-Type: text/html; charset=UTF-8');?>
	<!DOCTYPE html>
	<html xmlns="http://www.w3.org/1999/xhtml" xmlns:dc="http://purl.org/dc/terms/" dir="ltr" lang="en-US">
	<head profile="http://gmpg.org/xfn/11">
		<title>Drupal to WordPress migration tool</title>
		<link rel="stylesheet" type="text/css" href="style.css">
		
		<link rel="stylesheet" href="//code.jquery.com/ui/1.10.4/themes/smoothness/jquery-ui.css">
		  <script src="//code.jquery.com/jquery-1.10.2.js"></script>
		  <script src="//code.jquery.com/ui/1.10.4/jquery-ui.js"></script>
	</head>
	<body>
		<div id="container"><?php
			if ( ! empty( $errors ) ) {
				echo '<div class="error">';
				echo "<h3>Sorry, there were errors</h3>";
				echo '<p>' . $errors . '</p>';
				echo '</div>';
			}
			echo "<h1>Drupal 6 to WordPress 3.5 database migration</h1>";
			echo "<div id='navigation'><p><a href='drupaltowordpress.php'>Home</a> | Step: $step</p></div>";
			
			/*
			<div id="navigation">
				<ul>
					<li><a href='index.php'>Home</a></li>
				    <li>Step: <?php echo $step ?></li>
				</ul>
			</div> <!-- #navigation -->
			*/
			
			if ($heading ) echo "<h2>".$heading."</h2>";
}

function showHTMLFooter()
{
	?>
			
			<div id="footer">By <a title="Another Cup of Coffee Limited - Drupal and WordPress support specialists" href="http://anothercoffee.net">Another Cup of Coffee Limited</a>&nbsp;&middot;&nbsp;<a title="Released under The MIT License" href="license.html">License</a>&nbsp;&middot;&nbsp;Version <?php echo D2W_VERSION ?>

			</div> <!-- /footer -->
		</div> <!-- /container -->
		</body>
		</html>
	<?php
}

/*** Default ***/
function showErrorPage($errors)
{
	global $step;
	@header('Content-Type: text/html; charset=UTF-8');?>
	<!DOCTYPE html>
	<html xmlns="http://www.w3.org/1999/xhtml" xmlns:dc="http://purl.org/dc/terms/" dir="ltr" lang="en-US">
	<head profile="http://gmpg.org/xfn/11">
		<title>Wordpress to Drupal migration tool</title>
		<link rel="stylesheet" type="text/css" href="style.css">
	</head>
	<body>
		<div id="container">
			<h1>Drupal 6 to WordPress 3.5 database migration</h1>
			<div id='navigation'><p><a href='drupaltowordpress.php'>Home</a></p></div>
			<h2>Error</h2>
	<p>Sorry there was a problem. The error message is shown below.</p>
	<?php
		if ( ! empty( $errors ) ) {
			echo '<div class="error">';
			echo '<p>' . $errors . '</p>';
			echo '</div>';
		}
		
	showHTMLFooter();
}
?>