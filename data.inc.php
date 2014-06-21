<?php
/*******************************************************************************
 *
 * The queries for migrating FROM Drupal TO WordPress are based on a post
 * by Room 34 in http://blog.room34.com/archives/4530
 * Please visit the site for additional credits
 *
 * I've copied their comments verbatim. Where helpful, I've added additional 
 * explanation on changes and tagged them with 'ACCL' (Another Cup of Coffee Limited)
 *
 *******************************************************************************/ 
/*
print '<p>In data.inc</p>';
print "<pre style='font-size:small'>";
print_r($_POST);
print '</pre>';
print '<hr />';
*/

define("D2W_VERSION", "0.4");

/*** DB details ***/
//	Wordpress
$wp_settings_array['host'] = isset( $_POST[ 'wp_host' ] ) ? stripcslashes( $_POST[ 'wp_host' ] ) : 'localhost';			// normally localhost, but not necessarily.
$wp_settings_array['user'] = isset( $_POST[ 'wp_user' ] ) ? stripcslashes( $_POST[ 'wp_user' ] ) : 'username';			// your db userid
$wp_settings_array['pass'] = isset( $_POST[ 'wp_pass' ] ) ? stripcslashes( $_POST[ 'wp_pass' ] ) : 'password';			// your db password
$wp_settings_array['data'] = isset( $_POST[ 'wp_data' ] ) ? stripcslashes( $_POST[ 'wp_data' ] ) : 'wordpress';			// your database
$wp_settings_array['char'] = isset( $_POST[ 'wp_char' ] ) ? stripcslashes( $_POST[ 'wp_char' ] ) : '';					// your db charset
//	Drupal
$d_settings_array['host'] = $wp_settings_array['host'];			// normally localhost, but not necessarily.
$d_settings_array['user'] = $wp_settings_array['user'];			// your db userid
$d_settings_array['pass'] = $wp_settings_array['pass'];		// your db password
$d_settings_array['data'] = isset( $_POST[ 'd_data' ] ) ? stripcslashes( $_POST[ 'd_data' ] ) : 'drupal';			// your database
$d_settings_array['char'] = isset( $_POST[ 'd_char' ] ) ? stripcslashes( $_POST[ 'd_char' ] ) : '';					// your db charset
/*
$d_settings_array['host'] = isset( $_POST[ 'd_host' ] ) ? stripcslashes( $_POST[ 'd_host' ] ) : '';			// normally localhost, but not necessarily.
$d_settings_array['data'] = isset( $_POST[ 'd_data' ] ) ? stripcslashes( $_POST[ 'd_data' ] ) : '';			// your database
$d_settings_array['user'] = isset( $_POST[ 'd_user' ] ) ? stripcslashes( $_POST[ 'd_user' ] ) : '';			// your db userid
$d_settings_array['pass'] = isset( $_POST[ 'd_pass' ] ) ? stripcslashes( $_POST[ 'd_pass' ] ) : '';			// your db password
$d_settings_array['char'] = isset( $_POST[ 'd_char' ] ) ? stripcslashes( $_POST[ 'd_char' ] ) : '';			// your db charset
*/

/**********************************************************************
 * START: room34.com queries for migrating FROM Drupal TO WordPress
 * 
 */

/* room34.com: Empty previous content from WordPress database. */
define("QUERY_LIST_POSTS", "SELECT * FROM ".$wp_settings_array['data'].".wp_posts;");

/* room34.com: Empty previous content from WordPress database. */
define("QUERY_TRUNCATE_WP_TABLES", "TRUNCATE TABLE ".$wp_settings_array['data'].".wp_comments;".
									"TRUNCATE TABLE ".$wp_settings_array['data'].".wp_links;".
									"TRUNCATE TABLE ".$wp_settings_array['data'].".wp_postmeta;".
									"TRUNCATE TABLE ".$wp_settings_array['data'].".wp_posts;".
									"TRUNCATE TABLE ".$wp_settings_array['data'].".wp_term_relationships;".
									"TRUNCATE TABLE ".$wp_settings_array['data'].".wp_term_taxonomy;".
									"TRUNCATE TABLE ".$wp_settings_array['data'].".wp_terms;");

