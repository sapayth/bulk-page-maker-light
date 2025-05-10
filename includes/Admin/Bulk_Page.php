<?php

namespace Bulk\Page\Maker\Admin;

/**
 * the Bulk_Page Class
 */
class Bulk_Page {

	public $errors = [];

	/**
	 * [plugin_page description]
	 * @return [type] [description]
	 */
	public function plugin_page() {

		$action = isset( $_GET['action'] ) ? sanitize_key( $_GET['action'] ) : 'list';

		switch ( $action ) {
			case 'new':
				$template = __DIR__ . '/views/bulk-page-new.php';
				break;

			default:
				$template = __DIR__ . '/views/bulk-page-list.php';
				break;
		}

		if( file_exists( $template ) ) {
			include $template;
		}
	}

	/**
	 * handle add new pages form
	 * @return void
	 */
	public function form_handler() {
		$pages              = '';
		$page_content 		= '';
		$post_types_arr     = [ 'page', 'post' ];
		$status_arr         = [ 'publish', 'pending', 'draft' ];
		$comment_status_arr = [ 'closed', 'open' ];

		if( ! isset( $_POST['btn_add_pages'] ) ) {
			return;
		}

		if( ! wp_verify_nonce( $_POST['_wpnonce'], 'bpm-nonce' ) ) {
			wp_die( 'Request Unauthorized' );
		}

		if( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Access Denied' );
		}

		if( empty( $_POST['txt_pages_list'] ) ) {
			$this->errors['page_names'] = __( 'Please provide at least one page/post name', 'bpm-light' );
		} else {
			$pages = sanitize_textarea_field( $_POST['txt_pages_list'] );
		}

		$pages_arr      = array_map( 'trim', explode( ',', $pages ) );
		$page_content   = filter_input( INPUT_POST, 'cmb_page_content', FILTER_SANITIZE_SPECIAL_CHARS );
		$page_content   = htmlspecialchars_decode( $page_content );
		$post_type      = isset( $_POST['cmb_post_type'] ) ? sanitize_text_field( $_POST['cmb_post_type'] ) : 'page';
		$page_status    = isset( $_POST['cmb_page_status'] ) ? sanitize_text_field( $_POST['cmb_page_status'] ) : 'draft';
		$comment_status = isset( $_POST['cmb_comment_status'] ) ? sanitize_text_field( $_POST['cmb_comment_status'] ) : 'closed';
		$post_parent    = isset( $_POST['page_id'] ) ? sanitize_text_field( $_POST['page_id'] ) : '';

		// if input is not in our list
		if ( ! in_array( $post_type , $post_types_arr) ) {
			wp_die( 'Request Unauthorized' );
		}

		// if input is not in our list
		if ( ! in_array( $page_status , $status_arr) ) {
			wp_die( 'Request Unauthorized' );
		}

		// if input is not in our list
		if ( ! in_array( $comment_status , $comment_status_arr) ) {
			wp_die( 'Request Unauthorized' );
		}

		if( ! empty( $this->errors ) ) {
			return;
		}

		for( $i = 0; $i < count( $pages_arr ); $i++ ) {
			$postarr = [
				'post_title'     => $pages_arr[ $i ],
				'post_status'    => $page_status,
				'post_type'      => $post_type,
				'comment_status' => $comment_status,
				'ping_status'    => $comment_status,
				'post_parent'    => $post_parent,
				'post_content'   => $page_content,
		    ];

			$insert_id = wp_insert_post( $postarr );
			$second_insert_id = bpmaker_insert_pages_info(
				[
					'page_id'    => $insert_id,
				]
			);

			if( is_wp_error( $insert_id ) ) {
				wp_die( $insert_id->get_error_message() );
			}

			if( is_wp_error( $second_insert_id ) ) {
				wp_die( $insert_id->get_error_message() );
			}
		}

		$redirected_to = admin_url( 'admin.php?page=bulk-page-maker&inserted=true', 'admin' );

		wp_redirect( $redirected_to );
		exit;
	}

