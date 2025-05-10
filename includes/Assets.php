<?php

namespace Bulk\Page\Maker;

/**
 * the class to handle all JS and CSS files
 *
 * @package default
 * @author
 **/
class Assets {

	/**
	 * the class constructor
	 *
	 * @return void
	 * @author
	 **/
	public function __construct() {
		add_action( 'admin_enqueue_scripts', [$this, 'enqueue_bpm_assets'] );
	}

	/**
	 * registering all assets to be added later
	 * @return void
	 */
	function enqueue_bpm_assets($hook) {
		// Only load React scripts on our plugin page
		$screen = get_current_screen();
		if ( $screen && strpos( $screen->id, 'bulk-page-maker' ) !== false ) {
			// Enqueue the compiled React script
			if ( file_exists( BPM_PATH . '/build/index.asset.php' ) ) {
				$dependencies = require_once BPM_PATH . '/build/index.asset.php';

				wp_enqueue_script(
					'bpm-react-app',
					BPM_URL . '/build/index.js',
					$dependencies['dependencies'],
					$dependencies['version'],
					true
				);

				wp_enqueue_style(
					'bpm-react-styles',
					BPM_URL . '/build/index.css',
					[],
					filemtime( BPM_PATH . '/build/index.css' )
				);

				// Add data to be available in React
				wp_localize_script(
					'bpm-react-app',
					'bpmData',
					[
                        'nonce'         => wp_create_nonce( 'wp_rest' ),
                        'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
                        'restUrl'       => esc_url_raw( rest_url() ),
                        'pluginName'    => 'Bulk Page Maker Light',
                        'pluginVersion' => BPM_VERSION,
					]
				);
			}
		}
	}
}