/* 
 * room34.com: If you're not bringing over multiple Drupal authors, comment out these lines and the other
 * author-related queries near the bottom of the script.
 * This assumes you're keeping the default admin user (user_id = 1) created during installation.
 */
define("QUERY_DELETE_WP_AUTHORS", "DELETE FROM ".
									$wp_settings_array['data'].
									".wp_users WHERE ID > 1; DELETE FROM ".
									$wp_settings_array['data'].
									".wp_usermeta WHERE user_id > 1;");


/* 
 * room34.com: TAGS
 * Using REPLACE prevents script from breaking if Drupal contains duplicate terms.
 */
define("QUERY_CREATE_WP_TAGS", "REPLACE INTO ".$wp_settings_array['data'].".wp_terms ".
									"(term_id, name, slug, term_group) ".
									"SELECT DISTINCT ".
										"d.tid, d.name, REPLACE(LOWER(d.name), ' ', '_'), 0 ".
									"FROM ".$d_settings_array['data'].".term_data d ".
									"WHERE (1);");	// This helps eliminate spam tags from import; uncomment if necessary.
									 				// AND LENGTH(d.name) < 50

/* room34.com: POST/TAG RELATIONSHIPS */
define("QUERY_SET_TERM_RELATIONSHIPS", "INSERT INTO ".$wp_settings_array['data'].".wp_term_relationships (object_id, term_taxonomy_id) ".
										"SELECT DISTINCT nid, tid FROM ".$d_settings_array['data'].".term_node;");

/* room34.com: Update tag counts */
define("QUERY_UPDATE_TAG_COUNTS", "UPDATE ".$wp_settings_array['data'].".wp_term_taxonomy tt ".
										"SET count = ( ".
											"SELECT COUNT(tr.object_id) ".
											"FROM ".$wp_settings_array['data'].".wp_term_relationships tr ".
											"WHERE tr.term_taxonomy_id = tt.term_taxonomy_id);");

/*
 * room34.com: COMMENTS
 * Keeps unapproved comments hidden.
 * Incorporates change noted here: http://www.mikesmullin.com/development/migrate-convert-import-drupal-5-to-wordpress-27/#comment-32169
 *
 * ACCL: Amended to truncate Drupal's homepage field (varchar 255) to fit
 * Wordpress' comment_author_url field (varchar 200)
 *
 */
define("QUERY_HIDE_ADD_COMMENTS", "INSERT INTO ".$wp_settings_array['data'].".wp_comments ".
											"(comment_ID, comment_post_ID, comment_date, comment_content, comment_parent, comment_author, ".
											"comment_author_email, comment_author_url, comment_approved) ".
										"SELECT DISTINCT ".
											"cid, nid, FROM_UNIXTIME(timestamp), comment, pid, name, ".
											"mail, SUBSTRING(homepage,1,200), ((status + 1) % 2) ".
											"FROM ".$d_settings_array['data'].".comments;");

/* room34.com: Update comments count on wp_posts table. */
define("QUERY_UPDATE_COMMENTS_COUNTS", "UPDATE ".$wp_settings_array['data'].".wp_posts ".
											"SET comment_count = ( ".
												"SELECT COUNT(comment_post_id) ".
												"FROM ".$wp_settings_array['data'].".wp_comments ".
												"WHERE ".$wp_settings_array['data'].".wp_posts.id = ".$wp_settings_array['data'].".wp_comments.comment_post_id);");

/* room34.com: Fix images in post content; uncomment if you're moving files from "files" to "wp-content/uploads". */
define("QUERY_UPDATE_FILEPATH", "UPDATE ".$wp_settings_array['data'].".wp_posts SET post_content = REPLACE(post_content, '\"/files/', '\"/wp-content/uploads/');");

/* room34.com: Fix taxonomy; http://www.mikesmullin.com/development/migrate-convert-import-tindrupal6-5-to-wordpress-27/#comment-27140 */
define("QUERY_FIX_TAXONOMY", "UPDATE IGNORE ".$wp_settings_array['data'].".wp_term_relationships, ".$wp_settings_array['data'].".wp_term_taxonomy ".
					"SET ".$wp_settings_array['data'].".wp_term_relationships.term_taxonomy_id = ".$wp_settings_array['data'].".wp_term_taxonomy.term_taxonomy_id ".
					"WHERE ".$wp_settings_array['data'].".wp_term_relationships.term_taxonomy_id = ".$wp_settings_array['data'].".wp_term_taxonomy.term_id;");


