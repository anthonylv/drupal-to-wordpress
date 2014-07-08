<?php
/*******************************************************************************
 * Displays results of Drupal database analysis 
 *******************************************************************************/ 

function displayAnalysisResultsPage($wp_settings_array, $d_settings_array) {
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

	// Check for problems
	$result_dup_terms = runFetchFromDatabase($d_settings_array, QUERY_DRUPAL_GET_DUPLICATE_TERMS, $errors);
	$duplicate_terms_count = count($result_dup_terms);

	$result_terms_charlength_exceeded = runFetchFromDatabase($d_settings_array, QUERY_DRUPAL_TERMS_CHARLENGTH, $errors);
	$terms_charlength_exceeded_count = count($result_terms_charlength_exceeded);

	$result_dup_aliases = runFetchFromDatabase($d_settings_array, QUERY_DRUPAL_GET_DUPLICATE_ALIAS, $errors);
	$duplicate_aliases_count = count($result_dup_aliases);	

	if( ($duplicate_terms_count > 0) ||
		($terms_charlength_exceeded_count > 0) ||
		($duplicate_aliases_count > 0 )) {
		$abort = true;
	}
		 
	// Show the database analysis results
	showHTMLHeader("Drupal database analysis", $errors);
	
	echo "<table class=\"analysis_table\"><caption>Drupal properties</caption><thead>";
	echo "<th class=\"property\">Property</th><th class=\"found\">Found in Drupal</th></thead><tbody>";

	echo "<tr><td>Terms</td><td>$terms_count terms</td></tr>";
	echo "<tr><td>Node types</td><td>$node_types_count node types (".
				$node_types_params_list.")</td></tr>";
	echo "<tr><td>Entries</td><td>$posts_count entries</td></tr>";
	echo "</tbody></table>";
	
	// Any potential problems? Don't show migration options
	if($abort)
	{
		echo "<table class=\"problems_table\"><caption id=\"problems\">Possible problems</caption><thead>";
		echo "<th class=\"problem_property\">Problem</th><th class=\"problem_found\">Description</th></thead><tbody>";

		// Duplicate terms?
		if($duplicate_terms_count > 0) {
			showDuplicateTermsRow($result_dup_terms, $duplicate_terms_count, $d_settings_array);
		}
		// Exceeded terms character length?
		if($terms_charlength_exceeded_count > 0) {
			showCharLengthExceededRow($result_terms_charlength_exceeded, $terms_charlength_exceeded_count, $d_settings_array);
		}
		// Duplicate aliases?
		if($duplicate_aliases_count > 0) {
			showDuplicateAliasesRow($result_dup_aliases, $duplicate_aliases_count, $d_settings_array);
		}
		echo "</tbody></table>";
	} ?>
	<form action="<?php step_form_action( ); ?>" method="post">
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
	<?php step_submit( "Set options"); ?>
	</form>
	
	<div style="visibility:hidden">
		<p id="status"></p>
	</div>
	
	<?php
	showHTMLFooter();
}


/*
 * Allow user to fix duplicate terms
 */
