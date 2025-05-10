<?php

namespace Bulk\Page\Maker;

use Bulk\Page\Maker\Traits\ContainerTrait;
use Bulk\Page\Maker\Api\Post;

class Api {
	use ContainerTrait;

	/**
	 * Api constructor.
	 */
	public function __construct() {
        $this->container['post'] = new Post();

		add_action( 'rest_api_init', [ $this, 'init_api' ] );
	}

	/**
	 * Initialize API
	 *
	 * @since 2.0.0
	 *
	 * @return void
	 */
	public function init_api() {
		foreach ( $this->container as $class ) {
			$object = new $class();
			$object->register_routes();
		}
	}
}
