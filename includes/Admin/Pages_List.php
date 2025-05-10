<?php

namespace Bulk\Page\Maker\Admin;

if( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}


class Pages_List extends \WP_List_Table {
	
	public function __construct() {
		parent::__construct( [
			'plural'   => 'Pages',
			'singular' => 'Page',
			'ajax'     => false,
		] );
	}

	public function get_columns() {
		return [
			'cb'         => '<input type="checkbox" />',
			'post_title' => __( 'Title', 'bulk-page-maker-light' ),
			'created_by' => __( 'Author', 'bulk-page-maker-light' ),
			'post_type'  => __( 'Type', 'bulk-page-maker-light' ),
			'post_date'  => __( 'Date', 'bulk-page-maker-light' ),

		];
	}

	protected function column_default( $item, $column_name ) {
		switch( $column_name ) {
			case 'created_by':
				return get_the_author_meta( 'display_name', $item->created_by );
			
			case 'post_date':
				$mysqldate = strtotime( $item->$column_name );
				return date( 'Y/m/d H:i A', $mysqldate);

			default:
				return isset( $item->$column_name ) ? esc_html( $item->$column_name ) : '';
		}
	}

	public function column_post_title( $item ) {
		$actions = [];

		$actions['edit']   = sprintf(
			'<a href="%s" title="%s">%s</a>',
			esc_url( admin_url( '/post.php?post=' . $item->page_id . '&action=edit' ) ),
			__( 'Edit', 'bulk-page-maker-light' ),
			__( 'Edit', 'bulk-page-maker-light' )
		);

        $actions['delete'] = sprintf(
        	'<a href="%s" class="submitdelete" onclick="return confirm(\'Are you sure?\');" title="%s">%s</a>',
        	wp_nonce_url( admin_url( 'admin-post.php?page=bulk-page-maker&action=bpm-delete-action&id=' . $item->page_id ), 'bpm-delete-action' ),
        	__( 'Delete', 'bulk-page-maker-light' ),
        	__( 'Delete', 'bulk-page-maker-light' )
        );

		return sprintf(
			'<a href="%1$s"><strong>%2$s</strong></a> %3$s',
			esc_url( admin_url( '/post.php?post=' . $item->page_id . '&action=edit' ) ),
			$item->post_title,
			$this->row_actions( $actions )
		);
	}

	protected function column_cb($item) {
		return sprintf(
			'<input type="checkbox" name="page_id[]" value="%d">',
			$item->id
		);
	}

	/**
     * Get sortable columns
     *
     * @return array
     */
    function get_sortable_columns() {
        $sortable_columns = [
			'post_date'  => [ 'date', true ],
			'post_type'  => [ 'post_type', true ],
			'post_title' => [ 'post_title', true ]
        ];

        return $sortable_columns;
    }

    /**
	 * Prepares the list of items for displaying.
	 * overridden from WP_List_Table
	 *
	 * @uses WP_List_Table::set_pagination_args()
	 *
	 * @since 3.1.0
	 * @abstract
	*/
	public function prepare_items() {
		$per_page     = 20;
		$current_page = $this->get_pagenum();
		$offset       = ( $current_page - 1 ) * $per_page;
		$column       = $this->get_columns();
		$hidden       = [];
		$sortable     = $this->get_sortable_columns();

		$this->_column_headers = [$column, $hidden, $sortable];

		$args = [
            'number' => $per_page,
            'offset' => $offset,
        ];

        if ( isset( $_REQUEST['orderby'] ) && isset( $_REQUEST['order'] ) ) {
			$allowed_keys = ['orderby', 'order'];
			$orderby      = sanitize_key( $_REQUEST['orderby'] );
			$order        = sanitize_key( $_REQUEST['order'] );

        	if( in_array($orderby, $allowed_keys, true) ) {
        		$args['orderby'] = $orderby;
        	}

        	if( in_array($order, $allowed_keys, true) ) {
        		$args['order']   = $order;
        	}
        }

		$this->items = bpmaker_get_pages( $args );
		$this->set_pagination_args( [
			'total_items' => bpmaker_get_pages_count(),
			'per_page'    => $per_page,
		] );
	}
}