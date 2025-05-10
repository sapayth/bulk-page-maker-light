<?php

/**
 * insert newly generated pages info
 * @param  array  $args [description]
 * @return int
 */
function bpmaker_insert_pages_info( $args = [] ) {
	global $wpdb;

	$transient_posts = 'bpmaker_all_pages';
	$transient_count = 'bpmaker_pages_count';

	$defaults = [
		'created_by' => get_current_user_id(),
		'created_at' => current_time('mysql'),
	];

	$data = wp_parse_args( $args, $defaults );

	$inserted = $wpdb->insert(
		$wpdb->prefix . 'bpm_pages',
		$data,
		[
			'%s',
			'%s',
		]
	);

	if( ! $inserted ) {
		return new \WP_Error( 'failed-to-insert', __('Failed to insert data', 'bulk-page-maker') );
	}

	// delete releated transient
	if ( false !== get_transient( $transient_count ) ) {
		delete_transient( $transient_count );
	}

	// delete releated transient
	if ( false !== get_transient( $transient_posts ) ) {
		delete_transient( $transient_posts );
	}

	return $wpdb->insert_id;
}

/**
 * return pages
 * @param  array  $args
 * @return array
 */
function bpmaker_get_pages( $args = [] ) {
	global $wpdb;
	$transient = 'bpmaker_all_pages';

	$defaults = [
		'numbers' => 20,
		'offset'  => 0,
		'orderby' => 'created_at',
		'order'   => 'ASC',
	];

	$args = wp_parse_args( $args, $defaults );

	$sql = $wpdb->prepare(
		"SELECT bpm.*, wp.post_title, wp.post_type, wp.post_date
		FROM {$wpdb->prefix}bpm_pages bpm, {$wpdb->prefix}posts wp
		WHERE bpm.page_id = wp.id
		ORDER BY {$args['orderby']} {$args['order']}
		LIMIT %d, %d",
		$args['offset'], $args['numbers']
	);

	// store pages in a transient
	if ( false === ( $items = get_transient( $transient ) ) ) {
		$items = $wpdb->get_results( $sql );
		set_transient( $transient, $items, MONTH_IN_SECONDS );
	}

	return $items;
}

/**
 * get total number of page created
 * @return int
 */
function bpmaker_get_pages_count() {
	global $wpdb;
	$transient = 'bpmaker_pages_count';

	if ( false === ( $count = get_transient( $transient ) ) ) {
		$count = (int) $wpdb->get_var( "SELECT count(id) FROM {$wpdb->prefix}bpm_pages" );
		set_transient( $transient, $count, MONTH_IN_SECONDS );
	}

	return $count;
}

/**
 * get selected page
 * @param  int $id [description]
 * @return object
 */
function bpmaker_get_page( $id ) {
	global $wpdb;

	return $wpdb->get_row(
		$wpdb->prepare( "SELECT * FROM {$wpdb->prefix}bpm_pages WHERE ID = %d", $id )
	);
}

/**
 * delete page
 * @param  int $post_id
 * @return int|bool
 */
function bpmaker_delete_page( $post_id ) {
	global $wpdb;
	$transient_posts = 'bpmaker_all_pages';
	$transient_count = 'bpmaker_pages_count';

	// delete releated transient
	if ( false !== get_transient( $transient_count ) ) {
		delete_transient( $transient_count );
	}

	// delete releated transient
	if ( false !== get_transient( $transient_posts ) ) {
		delete_transient( $transient_posts );
	}

	// delete from posts table
	$wpdb->delete(
		$wpdb->prefix . 'posts',
		[ 'id' => $post_id ],
		[ '%d' ]
	);
	
	// delete from plugin table
	return $wpdb->delete(
		$wpdb->prefix . 'bpm_pages',
		[ 'page_id' => $post_id ],
		[ '%d' ]
	);
}