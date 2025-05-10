<?php

namespace Bulk\Page\Maker;

/**
 * Admin class
 */
class Admin {
	
	/**
	 * the class constructor
	 * @return  void
	 */
	public function __construct() {
		$bulkpage = new Admin\Bulk_Page();

		$this->dispatch_actions( $bulkpage );
		new Admin\Menu( $bulkpage );
	}

	/**
	 * dispatch actions
	 * @param  object $bulkpage
	 * @return  void
	 */
	public function dispatch_actions( $bulkpage ) {
		add_action( 'admin_init', [ $bulkpage, 'form_handler' ] );
		add_action( 'admin_post_bpm-delete-action', [ $bulkpage, 'delete_page' ] );
		
		// Add AJAX handler for React form submission
		add_action( 'wp_ajax_bpm_create_bulk_pages', [ $bulkpage, 'ajax_create_bulk_pages' ] );
	}
}