/*
 * Stuff below needs more testing and customizatio
 */

/* room34.com: AUTHORS */
define("QUERY_ADD_AUTHORS", "INSERT IGNORE INTO ".$wp_settings_array['data'].".wp_users ".
										"(ID, user_login, user_pass, user_nicename, user_email, ".
										"user_registered, user_activation_key, user_status, display_name) ".
										"SELECT DISTINCT ".
											"u.uid, u.mail, NULL, u.name, u.mail, ".
											"FROM_UNIXTIME(created), '', 0, u.name ".
										"FROM ".$d_settings_array['data'].".users u ".
										"INNER JOIN ".$d_settings_array['data'].".users_roles r ".
											"USING (uid) ".
										"WHERE (1);");	// Uncomment and enter any email addresses you want to exclude below.
														// AND u.mail NOT IN ('test@example.com')
										

/* room34.com: Assign author permissions.
 * Sets all authors to "author" by default; next section can selectively promote individual authors
 *
 * ACCL: Buidling from two separate queries for troubleshooting
 */ 
define("QUERY_ASSIGN_AUTHOR_PERMISSIONS_A", "INSERT IGNORE INTO ".$wp_settings_array['data'].".wp_usermeta (user_id, meta_key, meta_value) ".
										"SELECT DISTINCT ".
											"u.uid, 'wp_capabilities', 'a:1:{s:6:\"author\";s:1:\"1\";}' ".
										"FROM ".$d_settings_array['data'].".users u ".
										"INNER JOIN ".$d_settings_array['data'].".users_roles r ".
											"USING (uid) ".
										"WHERE (1);");	// Uncomment and enter any email addresses you want to exclude below.
 														// AND u.mail NOT IN ('test@example.com')
										
define("QUERY_ASSIGN_AUTHOR_PERMISSIONS_B", "INSERT IGNORE INTO ".$wp_settings_array['data'].".wp_usermeta (user_id, meta_key, meta_value) ".
										"SELECT DISTINCT ".
											"u.uid, 'wp_user_level', '2' ".
										"FROM ".$d_settings_array['data'].".users u ".
										"INNER JOIN ".$d_settings_array['data'].".users_roles r ".
											"USING (uid) ".
										"WHERE (1);");	// Uncomment and enter any email addresses you want to exclude below.
 														// AND u.mail NOT IN ('test@example.com')

define("QUERY_ASSIGN_AUTHOR_PERMISSIONS", QUERY_ASSIGN_AUTHOR_PERMISSIONS_A.QUERY_ASSIGN_AUTHOR_PERMISSIONS_B);


/*
 * room34.com: Change permissions for admins.
 * Add any specific user IDs to IN list to make them administrators.
 * User ID values are carried over from Drupal.
 */
define("QUERY_ASSIGN_ADMIN_PERMISSIONS", "UPDATE ".$wp_settings_array['data'].".wp_usermeta ".
											"SET meta_value = 'a:1:{s:13:\"administrator\";s:1:\"1\";}' ".
											"WHERE user_id IN (1) AND meta_key = 'wp_capabilities'; ".
										"UPDATE ".$wp_settings_array['data'].".wp_usermeta ".
											"SET meta_value = '10' ".
											"WHERE user_id IN (1) AND meta_key = 'wp_user_level';");

/* room34.com: Reassign post authorship. */
define("QUERY_ASSIGN_POST_AUTHORSHIP", "UPDATE ".$wp_settings_array['data'].".wp_posts ".
											"SET post_author = NULL ".
											"WHERE post_author NOT IN (SELECT DISTINCT ID FROM ".$wp_settings_array['data'].".wp_users);");

/*
 * room34.com: VIDEO - READ BELOW AND COMMENT OUT IF NOT APPLICABLE TO YOUR SITE
 * If your Drupal site uses the content_field_video table to store links to YouTube videos,
 * this query will insert the video URLs at the end of all relevant posts.
 * WordPress will automatically convert the video URLs to YouTube embed code.
 */