function showDuplicateTermsRow($result_dup_terms, $duplicate_terms_count, $d_settings_array)
{
		echo "<tr><td>Duplicate terms</td><td><div id=\"dup_terms\"><p>$duplicate_terms_count duplicate terms. We can't import duplicate terms into WordPress. The migration will fail if these are to be included. Clicking <em>Fix</em> will solve this by appending the term's <em>tid</em> to create a unique term.</p>";
		?>
		<button id="duplicate_terms_opener">View terms</button> <button type="button" onclick="fixDuplicates()" style="margin-top:10px">Fix</button></div>

		<div id="duplicate_terms_dialog" title="Possible problems">
			<table class="problems_table">
				<caption class="problems">Duplicate terms</caption>
				<thead>
					<th class="problem_found">name</th>
					<th class="problem_found">count</th>
				</thead>
				<tbody>
			
				<?php
					foreach ($result_dup_terms as $row_key => $row_val) {
						echo "<tr><td>".$row_val['name']."</td><td>".$row_val['c']."</td></tr>";
					}
				?>
			</table>
		</div>

		<script>
		$( "#duplicate_terms_dialog" ).dialog({ autoOpen: false, minWidth: 600, maxHeight: 400 });
		$( "#duplicate_terms_opener" ).click(function() {
		  $( "#duplicate_terms_dialog" ).dialog( "open" );
		});
		</script>
		
		
		<script>
		function fixDuplicates() {
		    var statusBox = document.getElementById("status");
			statusBox.style.visibility = "visible";
			statusBox.style.border = "1px solid red";
			statusBox.style.padding = "10px";
			var duplicateTerms = document.getElementById("dup_terms");
			// Send the data using post
			var posting = $.post( 'fix_duplicate_terms.php', { host: "<?php echo $d_settings_array['host']; ?>",
															database: "<?php echo $d_settings_array['data']; ?>",
																user: "<?php echo $d_settings_array['user']; ?>",
																pass: "<?php echo $d_settings_array['pass']; ?>" });

			// Put the results in a div
			posting.done(function( data ) {
			    var response = "";
				try {
					var result = $.parseJSON( data ).result;
					var html = $.parseJSON( data ).html_output;
					statusBox.innerHTML = "<p>Result: " + result + "</p>"+html;
				} catch (e) {
				  	console.error("Parsing error:", e); 
					statusBox.innerHTML = "Parsing error";
				}
			  });
			duplicateTerms.innerHTML = "<p>Duplicates fixed</p>";
		}
		</script>
		
	</td></tr>
<?php
}


/*
 * Allow user to terms that exceed WordPress 200 char length
 */
function showCharLengthExceededRow($result_terms_charlength_exceeded, $terms_charlength_exceeded_count, $d_settings_array)
{

	echo "<tr><td>Term character length exceeded</td><td><div id=\"terms_charlength\"><p>$terms_charlength_exceeded_count terms exceed WordPress' 200 character length. The migration will fail if these are to be included. Clicking <em>Fix</em> will solve this by truncating the column to 200 characters. <strong>Warning:</strong> this will cause some data loss on the truncated columns.</p>";	
	?>
<button id="terms_charlength_exceeded_opener">View terms</button> <button type="button" onclick="fixTermCharlength()" style="margin-top:10px">Fix</button></div>

<div id="terms_charlength_exceeded_dialog" title="Possible problems">
	<table class="problems_table">
		<caption class="problems">Term character length exceeded</caption>
		<thead>
			<th class="problem_property">tid</th>
			<th class="problem_property">name</th>
		</thead>
		<tbody>

		<?php
		foreach ($result_terms_charlength_exceeded as $row_key => $row_val) {
			echo "<tr><td>".$row_val['tid']."</td><td>".$row_val['name']."</td></tr>";
		} 
		?>
			</table>
		</div>

		<script>
		$( "#terms_charlength_exceeded_dialog" ).dialog({ autoOpen: false, minWidth: 600, maxHeight: 400 });
		$( "#terms_charlength_exceeded_opener" ).click(function() {
		  $( "#terms_charlength_exceeded_dialog" ).dialog( "open" );
		});
		</script>
		
		<script>
		function fixTermCharlength() {
		    var statusBox = document.getElementById("status");
			statusBox.style.visibility = "visible";
			statusBox.style.border = "1px solid red";
			statusBox.style.padding = "10px";
			var duplicateTerms = document.getElementById("terms_charlength");
			// Send the data using post
			var posting = $.post( 'fix_terms_charlength.php', { host: "<?php echo $d_settings_array['host']; ?>",
															database: "<?php echo $d_settings_array['data']; ?>",
																user: "<?php echo $d_settings_array['user']; ?>",
																pass: "<?php echo $d_settings_array['pass']; ?>" });

			// Put the results in a div
			posting.done(function( data ) {
			    var response = "";
				try {
					var result = $.parseJSON( data ).result;
					var html = $.parseJSON( data ).html_output;
					statusBox.innerHTML = "<p>Result: " + result + "</p>"+html;
				} catch (e) {
				  	console.error("Parsing error:", e); 
					statusBox.innerHTML = "Parsing error";
				}
			  });
			duplicateTerms.innerHTML = "<p>Term character length fixed</p>";
		}
		</script>
	</td></tr>
<?php
}


