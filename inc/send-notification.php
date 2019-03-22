<?php
// Returns whether the push notification process is successfully completed.
// $manual parameter changes the behavior of no receiver;
// I.e. If it's not manual, no error will be produced when there's no receiver.
function tsd_send_expo_push_notification( $receiver_pn_users_ids, $title, $body, $data, $manual = false ) {
	// We have to check this first because `'include' => []` below will return all tsd_pn_receiver.
	if ( empty( $receiver_pn_users_ids ) ) {
		if ( $manual ) {
			tsd_pn_set_admin_notice( 'fail', "No receiver!" );
			return false;
		} else {
			// Do nothing
			return true;
		}
	}
	$receiver_pn_users = get_posts( [
		'post_type' => 'tsd_pn_receiver',
		'include' => $receiver_pn_users_ids,
	] );
	if ( empty( $receiver_pn_users ) ) {
		if ( $manual ) {
			tsd_pn_set_admin_notice( 'fail', "No receiver!\nThis might be a bug. Please note down what you are doing and contact the Tech Team!" );
			return false;
		} else {
			// Do nothing
			return true;
		}
	}

	if ( empty( $title ) ) {
		tsd_pn_set_admin_notice( 'fail', "Missing title!" );
		return false;
	}
	// TODO: Check char. limit for title and body

	$message_body = [
		"title" => $title,
		"body" => $body,
	];
	if ( ! empty( $data ) ) {
		$message_body[ "data" ] = $data;
	}

	$all_messages = [];
	foreach ( $receiver_pn_users as $each_user ) {
		$each_message = $message_body;
		$each_message[ "to" ] = $each_user->post_title;	// post_title is the user token
		$all_messages[] = $each_message;
	}

	// Because Expo HTTP request can only consist of "an array of up to 100 messages".
	$all_messages_chucked = array_chunk( $all_messages, 99 );
	$responses = [];
	foreach ( $all_messages_chucked as $each_messages_chucked ) {
		// Ref: https://docs.expo.io/versions/latest/guides/push-notifications/#http2-api
		$responses[] = wp_safe_remote_post( "https://exp.host/--/api/v2/push/send", [
			'method' => 'POST',
			'timeout' => 15,
			'httpversion' => '2.0',
			'headers' => [ "content-type" => "application/json" ],
			'body' => json_encode( $all_messages ),
		] );
	}


	$all_responses_success = true;
	$admin_notice_message = "";
	foreach ( $responses as $response ) {
		if ( is_wp_error( $response ) ) {
			$all_responses_success = false;
			$error_message = $response->get_error_message();
			$admin_notice_message .= json_encode( $error_message ) . "\n";
			continue;
		}

		$decoded_body = json_decode( $response[ "body" ], true );
		if ( $response[ "response" ][ "code" ] != 200 ) {
			$all_responses_success = false;
			$admin_notice_message .= json_encode( $decoded_body ) . "\n";
			continue;
		}

		$admin_notice_message .= json_encode( $decoded_body ) . "\n";
	}

	$admin_notice_type = "success";
	if ( ! $all_responses_success ) {
		$admin_notice_type = "fail";
	}
	tsd_pn_set_admin_notice( $admin_notice_type, "Response: \n" . $admin_notice_message . "\nYour message: \n" . json_encode( $message_body ) );

	return $all_responses_success;
}
