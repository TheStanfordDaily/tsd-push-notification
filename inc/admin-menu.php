<?php
function tsd_pn_plugin_menu() {
	add_options_page(
		'TSD Push Notification',
		'TSD Push Notification',
		'manage_options',
		'tsd-push-notification.php',
		'tsd_pn_plugin_settings_page'
	);
}
add_action( 'admin_menu', 'tsd_pn_plugin_menu' );

function tsd_pn_plugin_settings_page() {
	if ( isset( $_POST["clear_debug"] ) ) {
		update_option( "tsd_pn_debug_info", [] );
	}
	?>
<div class="wrap">
	<h1>TSD Push Notification Debug</h1>
	<form action="" method="POST">
		<input type="submit" name="clear_debug" value="Clear Debugging Log" />
	</form>
	<pre>
	<?php var_dump( get_option( "tsd_pn_debug_info" ) ); ?>
	</pre>
</div>
	<?php
}
