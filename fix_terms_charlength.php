<?php
/*******************************************************************************
 * WordPress term name field is set 200 chars but Drupal's is term name is 255 chars
 * Truncate the field to fit WordPress
 *******************************************************************************/ 
require_once("functions_database.php");

$result = "ERROR";
$html_output = "";

$database_settings_array = array();
$database_settings_array['host'] = filter_var( $_POST['host'], FILTER_SANITIZE_STRING);
$database_settings_array['data'] = filter_var( $_POST['database'], FILTER_SANITIZE_STRING);
$database_settings_array['user'] = filter_var( $_POST['user'], FILTER_SANITIZE_STRING);
$database_settings_array['pass'] = filter_var( $_POST['pass'], FILTER_SANITIZE_STRING);

$errors = "";

$update_query = "UPDATE ".$database_settings_array['data'].".term_data SET name=SUBSTRING(name, 1, 200);";
$html_output = runAlterDatabase($database_settings_array, $update_query, $errors);

print json_encode(array('result' => $errors, 'html_output' => $html_output));
?>