	public function delete_page() {
        if( ! wp_verify_nonce( $_GET['_wpnonce'], 'bpm-delete-action' ) ) {
			wp_die( 'Request Unauthorized' );
		}

		if( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Access Denied' );
		}

        $id = isset( $_REQUEST['id'] ) ? intval( $_REQUEST['id'] ) : 0;

        if ( bpmaker_delete_page( $id ) ) {
            $redirected_to = admin_url( 'admin.php?page=bulk-page-maker&page-deleted=true' );
        } else {
            $redirected_to = admin_url( 'admin.php?page=bulk-page-maker&page-deleted=false' );
        }

        wp_redirect( $redirected_to );

        exit;
    }

	/**
	 * Process AJAX request from React app
	 *
	 * @return void
	 */
	public function ajax_create_bulk_pages() {
		// Verify nonce
		if (!check_ajax_referer('wp_rest', 'nonce', false)) {
			wp_send_json_error([
				'message' => __('Security check failed. Please refresh the page and try again.', 'bpm-light')
			]);
			return;
		}

		// Check user capabilities
		if (!current_user_can('publish_pages')) {
			wp_send_json_error([
				'message' => __('You do not have permission to create pages.', 'bpm-light')
			]);
			return;
		}

		// Get the form data
		$pages_list = isset($_POST['pages_list']) ? sanitize_text_field($_POST['pages_list']) : '';
		$page_content = isset($_POST['page_content']) ? wp_kses_post($_POST['page_content']) : '';
		$post_type = isset($_POST['post_type']) ? sanitize_text_field($_POST['post_type']) : 'page';
		$page_status = isset($_POST['page_status']) ? sanitize_text_field($_POST['page_status']) : 'publish';
		$comment_status = isset($_POST['comment_status']) ? sanitize_text_field($_POST['comment_status']) : 'closed';

		// Validate pages list
		if (empty($pages_list)) {
			wp_send_json_error([
				'message' => __('Please enter a list of pages/posts.', 'bpm-light')
			]);
			return;
		}

		// Validate post type
		if (!in_array($post_type, ['page', 'post'])) {
			wp_send_json_error([
				'message' => __('Invalid post type.', 'bpm-light')
			]);
			return;
		}

		// Validate post status
		if (!in_array($page_status, ['publish', 'draft', 'pending'])) {
			wp_send_json_error([
				'message' => __('Invalid post status.', 'bpm-light')
			]);
			return;
		}

		// Validate comment status
		if (!in_array($comment_status, ['open', 'closed'])) {
			wp_send_json_error([
				'message' => __('Invalid comment status.', 'bpm-light')
			]);
			return;
		}

		// Process the pages
		$page_names = explode(',', $pages_list);
		$page_names = array_map('trim', $page_names);
		$page_names = array_filter($page_names); // Remove empty items

		if (empty($page_names)) {
			wp_send_json_error([
				'message' => __('Please enter valid page names.', 'bpm-light')
			]);
			return;
		}

		$created_pages = [];
		$failed_pages = [];

		foreach ($page_names as $page_name) {
			$page_args = [
				'post_type' => $post_type,
				'post_title' => $page_name,
				'post_content' => $page_content,
				'post_status' => $page_status,
				'comment_status' => $comment_status,
			];

			$page_id = wp_insert_post($page_args);

			if (!is_wp_error($page_id) && $page_id > 0) {
				$created_pages[] = $page_name;
			} else {
				$failed_pages[] = $page_name;
			}
		}

		// Update transient for all pages
		delete_transient('bpmaker_all_pages');
		delete_transient('bpmaker_pages_count');

		// Send response
		if (count($created_pages) > 0) {
			wp_send_json_success([
				'message' => sprintf(
					__('%d %s created successfully: %s', 'bpm-light'),
					count($created_pages),
					$post_type === 'page' ? 'pages' : 'posts',
					implode(', ', $created_pages)
				),
				'created' => $created_pages,
				'failed' => $failed_pages,
				'clear_form' => count($failed_pages) === 0, // Clear form if all succeeded
			]);
		} else {
			wp_send_json_error([
				'message' => __('Failed to create pages/posts. Please try again.', 'bpm-light'),
				'failed' => $failed_pages,
			]);
		}

		exit;
	}
}