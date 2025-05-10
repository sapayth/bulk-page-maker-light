<?php

namespace Bulk\Page\Maker;

/**
 * 
 */
class Installer {

	/**
	 * run the installer
	 * @return void
	 */
	public function run() {
		$this->add_version();
		$this->create_tables();
	}

	/**
	 * adding the version in db
	 * @return void
	 */
	public function add_version() {
		// check if the plugin is installed before
        $installed = get_option( 'bpm_installed' );

        if( !$installed ) {
            // if not installed before, save the activation time in db
            update_option( 'BPM_INSTALLED', time() );
        }
        // save the version in db
        update_option( 'BPM_VERSION', BPM_VERSION );
	}

	/**
	 * create necessary table
	 * @return void
	 */
	public function create_tables() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();


		$schema = "CREATE TABLE IF NOT EXISTS `{$wpdb->prefix}bpm_pages` ( 
			`id` INT(11) unsigned NOT NULL AUTO_INCREMENT ,
			`page_id` BIGINT(20) NOT NULL ,
			`created_by` BIGINT(20) NOT NULL ,
			`created_at` DATETIME NOT NULL ,
			PRIMARY KEY (`id`)
			) $charset_collate";

		if( ! function_exists( 'dbDelta' ) ) {
			require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		}

		dbDelta( $schema );
	}
}
