<?php
// Custom WP API endpoint
function tsd_push_notification_enable_api() {
	// Ref: https://developer.wordpress.org/rest-api/extending-the-rest-api/adding-custom-endpoints/

	// Create json-api endpoint
	add_action('rest_api_init', function () {
		// Match "/push-notification/users/{token}"
		// Ref: https://restfulapi.net/rest-put-vs-post/
		register_rest_route('tsd/v1', '/push-notification/users/(?P<token>(.+))', [
			'methods' => 'PUT',
			'callback' => 'tsd_push_notification_update_user',
			'permission_callback' => function (WP_REST_Request $request) {
				return true;
			}
		]);
	});

	function tsd_push_notification_update_user( $request ) {
		$user_token = $request[ "token" ];
		$user_subscribing = $request[ "subscribing" ];

		$list_ids = [];
		foreach ( $user_subscribing[ "list" ] as $each_list_name ) {
			$list_ids[] = tsd_pn_get_sub_list_id_from_name( $each_list_name );
		}
		$user_subscribing[ "list" ] = $list_ids;

		$this_user = get_posts( [
			'post_type' => 'tsd_pn_receiver',
			'name' => $user_token,
		] );
		$post_id = -1;
		if ( empty( $this_user ) ) {
			$post_id = tsd_create_new_pn_receiver( $user_token );
		} else if ( count( $this_user ) == 1 ) {
			$post_id = $this_user[0]->ID;
		} else {
			return new WP_Error( 'tsd_pn_error_duplicate_pn_receiver', 'There are more than one PN Receivers with the same token!', [ 'status' => 500 ] );
		}

		if ( $post_id === -1 ) {
			return new WP_Error( 'tsd_pn_unknown_error', 'Unknown error!', [ 'status' => 500 ] );
		}

		foreach ( tsd_pn_get_subscription_types() as $each_type ) {
			tsd_pn_sub_update_subscription( $post_id, $each_type, $user_subscribing[ $each_type ] );
		}
		return [];
	}

	function tsd_create_new_pn_receiver( $token ) {
		// insert the post and set the category
		$post_id = wp_insert_post( [
			'post_type' => 'tsd_pn_receiver',
			'post_title' => $token,
			'post_status' => "publish",
		] );
		return $post_id;
	}
}
add_action('init', 'tsd_push_notification_enable_api');
