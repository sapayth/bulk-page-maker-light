<?php

namespace Bulk\Page\Maker\Api;

use WP_REST_Server;

if ( ! function_exists( 'bpmaker_insert_pages_info' ) ) {
	require_once dirname( __DIR__ ) . '/functions.php';
}

class Post extends BPM_REST_Controller {
	/**
	 * Route name
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $base = 'posts';

	/**
	 * Register routes
	 *
	 * @since 1.0.0
	 *
	 * @return void
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->base . '/bulk',
			[
				[
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => [ $this, 'create_bulk_posts' ],
					'permission_callback' => [ $this, 'permission_check' ],
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				],
			]
		);
	}

	/**
	 * Handle bulk post creation
	 *
	 * @param \WP_REST_Request $request
	 * @return \WP_REST_Response
	 */
	public function create_bulk_posts( $request ) {
		$params = $request->get_json_params();

        $post_type      = isset( $params['post_type'] ) ? sanitize_key( $params['post_type'] ) : 'post';
        $post_status    = isset( $params['post_status'] ) ? sanitize_key( $params['post_status'] ) : 'publish';
        $comment_status = isset( $params['comment_status'] ) ? sanitize_key( $params['comment_status'] ) : 'closed';
        $post_parent    = isset( $params['post_parent'] ) ? intval( $params['post_parent'] ) : 0;
        $post_number    = isset( $params['post_number'] ) ? intval( $params['post_number'] ) : 1;
        $post_titles    = isset( $params['post_titles'] ) && is_array( $params['post_titles'] ) ? array_map(
            'sanitize_text_field', $params['post_titles']
        ) : [];
        $post_contents  = isset( $params['post_contents'] ) && is_array( $params['post_contents'] ) ? array_map(
            'wp_kses_post', $params['post_contents']
        ) : [];

		$results = [];
		$errors = [];

		if ( $post_number < 1 ) {
			return new \WP_REST_Response( [ 'message' => __( 'Number of posts must be at least 1', 'bpm-light' ) ], 400 );
		}

		for ( $i = 0; $i < $post_number; $i++ ) {
			// randomize title and content
            $title   = isset( $post_titles[ $i ] ) ? $post_titles[ $i ] : $this->randomize_title();
            $content = isset( $post_contents[ $i ] ) ? $post_contents[ $i ] : $this->randomize_content();

			$postarr = [
				'post_type'      => $post_type,
				'post_status'    => $post_status,
				'comment_status' => $comment_status,
				'post_parent'    => $post_parent,
				'post_title'     => $title,
				'post_content'   => $content,
			];

			$post_id = wp_insert_post( $postarr, true );
			if ( is_wp_error( $post_id ) ) {
				$errors[] = [ 'title' => $title, 'error' => $post_id->get_error_message() ];
			} else {
				$results[] = $post_id;
				// Insert into bpm_pages table
				$insert_result = bpmaker_insert_pages_info([
					'page_id' => $post_id,
				]);
				if ( is_wp_error( $insert_result ) ) {
					$errors[] = [ 'title' => $title, 'error' => $insert_result->get_error_message() ];
				}
			}
		}

		$response = [
			'created' => $results,
			'errors'  => $errors,
		];

		return new \WP_REST_Response( $response, empty( $errors ) ? 201 : 207 );
	}

	private function randomize_title() {
		$adjectives = ['Amazing', 'Incredible', 'Essential', 'Ultimate', 'Hidden', 'Simple', 'Creative', 'Powerful', 'Effective', 'Practical'];
		$nouns = ['Guide', 'Tips', 'Secrets', 'Ideas', 'Tricks', 'Strategies', 'Ways', 'Steps', 'Lessons', 'Facts'];
		$verbs = ['Boost', 'Improve', 'Master', 'Learn', 'Discover', 'Understand', 'Create', 'Build', 'Explore', 'Optimize'];
		$topics = ['Productivity', 'Marketing', 'Writing', 'Design', 'Success', 'Technology', 'Health', 'Finance', 'Coding', 'Growth'];

		$structures = [
			// Templates of different word counts
			['verb', 'your', 'adjective', 'noun'], // e.g., Boost Your Creative Ideas
			['the', 'adjective', 'noun', 'for', 'topic'], // The Ultimate Guide for Marketing
			['how', 'to', 'verb', 'your', 'noun'], // How to Improve Your Skills
			['x', 'adjective', 'nouns', 'to', 'verb'], // 10 Simple Tricks to Master
			['why', 'topic', 'needs', 'adjective', 'nouns'], // Why Marketing Needs Creative Ideas
			['noun', 'that', 'verb', 'topic'], // Tips That Boost Productivity
		];

		// Randomly pick a structure
		$template = $structures[array_rand($structures)];

		// Replace keywords with random words
		$title = array_map(function($word) use ($adjectives, $nouns, $verbs, $topics) {
			switch ($word) {
				case 'adjective': return $adjectives[array_rand($adjectives)];
				case 'noun': return $nouns[array_rand($nouns)];
				case 'nouns': return $nouns[array_rand($nouns)] . 's';
				case 'verb': return $verbs[array_rand($verbs)];
				case 'topic': return $topics[array_rand($topics)];
				case 'x': return rand(4, 12); // Number
				default: return $word;
			}
		}, $template);

		// Capitalize the first letter of the sentence
		$titleStr = ucfirst(implode(' ', $title));

		return $titleStr;
	}

	private function randomize_content() {
		$lorem = [
			'Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.',
			'Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.',
			'Duis aute irure dolor in reprehenderit in voluptate velit esse cillum dolore eu fugiat nulla pariatur.',
			'Excepteur sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit anim id est laborum.',
			'Lorem ipsum dolor sit amet, consectetur adipiscing elit.',
			'Adipiscing sint occaecat cupidatat non proident, sunt in culpa qui officia deserunt mollit',
		];
		$paragraphs = rand(3, 8);
		$content = '';
		// randomly pick 3 to 8 paragraphs. make it look like a wordpress post content by adding p and h tags
		for ( $i = 0; $i < $paragraphs; $i++ ) {
			$content .= '<p>' . $lorem[array_rand($lorem)] . '</p>';
		}

        error_log( print_r( $content, true ) );

		return $content;
	}
}
