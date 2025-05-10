<?php

namespace Bulk\Page\Maker\Admin;

/**
 * The Menu holder class
 */
class Menu {
	
	public $bulkpage;

	function __construct( $bulkpage ) {
		$this->bulkpage = $bulkpage;
		add_action( 'admin_menu', [ $this, 'admin_menu' ] );
	}

	/**
	 * register admin menu
	 * @return void
	 */
	public function admin_menu() {
		$parent_slug = 'bulk-page-maker';
		$capability  = 'manage_options';
		
		add_menu_page( __( 'Bulk Page Maker', 'bpm-light' ), __( 'Bulk Page Maker', 'bpm-light' ), $capability, $parent_slug, [ $this->bulkpage, 'plugin_page' ], 'dashicons-images-alt' );
		add_submenu_page( $parent_slug, __( 'All Pages', 'bpm-light' ), __( 'Add New Pages', 'bpm-light' ), $capability, $parent_slug, [ $this->bulkpage, 'plugin_page' ] );
	}
}