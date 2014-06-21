<?php
/*******************************************************************************
 * Displays table of database settings 
 *******************************************************************************/ 

function displayConnectionSettingsPage($wp_settings_array, $d_settings_array) {
	$errors = "";
	showHTMLHeader("", $errors);
?>
<p>This tool performs the bulk of the migration from <strong>Drupal 6</strong> to <strong>WordPress 3.5</strong>. It works pretty well with our own Drupal installations but you may need to make some tweaks to get things right for you, either to the code or to the migrated WordPress database. If you're not sure how to make the appropriate changes or would simply like someone else to do the work, please <a href="http://anothercoffee.net">contact Another Cup of Coffee Limited</a> and we'll be happy to provide a quotation.</p>

<p><strong>CAUTION:</strong> Make a backup of both your Drupal and WordPress databases before running this tool. <em>USE IS ENTIRELY AT YOUR OWN RISK.</em></p>

<form action="<?php step_form_action( ); ?>" method="post">
<TABLE>
<CAPTION>Database connection details</CAPTION>
<thead>
</thead>
<tbody>
<TR>
	<TH>Server Name:</TH>
	<TD><input class="setting" type="text" name="wp_host" id="wp_host" value="<?php esc_html_attr( $wp_settings_array['host'], true ) ?>" /></TD>
</TR>
<TR>
	<TH>Username:</TH>
	<TD><input class="setting" type="text" name="wp_user" id="wp_user" value="<?php esc_html_attr( $wp_settings_array['user'], true ) ?>" /></TD>
</TR>
<TR>
	<TH>Password:</TH>
	<TD><input class="setting" type="password" name="wp_pass" id="wp_pass" value="<?php esc_html_attr( $wp_settings_array['pass'], true ) ?>" /></TD>
</TR>
<TR>
	<TH>WordPress Database Name:</TH>
	<TD><input class="setting" type="text" name="wp_data" id="wp_data" value="<?php esc_html_attr( $wp_settings_array['data'], true ) ?>" /></TD>
</TR>
<TR>
	<TH>WordPressCharset:</TH>
	<TD><input class="setting" type="text" name="d_char" id="d_char" value="<?php esc_html_attr( $d_settings_array['char'], true ) ?>" /></TD>
</TR>
<TR>
	<TH>Drupal Database Name:</TH>
	<TD><input class="setting" type="text" name="d_data" id="d_data" value="<?php esc_html_attr( $d_settings_array['data'], true ) ?>" /></TD>
</TR>
<TR>
	<TH>Drupal Charset:</TH>
	<TD><input class="setting" type="text" name="d_char" id="d_char" value="<?php esc_html_attr( $d_settings_array['char'], true ) ?>" /></TD>
</TR>
</tbody>
</TABLE>

<p>First we will analyze your Drupal database. No changes will be made.</p>
		<?php step_submit( 'Analyze Drupal' ); ?>
</form>

<?php
	showHTMLFooter(); 
}
?>