/*
define("QUERY_ADD_VIDEO_URLS", "UPDATE IGNORE ".$wp_settings_array['data'].".wp_posts p, ".$d_settings_array['data'].".content_field_video v ".
										"SET p.post_content = CONCAT_WS('\n',post_content,v.field_video_embed) ".
										"WHERE p.ID = v.nid;");
*/

/*
 * room34.com: IMAGES - READ BELOW AND COMMENT OUT IF NOT APPLICABLE TO YOUR SITE
 * If your Drupal site uses the content_field_image table to store images associated with posts,
 * but not actually referenced in the content of the posts themselves, this query
 * will insert the images at the top of the post.
 * HTML/CSS NOTE: The code applies a "".$d_settings_array['data']."_image" class to the image and places it inside a <div>
 * with the "".$d_settings_array['data']."_image_wrapper" class. Add CSS to your WordPress theme as appropriate to
 * handle styling of these elements. The <img> tag as written assumes you'll be copying the
 * Drupal "files" directory into the root level of WordPress, NOT placing it inside the
 * "wp-content/uploads" directory. It also relies on a properly formatted <base href="" /> tag.
 * Make changes as necessary before running this script!
 */
/*
define("QUERY_ADD_IMAGES", "UPDATE IGNORE ".$wp_settings_array['data'].".wp_posts p, ".$d_settings_array['data'].".content_field_image i, ".$d_settings_array['data'].".files f ".
										"SET p.post_content = ".
											"CONCAT( ".
												"CONCAT( ".
													"'<div class=\"".$d_settings_array['data']."_image_wrapper\"><img src=\"files/', ".
													"f.filename, ".
													"'\" class=\"".$d_settings_array['data']."_image\" /></div>' ".
												"), ".
												"p.post_content ".
											") ".
										"WHERE p.ID = i.nid ".
										"AND i.field_image_fid = f.fid ".
										"AND ( ".
											"f.filename LIKE '%.jpg' ".
											"OR f.filename LIKE '%.jpeg' ".
											"OR f.filename LIKE '%.png' ".
											"OR f.filename LIKE '%.gif');");
*/

/*
 * room34.com: Fix post_name to remove paths.
 * If applicable; Drupal allows paths (i.e. slashes) in the dst field, but this breaks
 * WordPress URLs. If you have mod_rewrite turned on, stripping out the portion before
 * the final slash will allow old site links to work properly, even if the path before
 * the slash is different!
 */
/*
define("QUERY_FIX_POST_NAME", "UPDATE ".$wp_settings_array['data'].".wp_posts ".
										"SET post_name = ".
								"REVERSE(SUBSTRING(REVERSE(post_name),1,LOCATE('/',REVERSE(post_name))-1));");
*/

/*
 * room34.com: Miscellaneous clean-up.
 * There may be some extraneous blank spaces in your Drupal posts; use these queries
 * or other similar ones to strip out the undesirable tags.
*/
/*
define("QUERY_STRIP_NBSP", "UPDATE ".$wp_settings_array['data'].".wp_posts SET post_content = REPLACE(post_content,'<p>&nbsp;</p>','');");
define("QUERY_STRIP_TAGS_01", "UPDATE ".$wp_settings_array['data'].".wp_posts SET post_content = REPLACE(post_content,'<p class=\"italic\">&nbsp;</p>','');");
*/

/*
 * room34.com: NEW PAGES - READ BELOW AND COMMENT OUT IF NOT APPLICABLE TO YOUR SITE
 * MUST COME LAST IN THE SCRIPT AFTER ALL OTHER QUERIES!
 * If your site will contain new pages, you can set up the basic structure for them here.
 * Once the import is complete, go into the WordPress admin and copy content from the Drupal
 * pages (which are set to "pending" in a query above) into the appropriate new pages.
 */
/*
define("QUERY_SETUP_NEW_PAGE_STRUCTURE", "INSERT INTO ".$wp_settings_array['data'].".wp_posts ".
											"('post_author', 'post_date', 'post_date_gmt', 'post_content', 'post_title', ".
											"'post_excerpt', 'post_status', 'comment_status', 'ping_status', 'post_password', ".
											"'post_name', 'to_ping', 'pinged', 'post_modified', 'post_modified_gmt', ".
											"'post_content_filtered', 'post_parent', 'guid', 'menu_order', 'post_type', ".
											"'post_mime_type', 'comment_count') ".
											"VALUES ".
											"(1, NOW(), NOW(), 'Page content goes here, or leave this value empty.', 'Page Title', ".
											"'', 'publish', 'closed', 'closed', '', ".
											"'slug-goes-here', '', '', NOW(), NOW(), ".
											"'', 0, 'http://full.url.to.page.goes.here', 1, 'page', '', 0);");
*/

