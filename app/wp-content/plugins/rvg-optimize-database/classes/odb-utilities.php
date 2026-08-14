<?php
/************************************************************************************************
 *
 *	UTILITIES
 *
 ************************************************************************************************/
?>
<?php
class ODB_Utilities {
	/********************************************************************************************
	 *	CONSTRUCTOR
	 ********************************************************************************************/
    function __construct() {
	} // __construct()


	/********************************************************************************************
	 *	GET THE (FOR REVISIONS) RELEVANT POST TYPES, INCLUDING CUSTOM POST TYPES (from v4.4)
	 ********************************************************************************************/
	function odb_get_relevant_post_types() {
		$relevant_pts = array();
		$posttypes    = get_post_types();
		foreach ($posttypes as $posttype) {
			// SKIP THE DEFAULT POST TYPES (EXCEPT FOR 'post' AND 'page')
			if ($posttype != 'attachment' &&
					$posttype != 'revision' &&
					$posttype != 'nav_menu_item' &&
					$posttype != 'custom_css' &&
					$posttype != 'customize_changeset' &&
					// v4.4.2
					$posttype != 'oembed_cache') {
				array_push($relevant_pts, $posttype);
			}
		} // foreach ($posttypes as $posttype)

		return $relevant_pts;
	} // odb_get_relevant_post_types()


	/********************************************************************************************
	 *	FORMAT SIZES FROM BYTES TO KB OR MB
	 ********************************************************************************************/
	function odb_format_size($size, $precision=1) {
		if($size > 1024*1024) return (round($size/(1024*1024),$precision)).' MB';

		return (round($size/1024,$precision)).' KB';
	} // odb_format_size()


	/********************************************************************************************
	 *	CALCULATE THE SIZE OF THE WORDPRESS DATABASE (IN BYTES)
	 ********************************************************************************************/
	function odb_get_db_size() {
		global $wpdb;

		$sql = $wpdb->prepare("
		  SELECT SUM(data_length + index_length) AS size
			FROM information_schema.TABLES
		   WHERE table_schema = %s
		GROUP BY table_schema
		", DB_NAME);

		$res = $wpdb->get_results($sql);

		return $res[0]->size;
	} // odb_get_db_size()


	/********************************************************************************************
	 *	PARSE A TIMESTAMP - v4.6
	 ********************************************************************************************/
	function odb_parse_timestamp($timestamp) {
		$d = substr($timestamp, 4, 2).'/'.substr($timestamp, 6, 2).'/'.substr($timestamp, 0, 4);
		$d .= ' ' . substr($timestamp, 8, 2).':'.substr($timestamp, 10, 2).':'.substr($timestamp, 12, 2);
		return $d;
	} // odb_parse_timestamp($timestamp)


	/********************************************************************************************
	 *	GET DATABASE TABLES
	 ********************************************************************************************/
	function odb_get_tables() {
		global $wpdb;

		// GET THE DATABASE BASE TABLES
		return $wpdb->get_results("
			SHOW FULL TABLES
			WHERE table_type = 'BASE TABLE'
		", ARRAY_N);
	} // odb_get_tables()

	/**
	 * Key sanitization. Matches sanitize_key() but does not strtolower.
	 *
	 * @param string $key
	 * @return string|string[]|null
	 */
	function odb_sanitize_key( $key ) {
		$key = (string) $key;

		return preg_replace( '/[^a-zA-Z0-9_\-]/', '', $key );
	}
} // ODB_Utilities