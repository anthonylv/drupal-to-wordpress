<?php
/*******************************************************************************
 * Displays migration options
 *******************************************************************************/ 

function displaySetOptionsPage($wp_settings_array, $d_settings_array) {
	$errors = "";
	$abort = false;
	
	$result = runFetchFromDatabase($d_settings_array, QUERY_DRUPAL_GET_TERMS, $errors);
	$terms_count = count($result);
	
	$result = runFetchFromDatabase($d_settings_array, QUERY_DRUPAL_GET_NODE_TYPES, $errors);
	$node_types_result = $result;
	$node_types_count = count($result);
	$node_types_params_list = buildNodeTypesParamsList($result);

	$result = runFetchFromDatabase($d_settings_array, QUERY_DRUPAL_GET_POSTS, $errors);
	$posts_count = count($result);

	showHTMLHeader("Drupal migration", $errors);
?>
	<form action="<?php step_form_action( ); ?>" method="post">

	<form action="../" onsubmit="return checkCheckBoxes(this);" >
	<input type="hidden" name="wp_host" id="wp_host" value="<?php esc_html_attr( $wp_settings_array['host'], true ) ?>">
	<input type="hidden" name="d_host" id="d_host" value="<?php esc_html_attr( $d_settings_array['host'], true ) ?>">
	<input type="hidden" name="wp_data" id="wp_data" value="<?php esc_html_attr( $wp_settings_array['data'], true ) ?>">
	<input type="hidden" name="d_data" id="d_data" value="<?php esc_html_attr( $d_settings_array['data'], true ) ?>">
	<input type="hidden" name="wp_user" id="wp_user" value="<?php esc_html_attr( $wp_settings_array['user'], true ) ?>">
	<input type="hidden" name="d_user" id="d_user" value="<?php esc_html_attr( $d_settings_array['user'], true ) ?>">
	<input type="hidden" name="wp_pass" id="wp_pass" value="<?php esc_html_attr( $wp_settings_array['pass'], true ) ?>">
	<input type="hidden" name="d_pass" id="d_pass" value="<?php esc_html_attr( $d_settings_array['pass'], true ) ?>">
	<input type="hidden" name="wp_char" id="wp_char" value="<?php esc_html_attr( $wp_settings_array['char'], true ) ?>">
	<input type="hidden" name="d_char" id="d_char" value="<?php esc_html_attr( $d_settings_array['char'], true ) ?>">
	
	<table class="settings_table">
		<caption>Migration options</caption>
		<thead>
			<tr>
				<th class="description">Description</th>				
				<th class="setting">Setting</th>		
			</tr>
		</thead>
		<tbody>
			<tr>
				<td class="description">Delete additional authors in WordPress? (Default admin user created during installation will not be deleted.)</td>				
				<td class="setting"><input type="checkbox" name="formDeleteAuthors" value="Yes" /></td>
			</tr>
			<tr>
					<?php showContentTypeConversion($node_types_result, $node_types_count); ?>
			</tr>
			<tr>
				<td class="description">Drupal file directory</td>
				<td class="setting"><input type="text" size="20" maxlength="255" name="formFilePath" value="/files/"></td>				
			</tr>
			<tr>
				<td class="description">Permalink structure</td>
				<td class="setting"><input type="text" size="20" maxlength="255" name="formPermalinkStructure" value="/content/%postname%/"></td>				
			</tr>
			<tr>
				<td>Please select which Drupal terms will be used as WordPress categories. The remaining terms will be converted into WordPress post tags. It's best not to create too many categories.</td>
				<td><select size="5" name="formTerms[]" multiple="yes">
				<?php
				$result = runFetchFromDatabase($d_settings_array, QUERY_DRUPAL_GET_TERMS, $errors);
				$terms_count = count($result);

				if ($terms_count) { 
					foreach ($result as $row_key => $row_val) {
						echo "<option value='".$row_val['tid']."'>".$row_val['name']." (ID ".$row_val['tid'].")</option>";
					}
				}
				?>
				</select></td>
			</tr>
			<tr>
				<td>Please select your WordPress default category.</td>
				<td><select size="5" name="formDefaultCategory">
				<?php
				if ($terms_count) { 
					foreach ($result as $row_key => $row_val) {
						echo "<option value='".$row_val['tid']."'>".$row_val['name']." (ID ".$row_val['tid'].")</option>";
					}
				}
				?>
				</select></td>
			</tr>			
			
			
		</tbody>
	</table>
		<?php step_submit( "Migrate now", "This will alter your WordPress tables. Are you sure?" ); ?>
	</form>
	
	
	<script>
	function checkCheckBoxes(theForm) {
		if (
			
		<?php
		$checkCount = 0;
		if ($node_types_count) { 
			foreach ($node_types_result as $row_key => $row_val) {
				echo "theForm.formContentTypes[$checkCount].checked == false";
				$checkCount = $checkCount + 1;
				if ($checkCount < $node_types_count) {
					echo " && ";
				} else {
					echo " ) ";
				}
			}
		}
		
		
		?>
		{
			alert ('You didn\'t choose any of the checkboxes!');
			return false;
		} else { 	
			return true;
		}
	}
	</script>
	
<?php
	showHTMLFooter();
}


/*
 *
 */
function showContentTypeConversion($node_types_result, $node_types_count) 
{
	$node_types_count = count($node_types_result);

	echo "<td>Please select which Drupal content types will be converted into WordPress posts. The unselected types will be converted in to pages.</td><td>";

	if ($node_types_count) { 
		foreach ($node_types_result as $row_key => $row_val) {
			echo "<input type=\"checkbox\" name=\"formContentTypes[]\" value=\"".$row_val['type']."\">".$row_val['type']."<br>";
		}
	}
	echo "</td>";
}
?>