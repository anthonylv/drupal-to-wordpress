<?php
/*******************************************************************************
 *
 * Functions to operate on database
 *
 *******************************************************************************/

/*
 * Converts selected Drupal content types to WordPress 'post'
 * the rest are converted to 'page'
 */
function buildQueryConvertPostTypes($wp_settings_array, $content_type_array) {
	$node_types_count = count($content_type_array);	
	$types_list="";
	$counter = 0;

	if ($node_types_count) { 
		foreach ($content_type_array as $row_key => $row_val) {
			$node_types_params_list=$node_types_params_list."'".$row_val."'";
						
			if ($counter < $node_types_count-1) {
				$node_types_params_list = $node_types_params_list.", ";
				$counter++;
			}
		}
	}
	
	// Selected node types are converted to 'post'
	$query = "UPDATE ".$wp_settings_array['data'].".wp_posts ".
									"SET post_type = 'post' ".
									"WHERE post_type IN (".$node_types_params_list.");";
	// remaining are set to 'page'
	$query = $query." UPDATE ".$wp_settings_array['data'].".wp_posts ".
								"SET post_type = 'page' ".
								"WHERE post_type NOT IN ('post');";									
	return $query;
}

/*
 * Gets nodes from Drupal and inserts them into WordPress 
 */
function buildQueryCreateWPPosts($wp_settings_array, $d_settings_array) {
	
	// Get all available Drupal node types
	$result = runFetchFromDatabase($d_settings_array, QUERY_DRUPAL_GET_NODE_TYPES, $errors);
	$node_types_count = count($result);
	$node_types_params_list = buildNodeTypesParamsList($result);
	
	// Insert associated nodes as WordPress posts
	$query = "INSERT INTO ".$wp_settings_array['data'].".wp_posts ".
		"(id, post_author, post_date, post_content, post_title, post_excerpt, ".
		"post_name, post_modified, post_type, post_status, to_ping, pinged, post_content_filtered) ".
		"SELECT DISTINCT ".
			"n.nid 'id', ".
			"n.uid 'post_author', ".
			/* ACCL:
			 * Original query had "FROM_UNIXTIME(n.created) 'post_date', ".
			 * FROM_UNIXTIME cannot handle dates prior to 1970. If a post date is somehow set to a date
			 * before 1970, this returns a NULL and will cause the query to fail. DATE_ADD solves this.
			 */
			"DATE_ADD(FROM_UNIXTIME(0), interval n.created second) 'post_date', ".
			"r.body 'post_content', ".
			"n.title 'post_title', ".
			"r.teaser 'post_excerpt', ".
			//No url alias? Use node ID. Strip directory path
			"IF(a.dst IS NULL, n.nid, SUBSTRING_INDEX(a.dst, '/', -1)) 'post_name', ".			
			"DATE_ADD(FROM_UNIXTIME(0), interval n.changed second) 'post_modified', ".
			"n.type 'post_type', ".
			"IF(n.status = 1, 'publish', 'private') 'post_status', ".
			"' ', ".
			"' ', ".
			"' ' ".
			"FROM ".$d_settings_array['data'].".node n ".
			"INNER JOIN ".$d_settings_array['data'].".node_revisions r ".
			"USING(vid) ".
			"LEFT OUTER JOIN ".$d_settings_array['data'].".url_alias a ".
			"ON a.src = CONCAT('node/', n.nid) ".
		"WHERE n.type IN (".$node_types_params_list.");";
		
		return $query;
}


/*
 * room34.com: Fix post type; http://www.mikesmullin.com/development/migrate-convert-import-tindrupal6-5-to-wordpress-27/#comment-17826
 * Add more Drupal content types below if applicable.
 */
function buildQuerySetWPPostType($node_types, $wp_settings_array, $d_settings_array) {
	$query = "UPDATE ".$wp_settings_array['data'].".wp_posts ".
									"SET post_type = 'post' ".
									"WHERE post_type IN (".$node_types.");";								
	return $query;
}

/*
 * room34.com: Fix images in post content; uncomment if you're moving files from "files" to "wp-content/uploads".
 */