/*
 * Allow user to fix duplicate aliases
 */
function showDuplicateAliasesRow($result_dup_aliases, $duplicate_aliases_count, $d_settings_array)
{
	echo "<tr><td>Duplicate aliases</td><td><div id=\"dup_aliases\"><p>$duplicate_aliases_count duplicate aliases found. Due to the way we build the WordPress post data, Drupal nodes with multiple url aliases will cause errors. <a href=\"#\" id=\"duplicate_aliases_info_opener\">More</a></p>";
	?>
	
	<div id="duplicate_aliases_info_dialog" title="More">
		<p>The node ID in Drupal's <em>node</em> table is used to create the post ID in WordPress' <em>wp_posts</em> table.</p>
		<p>The post name in WordPress' <em>wp_posts</em> table is created using either
			<ul>
				<li>(a) the url alias (dst field) in Drupal's <em>url_alias</em> table OR</li>
				<li>(b) the node id (nid) in Drupal's <em>node</em> table IF there is no url alias</li>
			</ul>
		<p>If there are multiple Drupal aliases with the same node ID, we will end up trying to create multiple entries into the WordPress <em>wp_posts</em> table with the same wp_posts ID. This will cause integrity constraint violation errors since the ID field in <em>wp_posts</em> is a unique primary key.</p>
		<p>To avoid this error, we need to check for duplicate aliases.</p>
	</div>
	
	<script>
	$( "#duplicate_aliases_info_dialog" ).dialog({ autoOpen: false, minWidth: 600, maxHeight: 400 });
	$( "#duplicate_aliases_info_opener" ).click(function() {
  		$( "#duplicate_aliases_info_dialog" ).dialog( "open" );
	});
	</script>
	
	<button id="duplicate_aliases_opener">View aliases</button> <button type="button" onclick="fixDuplicateAliases()" style="margin-top:10px">Fix</button></div>

	<div id="duplicate_aliases_dialog" title="Possible problems">
		<table class="problems_table">
			<caption class="problems">Duplicate aliases</caption>
			<thead>
				<th class="problem_found">src</th>
				<th class="problem_found">count</th>
			</thead>
			<tbody>
			<?php
			foreach ($result_dup_aliases as $row_key => $row_val) {
				echo "<tr><td>".$row_val['src']."</td><td>".$row_val['c']."</td></tr>";
			}
			?>
		</table>
	</div>

	<script>
	$( "#duplicate_aliases_dialog" ).dialog({ autoOpen: false, minWidth: 600, maxHeight: 400 });
	$( "#duplicate_aliases_opener" ).click(function() {
  		$( "#duplicate_aliases_dialog" ).dialog( "open" );
	});
	</script>
	
	<script>
	function fixDuplicateAliases() {
	    var statusBox = document.getElementById("status");
		statusBox.style.visibility = "visible";
		statusBox.style.border = "1px solid red";
		statusBox.style.padding = "10px";
		var duplicateAliases = document.getElementById("dup_aliases");
		// Send the data using post
		var posting = $.post( 'fix_duplicate_aliases.php', { host: "<?php echo $d_settings_array['host']; ?>",
														database: "<?php echo $d_settings_array['data']; ?>",
															user: "<?php echo $d_settings_array['user']; ?>",
															pass: "<?php echo $d_settings_array['pass']; ?>" });

		// Put the results in a div
		posting.done(function( data ) {
		    var response = "";
			try {
				var result = $.parseJSON( data ).result;
				var html = $.parseJSON( data ).html_output;
				statusBox.innerHTML = "<p>Result: " + result + "</p>"+html;
			} catch (e) {
			  	console.error("Parsing error:", e); 
				statusBox.innerHTML = "Parsing error";
			}
		  });
		duplicateAliases.innerHTML = "<p>Duplicate aliases fixed</p>";
	}
	</script>
</td></tr>	
<?php	
}
?>