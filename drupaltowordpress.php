<?php
/*******************************************************************************
 * Drupal to WordPress database migration tool
 * by Another Cup of Coffee Limited
 * 
 * This tool performs the bulk of the migration from Drupal 6 to WordPress 3.5.
 * It works pretty well with our own Drupal installations but you may need
 * to make some tweaks, either to the code or to the migrated database. If you're
 * not sure how to make the appropriate changes, Another Cup of Coffee Limited
 * will be happy to do the work. Contact us at http://anothercoffee.net
 * 
 * CAUTION:
 * Make a backup of both your Drupal and WordPress databases before running this
 * tool. USE IS ENTIRELY AT YOUR OWN RISK.
 * 
 * First released 2013-05-25 by Anthony Lopez-Vito of Another Cup of Coffee Limited
 * http://anothercoffee.net
 * 
 * All code is released under The MIT License.
 * Please see LICENSE.txt.
 *
 * Credits: Please see README.txt for credits 
 *
 *******************************************************************************/
require_once("data.inc.php");
require_once("functions_display.php");
require_once("functions_utility.php");
require_once("functions_database.php");

// Start at the beginning if the step counter isn't set
$step = isset( $_REQUEST[ 'step' ] ) ? intval( $_REQUEST[ 'step' ] ) : 0;

if( $step > 0 ) {
	if( !testDatabaseConnection($wp_settings_array, $errors) ) {
		$step = 4;
	}	
}

switch ( $step ) {
	case 0:
		include "display_step00.database-settings.inc.php";
		displayConnectionSettingsPage($wp_settings_array, $d_settings_array);
		break;
	case 1:
		include "display_step01.analysis_results.inc.php";
		displayAnalysisResultsPage($wp_settings_array, $d_settings_array);		
		break;
	case 2:
		include "display_step02.set-options.inc.php";
		displaySetOptionsPage($wp_settings_array, $d_settings_array);		
		break;
	case 3:
		$options_array = array();	
		if(isset($_POST['formDeleteAuthors']) && 
		   $_POST['formDeleteAuthors'] == 'Yes')  {
			$options_array['deleteAuthors'] = $_POST['formDeleteAuthors'];
		}
		if(isset($_POST['formFilePath'])) {
			$options_array['filePath'] = $_POST['formFilePath'];
		}
		if(isset($_POST['formContentTypes'])) {
			$options_array['formContentTypes']=$_POST['formContentTypes'];
		}		
		if(isset($_POST['formTerms'])) {
			$options_array['formTerms']=$_POST['formTerms'];
		}
		if(isset($_POST['formDefaultCategory'])) {
			$options_array['formDefaultCategory']=$_POST['formDefaultCategory'];
		}
		if(isset($_POST['formPermalinkStructure'])) {
			$options_array['formPermalinkStructure']=$_POST['formPermalinkStructure'];
		}

		$errors = migrate($wp_settings_array, $d_settings_array, $options_array);
		include "display_step03.migrate.inc.php";
		displayResultsPage($wp_settings_array, $d_settings_array, $errors);
		break;
	case 4:
	default:
	/*
	print "<pre style='font-size:small'>";
	print_r($_POST);
	print '</pre>';
	print '<hr />';
	print "<pre style='font-size:small'>";
	print_r($options_array);
	print '</pre>';
	print '<hr />';
	*/
		showErrorPage($errors);
		break;
}

?>