function buildQueryUpdateFilepath($filepath, $wp_settings_array) {
	$query = "UPDATE ".$wp_settings_array['data'].
										".wp_posts SET post_content = REPLACE(post_content, '\"".$filepath."', '\"/wp-content/uploads/');";
	return $query;						
}

/*
 *
 */
function buildQuerySetPermalinkStructure($wp_settings_array, $permalink_structure ) {
	$query = "UPDATE ".$wp_settings_array['data'].".wp_options ".
										"SET option_value = '$permalink_structure' ".
										"WHERE option_name = 'permalink_structure';";
										
	return $query;
}

/*
 * Builds node types in comma separated list for use in queries
 */
function buildNodeTypesParamsList($params_array) {
	
	// Build node types list
	$node_types_count = count($params_array);	
	$types_list="";
	$counter = 0;

	if ($node_types_count) { 
		foreach ($params_array as $row_key => $row_val) {		
			$types_list=$types_list."'".$row_val['type']."'";
			if ($counter < $node_types_count-1) {
				$types_list = $types_list.", ";
				$counter++;
			}
		}
	}
	return $types_list;
}

/*
 * room34.com: Auto-assign posts to category.
 * You'll need to work out your own logic to determine strings/terms to match.
 * Repeat this block as needed for each category you're creating.
 */
function buildQueryAssignPostsToCategory($wp_settings_array) {
 	$query = "INSERT IGNORE INTO ".$wp_settings_array['data'].".wp_term_relationships (object_id, term_taxonomy_id) ".
									"SELECT DISTINCT p.ID AS object_id, ".
										"(SELECT tt.term_taxonomy_id ".
										"FROM ".$wp_settings_array['data'].".wp_term_taxonomy tt ".
										"INNER JOIN ".$wp_settings_array['data'].".wp_terms t USING (term_id) ".
										"WHERE t.slug = 'enter-category-slug-here' ".
										"AND tt.taxonomy = 'category') AS term_taxonomy_id ".
									"FROM ".$wp_settings_array['data'].".wp_posts p ".
									"WHERE p.post_content LIKE '%enter string to match here%' ".
									"OR p.ID IN ( ".
										"SELECT tr.object_id ".
										"FROM ".$wp_settings_array['data'].".wp_term_taxonomy tt ".
										"INNER JOIN ".$wp_settings_array['data'].".wp_terms t USING (term_id) ".
										"INNER JOIN ".$wp_settings_array['data'].".wp_term_relationships tr USING (term_taxonomy_id) ".
										"WHERE t.slug IN ('enter','terms','to','match','here') ".
										"AND tt.taxonomy = 'post_tag');";
	return $query;
}


/*
 * Turns all Drupal terms into Drupal post tags 
 *
 * Under WordPress, tags would be more numerous than categories.
 * It's more efficient to blanket convert all terms into tags,
 * then offer the choice to convert selected tags into categories.
 *
 * room34.com's similar query didn't work for me as it didn't correctly match
 * Drupal's term ID for use with WordPress. WordPress associates a post's object_id
 * with a tag or category via term_taxonomy_id in table wp_term_relationships.

 * term_taxonomy_id is the primary key of table wp_term_taxonomy.
 * Therefore, when their query ran, new term_taxonomy_id primary keys are created in
 * wp_term_taxonomy as term_ids are inserted. Thus, the term_taxonomy_id no longer
 * corresponds to Drupal's tid
 *
 * I fixed this by inserting Drupal's tid into term_taxonomy_id and term_taxonomy_id
 *
 */
function buildQueryConvertDrupalTermsToWPTags($wp_settings_array, $d_settings_array) {
	$query = "INSERT INTO ".$wp_settings_array['data'].".wp_term_taxonomy ".
				"(term_taxonomy_id, term_id, taxonomy, description, parent)  ".
					"SELECT DISTINCT  ".
						"d.tid, ".
						"d.tid 'term_id', ".
						"'post_tag', ".
						"d.description 'description', ".
						"h.parent 'parent' ".
					"FROM ".$d_settings_array['data'].".term_data d ".
					"INNER JOIN ".$d_settings_array['data'].".term_hierarchy h ".
						"USING(tid) ".
					"INNER JOIN ".$d_settings_array['data'].".term_node n ".
						"USING(tid) WHERE (1); ";
						
	return $query;
}

