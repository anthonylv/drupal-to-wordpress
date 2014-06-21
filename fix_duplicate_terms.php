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

$query_get_duplicate_terms = "SELECT term_data.tid, term_data.name FROM ".$database_settings_array['data'].".term_data INNER JOIN ( SELECT name FROM ".$database_settings_array['data'].".term_data GROUP BY name HAVING COUNT(name) >1 ) temp ON term_data.name= temp.name;";

$result_dup_terms = runFetchFromDatabase($database_settings_array, $query_get_duplicate_terms, $errors);
$duplicate_terms_count = count($result_dup_terms);


if($duplicate_terms_count > 0) {
	foreach ($result_dup_terms as $row_key => $row_val) {
		$update_query = "UPDATE ".$database_settings_array['data'].".term_data ".
								"SET term_data.name = '".$row_val['name']."_".$row_val['tid']."' ".
								"WHERE tid=".$row_val['tid'].";";
										
		$html_output = runAlterDatabase($database_settings_array, $update_query, $errors);
	}
}

print json_encode(array('result' => $errors, 'html_output' => $html_output));
?>