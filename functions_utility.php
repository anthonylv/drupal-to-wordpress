<?php

/**
 * Form action attribute.
 *
 * @return null
 *
 * Code credits:
 * David Coveney of Interconnect IT Ltd (UK)
 * http://www.davidcoveney.com or http://www.interconnectit.com
 * and released under the WTFPL
 *
 */
function step_form_action( ) {
	global $step;
	echo '?step=' . intval( $step + 1 );
}


/**
 * Simply create a submit button with a JS confirm popup if there is need.
 *
 * Code credits:
 * David Coveney of Interconnect IT Ltd (UK)
 * http://www.davidcoveney.com or http://www.interconnectit.com
 * and released under the WTFPL
 *
 */
function step_submit( $text = 'Submit', $warning = '' ){
	$warning = str_replace( "'", "\'", $warning ); ?>
	<input type="submit" class="button" value="<?php echo htmlentities( $text, ENT_QUOTES, 'UTF-8' ); ?>" <?php echo ! empty( $warning ) ? 'onclick="if (confirm(\'' . htmlentities( $warning, ENT_QUOTES, 'UTF-8' ) . '\')){return true;}return false;"' : ''; ?>/> <?php
}

/**
 * Simple html esc
 *
 * Code credits:
 * David Coveney of Interconnect IT Ltd (UK)
 * http://www.davidcoveney.com or http://www.interconnectit.com
 * and released under the WTFPL
 *
 */
function esc_html_attr( $string = '', $echo = false ){
	$output = htmlentities( $string, ENT_QUOTES, 'UTF-8' );
	if ( $echo )
		echo $output;
	else
		return $output;
}

/**
 * Migrates data from WordPress to Drupal
 *
 */
function migrate($wp_settings_array, $d_settings_array, $options_array) {
	$errors="";
	
	// Prepare WordPress by clearing out the tables
	runAlterDatabase($wp_settings_array, QUERY_TRUNCATE_WP_TABLES, $errors);
	
	if (isset($options_array['deleteAuthors'])) {
		runAlterDatabase($wp_settings_array, QUERY_DELETE_WP_AUTHORS, $errors);
	}
	
	// Migrate posts, categories and tags.
	// Do not change the order of these as some are dependent on
	// other queries having run first.
	runAlterDatabase($wp_settings_array, QUERY_CREATE_WP_TAGS, $errors);
	runAlterDatabase($wp_settings_array, buildQueryConvertDrupalTermsToWPTags($wp_settings_array, $d_settings_array), $errors);	
	runAlterDatabase($wp_settings_array, buildQueryCreateWPPosts($wp_settings_array, $d_settings_array), $errors);
	runAlterDatabase($wp_settings_array, buildQueryConvertPostTypes($wp_settings_array, $options_array['formContentTypes']), $errors);
	runAlterDatabase($wp_settings_array, QUERY_SET_TERM_RELATIONSHIPS, $errors);
	runAlterDatabase($wp_settings_array, QUERY_UPDATE_TAG_COUNTS, $errors);		
	runAlterDatabase($wp_settings_array, QUERY_FIX_TAXONOMY, $errors);
	runAlterDatabase($wp_settings_array, buildQuerySetCategories($wp_settings_array, $options_array['formTerms']), $errors);
	runAlterDatabase($wp_settings_array, buildQuerySetDefaultCategory($wp_settings_array, $options_array['formDefaultCategory']), $errors);	

	runAlterDatabase($wp_settings_array, QUERY_HIDE_ADD_COMMENTS, $errors);
	runAlterDatabase($wp_settings_array, QUERY_UPDATE_COMMENTS_COUNTS, $errors);
	
	runAlterDatabase($wp_settings_array, buildQueryUpdateFilepath($options_array['filePath'], $wp_settings_array), $errors);	
		
	runAlterDatabase($wp_settings_array, QUERY_SET_SITE_NAME, $errors);
	runAlterDatabase($wp_settings_array, QUERY_SET_SITE_DESC, $errors);
	runAlterDatabase($wp_settings_array, QUERY_SET_SITE_EMAIL, $errors);
	runAlterDatabase($wp_settings_array, buildQuerySetPermalinkStructure($wp_settings_array, $options_array['formPermalinkStructure']), $errors);

	return $errors;
}

?>