/*
 *
 */
function buildQuerySetCategories($wp_settings_array, $term_id_array) {
	$term_id_count = count($term_id_array);
	$term_id_list="";
	$counter = 0;
	if ($term_id_count) { 
		foreach ($term_id_array as $row_key => $row_val) {
			$term_id_list=$term_id_list."'".$row_val."'";
			if ($counter < $term_id_count-1) {
				$term_id_list = $term_id_list.", ";
				$counter++;
			}
		}
	}
	
	$query = "UPDATE ".$wp_settings_array['data'].".wp_term_taxonomy ".
										"SET taxonomy='category' WHERE term_id IN (".$term_id_list.");";
	
	// Update category counts.
	$query = $query." UPDATE ".$wp_settings_array['data'].".wp_term_taxonomy tt ".
										"SET count = ( ".
											"SELECT COUNT(tr.object_id) ".
											"FROM ".$wp_settings_array['data'].".wp_term_relationships tr ".
											"WHERE tr.term_taxonomy_id = tt.term_taxonomy_id);";
											
	return $query;
}

/*
 *
 */
function buildQuerySetDefaultCategory($wp_settings_array, $term_id) {
	$query = "UPDATE ".$wp_settings_array['data'].".wp_options SET option_value='$term_id' WHERE option_name='default_category';";

	// Make sure the selection is set as a category 
	// It might already have been done by buildQuerySetCategories but do it anyway
	$query = $query." UPDATE ".$wp_settings_array['data'].".wp_term_taxonomy SET taxonomy='category' WHERE term_id=$term_id;";
	
	return $query;
}


/*
"QUERY_MIGRATE_POST_NAME", "UPDATE ".$wp_settings_array['data'].".wp_posts ".
				"SET post_name = ".
				"REVERSE(SUBSTRING(REVERSE(post_name),1,LOCATE('/',REVERSE(post_name))-1));"
*/


// Queries that get data from the database
function runFetchFromDatabase($database_settings_array, $query, &$errors) {
	$result = array();
	try {	
		$dsn = "mysql:host=".$database_settings_array['host'].";dbname=".$database_settings_array['data'];
		$conn = new PDO($dsn, $database_settings_array['user'], $database_settings_array['pass']);
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$stmt = $conn->prepare($query);
		$stmt->execute();
		$result = $stmt->fetchAll();

		  if ( count($result) ==0 ) { 
		    //$errors.= "No rows returned.";
			error_log($query, 0);
			error_log("No rows returned", 0);		
		  }		
	} catch(PDOException $e) {
		$errors = $errors."<br />".
					"<em>Query:</em> ".$query."<br />".
					"<strong>Error: ". $e->getMessage()."</strong><br />";
	}
	return $result;
}

// Queries that alter the database but don't need result
function runAlterDatabase($database_settings_array, $query, &$errors) {
	$row_count = 0;

	try {	
		$dsn = "mysql:host=".$database_settings_array['host'].";dbname=".$database_settings_array['data'];
		$conn = new PDO($dsn, $database_settings_array['user'], $database_settings_array['pass']);
		$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$stmt = $conn->prepare($query);
		$stmt->execute();
		$row_count = $stmt->rowCount();
	} catch(PDOException $e) {
		$errors = $errors."<br />".
					"<em>Query:</em> ".$query."<br />".
					"<strong>Error: ". $e->getMessage()."</strong><br />";
					
		error_log("runAlterDatabase(): dsn: $dsn", 0);
		error_log("runAlterDatabase(): query: $query", 0);
		error_log("runAlterDatabase(): Error: $errors", 0);			
	}
		
	return $row_count;
}


// Tests a database connection
function testDatabaseConnection($database_settings_array, &$errors) {
	$success = false;
	try{
    	$conn = new pdo( "mysql:host=".$database_settings_array['host'].";dbname=".$database_settings_array['data'],
                    	$database_settings_array['user'],
                    	$database_settings_array['pass'],
                    	array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
		if ($conn) {
			$success = true;
		}
	}
	catch(PDOException $e){
		$errors = $errors."<strong>Error:</strong> ". $e->getMessage();
	}
	return $success;
}
?>
