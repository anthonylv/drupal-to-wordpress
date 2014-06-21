<?php
/*******************************************************************************
 * Fix duplicate terms
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

$query_clear_tables = "DROP TABLE IF EXISTS  ".$database_settings_array['data'].".acc_url_alias_dups_removed;";
$query_clear_tables = $query_clear_tables." DROP TABLE IF EXISTS  ".$database_settings_array['data'].".url_alias_with_dups;";

$query_remove_duplicates = "CREATE TABLE  ".$database_settings_array['data'].".acc_url_alias_dups_removed AS SELECT pid, src, dst FROM  ".$database_settings_array['data'].".url_alias GROUP BY src;";

$query_rename_tables = "RENAME TABLE  ".$database_settings_array['data'].".url_alias TO  ".$database_settings_array['data'].".url_alias_with_dups;";
$query_rename_tables = $query_rename_tables." RENAME TABLE  ".$database_settings_array['data'].".acc_url_alias_dups_removed TO  ".$database_settings_array['data'].".url_alias;";

						
runAlterDatabase($database_settings_array, $query_clear_tables, $errors);
runAlterDatabase($database_settings_array, $query_remove_duplicates, $errors);
runAlterDatabase($database_settings_array, $query_rename_tables, $errors);


print json_encode(array('result' => $errors, 'html_output' => $html_output));
?>