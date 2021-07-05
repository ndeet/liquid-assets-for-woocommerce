<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Add Liquid Assets settings page menu entry.
 */
function wcla_add_settings_page() {
	add_submenu_page( 'woocommerce', 'Woocommerce Liquid Assets Settings', 'Liquid Assets Settings', 'manage_options', 'wcla-settings', 'wcla_render_settings_page' );
}
add_action( 'admin_menu', 'wcla_add_settings_page', 99 );

/**
 * Render Liquid Assets settings page output.
 */
function wcla_render_settings_page() {
	?>
	<h2>Woocommerce Liquid Assets Settings</h2>
	<form action="options.php" method="post">
		<?php
		settings_fields( 'wcla_plugin_options' );
		do_settings_sections( 'wcla_plugin' ); ?>
		<input name="submit" class="button button-primary" type="submit" value="<?php esc_attr_e( 'Save' ); ?>" />
	</form>
	<?php
}

/**
 * Register Liquid Assets plugin options and fields.
 */
function wcla_register_settings() {
	register_setting( 'wcla_plugin_options', 'wcla_plugin_options' );
	// Switch to choose between coinos.io or Elements node RPC.
	add_settings_section( 'general', 'General', 'wcla_plugin_general_section_text', 'wcla_plugin' );
	add_settings_field( 'wcla_plugin_setting_mode', 'Active configuration', 'wcla_plugin_setting_mode', 'wcla_plugin', 'general' );
	add_settings_field( 'wcla_plugin_setting_admin_mails', 'Admin notification mails', 'wcla_plugin_setting_admin_mails', 'wcla_plugin', 'general' );

	// coinos.io settings.
	add_settings_section( 'api_settings', 'Coinos.io API Settings', 'wcla_plugin_coinos_section_text', 'wcla_plugin' );
	add_settings_field( 'wcla_plugin_setting_coinos_api_key', 'API Key (JWT token)', 'wcla_plugin_setting_coinos_api_key', 'wcla_plugin', 'api_settings' );
	// Elements Node RPC settings.
	add_settings_section( 'rpc_settings', 'Elements node RPC Settings', 'wcla_plugin_rpc_section_text', 'wcla_plugin' );
	add_settings_field( 'wcla_plugin_setting_rpc_host', 'RPC Host', 'wcla_plugin_setting_rpc_host', 'wcla_plugin', 'rpc_settings' );
	add_settings_field( 'wcla_plugin_setting_rpc_user', 'RPC User', 'wcla_plugin_setting_rpc_user', 'wcla_plugin', 'rpc_settings' );
	add_settings_field( 'wcla_plugin_setting_rpc_pass', 'RPC Password', 'wcla_plugin_setting_rpc_pass', 'wcla_plugin', 'rpc_settings' );
}
add_action( 'admin_init', 'wcla_register_settings' );

/**
 * Callback to render the settings section text.
 */
function wcla_plugin_general_section_text() {
	echo '<p>Select which settings should be active and add emails that get notified in case of an error.</p>';
}

/**
 * Callback to render settings mode dropdown.
 */
function wcla_plugin_setting_mode() {
	$selected_option = get_option( 'wcla_plugin_options' )['wcla_plugin_setting_mode'];
	$items = [
		'coinos' => 'coinos.io API',
	    'elements' => 'Elements RPC',
    ];
	echo "<select id='wcla_plugin_setting_mode' name='wcla_plugin_options[wcla_plugin_setting_mode]'>";
	foreach ( $items as $id => $value ) {
		$selected = ( $selected_option == $id ) ? 'selected="selected"' : '';
		echo "<option value='" . esc_attr($id) . "' " . esc_attr($selected) . ">" . esc_attr($value) . "</option>";
	}
	echo "</select>";
}

/**
 * Callback to render the admin emails text input field.
 */
function wcla_plugin_setting_admin_mails() {
	echo "<input id='wcla_plugin_setting_admin_mails' name='wcla_plugin_options[admin_mails]' type='text' size='120' value='" . esc_attr( get_option( 'wcla_plugin_options' )['admin_mails'] ) . "' />";
	echo "<p>Enter a single or comma separated list of emails to get notifications on errors. e.g. admin@domain.tld,other@domain.tld</p>";
}

/**
 * Callback to render the settings section text.
 */
function wcla_plugin_coinos_section_text() {
	echo '<p>Put your <a href="https://coinos.io" target="_blank">coinos.io</a> API settings here. Leave empty if you want to use Elements node RPC settings below.</p>';
}

/**
 * Callback to render the coinos.io API key text input field.
 */
function wcla_plugin_setting_coinos_api_key() {
	echo "<input id='wcla_plugin_setting_coinos_api_key' name='wcla_plugin_options[coinos_api_key]' type='text' size='120' value='" . esc_attr( get_option( 'wcla_plugin_options' )['coinos_api_key'] ) . "' />";
}

/**
 * Callback to render the rpc settings section text.
 */
function wcla_plugin_rpc_section_text() {
	echo '<p>Enter your Elements node RPC settings to communicate directly with your node.</p>';
	echo '<p>Please make sure the nodes wallet has enough funds for the configured product assets and L-BTC for the fees.</p>';
}

/**
 * Callback to render the rpc host text input field.
 */
function wcla_plugin_setting_rpc_host() {
	echo "<input id='wcla_plugin_setting_rpc_host' name='wcla_plugin_options[rpc_host]' type='text' size='60' value='" . esc_attr( get_option( 'wcla_plugin_options' )['rpc_host'] ) . "' />";
	echo "<p>e.g. https://rpc.somehost.tld:7041/</p>";
}

/**
 * Callback to render the rpc user text input field.
 */
function wcla_plugin_setting_rpc_user() {
	echo "<input id='wcla_plugin_setting_rpc_user' name='wcla_plugin_options[rpc_user]' type='text' size='60' value='" . esc_attr( get_option( 'wcla_plugin_options' )['rpc_user'] ) . "' />";
}

/**
 * Callback to render the rpc user text input field.
 */
function wcla_plugin_setting_rpc_pass() {
	echo "<input id='wcla_plugin_setting_rpc_pass' name='wcla_plugin_options[rpc_pass]' type='text' size='60' value='" . esc_attr( get_option( 'wcla_plugin_options' )['rpc_pass'] ) . "' />";
}