/* room34.com: Disable or enable comments */
/*
 UPDATE ".$wp_settings_array['data'].".wp_posts p SET comment_status = 'closed', ping_status = 'closed' WHERE comment_status = 'open';
 UPDATE ".$wp_settings_array['data'].".wp_posts p SET comment_status = 'open', ping_status = 'open' WHERE comment_status = 'closed';
*/

/* 
 * END: room34.com queries for migrating FROM Drupal TO WordPress
 *
 **********************************************************************/


/**********************************************************************
 * Additional work and research by ACCL
 * 
 */

/* Set site name, description and admin email */
define("QUERY_SET_SITE_NAME", "UPDATE ".$wp_settings_array['data'].".wp_options ".
										"SET option_value = ( SELECT value FROM ".$d_settings_array['data'].".variable WHERE name='site_name') ".
										"WHERE option_name = 'blogname';");

define("QUERY_SET_SITE_DESC", "UPDATE ".$wp_settings_array['data'].".wp_options ".
										"SET option_value = ( SELECT value FROM ".$d_settings_array['data'].".variable WHERE name='site_slogan') ".
										"WHERE option_name = 'blogdescription';");

define("QUERY_SET_SITE_EMAIL", "UPDATE ".$wp_settings_array['data'].".wp_options ".
										"SET option_value = ( SELECT value FROM ".$d_settings_array['data'].".variable WHERE name='site_mail') ".
										"WHERE option_name = 'admin_email';");

/******************************
 * Querying Drupal
 *
 */
define("QUERY_DRUPAL_GET_POSTS", "SELECT DISTINCT ".
										"nid, FROM_UNIXTIME(created) post_date, title, type ".
									"FROM ".$d_settings_array['data'].".node;"); // Add more Drupal content types below if applicable.

define("QUERY_DRUPAL_GET_NODE_TYPES", "SELECT DISTINCT type, name, description FROM ".$d_settings_array['data'].".node_type n "); // Add more Drupal content types below if applicable.

define("QUERY_DRUPAL_GET_TERMS", "SELECT DISTINCT tid, name, REPLACE(LOWER(name), ' ', '_') slug, 0 ".
										"FROM ".$d_settings_array['data'].".term_data WHERE (1);");

/******************************
 * Checks for common problems
 *
 */

/*
 * Can't import duplicate terms into the WordPress wp_terms table
 */
define("QUERY_DRUPAL_GET_DUPLICATE_TERMS", "SELECT tid, name, COUNT(*) c FROM ".$d_settings_array['data'].".term_data GROUP BY name HAVING c > 1;");

/*
 * WordPress term name field is set 200 chars but Drupal's is term name is 255 chars
 */
define("QUERY_DRUPAL_TERMS_CHARLENGTH", "SELECT tid, name FROM ".$d_settings_array['data'].".term_data WHERE CHAR_LENGTH(name) > 200;");

/*
 * The node ID in Drupal's node table is used to create the post ID in WordPress' wp_posts table.
 *
 * The post name in WordPress' wp_posts table is created using either
 *   (a) the url alias (dst field) in Drupal's url_alias table OR
 *   (b) the node id (nid) in Drupal's node table IF there is no url alias
 *
 * If there are multiple Drupal aliases with the same node ID, we will end up trying to create multiple entries
 * into the WordPress wp_posts table with the same wp_posts ID. This will cause integrity constraint violation
 * errors since wp_posts ID is a unique primary key.
 *
 * To avoid this error, we need to check for duplicate aliases
 *
 * See buildQueryCreateWPPosts()
 */
define("QUERY_DRUPAL_GET_DUPLICATE_ALIAS", "SELECT pid, src, COUNT(*) c FROM ".$d_settings_array['data'].".url_alias GROUP BY src HAVING c > 1;");

?>

