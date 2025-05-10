<?php

namespace Bulk\Page\Maker\Api;

class BPM_REST_Controller extends \WP_REST_Controller {
	/**
	 * The namespace of this controller's route.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $namespace = 'bpm/v1';

	/**
	 * Check permission for settings
	 *
	 * @since 2.0.0
	 *
	 * @param \WP_REST_Request $request
	 *
	 * @return bool
	 */
    public function permission_check() {
        return current_user_can( apply_filters( 'bpmaker_rest_permission_check', 'edit_posts' ) );
    }
}
