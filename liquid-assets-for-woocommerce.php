<?php
/*
Plugin Name: Liquid Assets for WooCommerce
Description: Configure your products to reference Liquid Assets. The plugin will send Liquid Assets (coinos.io and your own Elements RPC node supported) to customers after successful payment.
Version:     1.8.3
Author:      ndeet
Author URI:  https://attec.at
License:     MIT

Copyright 2021 Andreas Tasch (email : info@attec.at)
Liquid Assets for WooCommerce is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 2 of the License, or
any later version.

Liquid Assets for WooCommerce is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the MIT license
along with this Liquid Assets for WooCommerce plugin. If not, see https://opensource.org/licenses/MIT.
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// Includes.
require_once plugin_dir_path(__FILE__) . '/includes/wcla-admin-settings.php';
require_once plugin_dir_path(__FILE__) . '/includes/WCLAElementsClient.php';

/**
 * Display the custom Liquid assets fields.
 */
function wcla_create_custom_field() {
	$liquid_asset_id = array(
		'id' => 'liquid_asset_id',
		'label' => __( 'Liquid asset ID', 'lac' ),
		'class' => 'liquid-asset-id',
		'desc_tip' => true,
		'description' => __( 'Enter the liquid asset ID of the token you want to send.', 'lac' ),
	);
	woocommerce_wp_text_input( $liquid_asset_id );

	$liquid_address = array(
		'id' => 'liquid_address',
		'label' => __( 'Customer Liquid address', 'wcla' ),
		'placeholder' => 'Customer visible label: e.g. Your Liquid Address',
		'class' => 'liquid-address',
		'desc_tip' => true,
		'description' => __( 'Enter your Liquid Address here. Tokens will be sent to this address.', 'wcla' ),
	);
	woocommerce_wp_text_input( $liquid_address );
}
add_action( 'woocommerce_product_options_general_product_data', 'wcla_create_custom_field' );

/**
 * Save the custom Liquid assets fields.
 *
 * @param Integer $product_id Product ID.
 */
function wcla_save_custom_field( $product_id ) {
	$product = wc_get_product( $product_id );
	$product->update_meta_data( 'liquid_asset_id', sanitize_text_field( $_POST['liquid_asset_id'] ?? '' ) );
	$product->update_meta_data( 'liquid_address', sanitize_text_field( $_POST['liquid_address'] ?? '' ) );
	$product->save();
}
add_action( 'woocommerce_process_product_meta', 'wcla_save_custom_field' );

/**
 * Display custom Liquid address field on frontend.
 */
function wcla_display_custom_field() {
	global $post;
	$product = wc_get_product( $post->ID );
	$liquid_address = $product->get_meta( 'liquid_address' );
	if ( $liquid_address ) {
		printf(
			'<div class="wcla-liquid-address-wrapper"><label for="wcla-liquid-address" class="wcla-liquid-address-label">%s</label> <input type="text" id="wcla-liquid-address" name="wcla-liquid-address" value=""></div>',
			esc_html( $liquid_address )
		);
	}
}
add_action( 'woocommerce_before_add_to_cart_button', 'wcla_display_custom_field' );

/**
 * Validate custom Liquid address field.
 *
 * @param Array $passed Validation status.
 * @param Integer $product_id Product ID.
 * @param Boolean $quantity Quantity
 */
function wcla_validate_custom_field( $passed, $product_id, $quantity ) {
	// We only validate products with liquid tokens enabled.
	$product = wc_get_product( $product_id );
	if ($liquid_asset_id = $product->get_meta('liquid_asset_id') ?? '') {
		if ( empty( $liquid_address = $_POST['wcla-liquid-address'] ) ) {
			wc_add_notice( __( 'Please enter your Liquid address into the text field.', 'wcla' ), 'error' );
			return false;
		}
		if ( wcla_is_liquid_address($liquid_address) === false ) {
			// Todo: additional validity check of Liquid addresses e.g. by doing RPC validateaddress
			wc_add_notice( __( 'Please make sure the Liquid address is valid.', 'wcla' ), 'error' );
			return false;
		}
	}
	return $passed;
}
add_filter( 'woocommerce_add_to_cart_validation', 'wcla_validate_custom_field', 10, 3 );

/**
 * Add the users Liquid address as item data to the cart object.
 *
 * @param Array $cart_item_data Cart item meta data.
 * @param Integer $product_id Product ID.
 * @param Integer $variation_id Variation ID.
 * @param Boolean $quantity Quantity
 */
function wcla_add_custom_field_item_data( $cart_item_data, $product_id, $variation_id, $quantity ) {
	if ( ! empty( $liquid_address = $_POST['wcla-liquid-address'] ) ) {
		// Add the item data
		$cart_item_data['liquid_address'] = sanitize_text_field($liquid_address);
	}
	return $cart_item_data;
}
add_filter( 'woocommerce_add_cart_item_data', 'wcla_add_custom_field_item_data', 10, 4 );

/**
 * Display the custom Liquid address field value in the cart.
 *
 * @param $name
 * @param $cart_item
 * @param $cart_item_key
 *
 * @return mixed|string
 */
function wcla_cart_item_name( $name, $cart_item, $cart_item_key ) {
	if ( isset( $cart_item['liquid_address'] ) ) {
		$liquid_address_short = substr_replace($cart_item['liquid_address'], '...', 7, -15);

		$name .= sprintf(
			'<p class="wcla-liquid-address-summary">' . __( 'Your Liquid address: ', 'lac' ) . ' %s</p>',
			esc_html( $liquid_address_short )
		);
	}
	return $name;
}
add_filter( 'woocommerce_cart_item_name', 'wcla_cart_item_name', 10, 3 );

/**
 * Add custom Liquid address field to order object.
 *
 * @param $item
 * @param $cart_item_key
 * @param $values
 * @param $order
 */
function wcla_add_custom_data_to_order( $item, $cart_item_key, $values, $order ) {
	foreach ( $item as $cart_item_key => $data ) {
		if ( isset( $data['liquid_address'] ) ) {
			$item->add_meta_data('Liquid address', $data['liquid_address'], true );
		}
	}
}
add_action( 'woocommerce_checkout_create_order_line_item', 'wcla_add_custom_data_to_order', 10, 4 );

/**
 * On payment complete, go on and send the liquid asset to the user.
 *
 * @param $order_id
 */
function wcla_order_payment_complete( $order_id ) {
	$logger = new WC_logger();
	$order = wc_get_order( $order_id );

	$logger->add('liquid-assets', "Liquid asset hook on payment complete triggered.");

	// Check which mode we operate coinos or Elements RPC.
	if (!$mode = get_option( 'wcla_plugin_options' )['wcla_plugin_setting_mode']) {
		$logger->add('liquid-assets', "No liquid assets mode set, aborting.");
		return false;
	}

	// Abort if there is no coinos API key or Elements RPC configured.
	if ( $mode === 'coinos' ) {
		if ( empty($jwt_token = get_option( 'wcla_plugin_options' )['coinos_api_key']) ) {
			$msg_coinos_api_key = "Liquid asset: No coinos.io API key provided, aborting.";
			$logger->add('liquid-assets', $msg_coinos_api_key);
			$order->add_order_note($msg_coinos_api_key);
			wcla_send_admin_mail($msg_coinos_api_key);
			return false;
		}
	} elseif ( $mode === 'elements' ) {
		if (is_null(wcla_elements_rpc_init())) {
			$msg_rpc_not_configured = 'Liquid asset: could not process, Elements RPC host, user or password not configured.';
			$logger->add('liquid-assets', $msg_rpc_not_configured);
			$order->add_order_note($msg_rpc_not_configured);
			wcla_send_admin_mail($msg_rpc_not_configured);
			return false;
		}
	}

	foreach ( $order->get_items() as $item_id => $item) {
		if ( $liquid_address = $item->get_meta('Liquid address') ) {
			// Check if there was already an attempt to send the asset.
			if ( $status = $item->get_meta('liquid_asset_sent_status') ) {
				if ($status === 'success') {
					$msg_asset_sent_status = 'Liquid asset: asset already sent, not sending again.';
				} else if ($status === 'error') {
					$msg_asset_sent_status = 'Liquid asset: asset sending failed before, not trying again. Please check manually.';
				}

				$logger->add('liquid-assets', $msg_asset_sent_status);
				$order->add_order_note($msg_asset_sent_status);
				// Only send admin notification on error.
				if ($status === 'error') {
					wcla_send_admin_mail($msg_asset_sent_status . ' Order ID: ' . $order_id);
				}
				return false;
			}

			$order->add_order_note('Liquid asset: trying to send the asset to user.');
			/** @var WC_Product $product */
			$product = $item->get_product();
			$sku = $product->get_sku();

			if (!$liquid_asset_id = $product->get_meta('liquid_asset_id')) {
				$msg_no_asset_id = "Liquid asset: asset ID not configured on product SKU $sku, aborting.";
				$logger->add('liquid-assets', $msg_no_asset_id);
				$order->add_order_note($msg_no_asset_id);
				wcla_send_admin_mail($msg_no_asset_id);
				return false;
			}
			// Send funds via coinos API or Elements RPC.
			if ($mode === 'coinos') {
				if ( wcla_coinos_send_liquid_asset( $liquid_address, $liquid_asset_id, $item->get_quantity() )) {
					// Track on the line item if the liquid asset was sent successfully.
					$item->add_meta_data('liquid_asset_sent_status', 'success', true);
					$item->save_meta_data();
					$msg_coinos_success = 'Liquid asset: Successfully sent Liquid asset via coinos.io.';
					$logger->add('liquid-assets', $msg_coinos_success);
					$order->add_order_note($msg_coinos_success);
				} else {
					$msg_coinos_error = 'Liquid asset: ERROR while sending or timeout. Please check logs and coinos.io and send manually if needed.';
					$logger->add('liquid-assets', $msg_coinos_error);
					$order->add_order_note($msg_coinos_error);
					wcla_send_admin_mail($msg_coinos_error . ' Order ID: ' . $order_id);
					// Track on the line item if the liquid asset was sent successfully.
					$item->add_meta_data('liquid_asset_sent_status', 'error', true);
					$item->save_meta_data();
				}
			} elseif ($mode === 'elements') {
				try {
					$client = wcla_elements_rpc_init();
					if ($response = $client->sendToAddress($liquid_address, $item->get_quantity(), $liquid_asset_id)->send()) {
						if (!empty($response->result)) {
							// Track on the line item if the liquid asset was sent successfully.
							$item->add_meta_data('liquid_asset_sent_status', 'success', true);
							$item->save_meta_data();
							$msg_rpc_success = 'Liquid asset: Successfully sent Liquid asset via Elements RPC. TXID: ' . $response->result;
							$logger->add('liquid-assets', $msg_rpc_success);
							$order->add_order_note($msg_rpc_success);
						} else {
							$msg_rpc_error = 'Liquid asset: ERROR sending via Elements RPC.';
							$logger->add('liquid-assets', $msg_rpc_error);
							$order->add_order_note($msg_rpc_error);
							wcla_send_admin_mail($msg_rpc_error . ' Order ID: ' . $order_id);
							$item->add_meta_data('liquid_asset_sent_status', 'error', true);
							$item->save_meta_data();
						}
					} else { // Likely this will never trigger as it would cause an exception on the client but to be sure.
						$msg_rpc_error = 'Liquid asset: ERROR sending via Elements RPC.';
						$logger->add('liquid-assets', $msg_rpc_error);
						$order->add_order_note($msg_rpc_error);
						wcla_send_admin_mail($msg_rpc_error . ' Order ID: ' . $order_id);
						$item->add_meta_data('liquid_asset_sent_status', 'error', true);
						$item->save_meta_data();
					}
				} catch (\Exception $e) {
					$msg_rpc_exception = "Liquid asset: ERROR sending via Elements RPC. Exception msg: " . $e->getMessage();
					$logger->add('liquid-assets', $msg_rpc_exception);
					$order->add_order_note($msg_rpc_exception);
					wcla_send_admin_mail($msg_rpc_exception . '; Order ID: ' . $order_id);
					$item->add_meta_data('liquid_asset_sent_status', 'error', true);
					$item->save_meta_data();
				}
			}
		}
	}
}
// We need to use pre payment complete hook here as woocommerce_payment_complete does not trigger because of how
// BTCPay plugin handles order status.
add_action( 'woocommerce_pre_payment_complete', 'wcla_order_payment_complete' );

/**
 * Send the Liquid asset over the coinos.io API.
 *
 * @param string $liquid_address Customers Liquid Address.
 * @param string $asset_id Liquid asset ID.
 * @param Integer $quantity Quantity to send.
 *
 * @return bool
 */
function wcla_coinos_send_liquid_asset( $liquid_address, $asset_id, $quantity ) {
	// WooCommerce logger in case it is activated for debugging.
	$logger = new WC_Logger();
	if ( empty($liquid_address) || empty($asset_id) || empty($quantity) ) {
		$logger->add('liquid-assets', "Empty param given, address: $liquid_address, asset_id: $asset_id, quantity: $quantity");
		return false;
	}

    // Get api key from settings form.
	if ( empty($jwt_token = get_option( 'wcla_plugin_options' )['coinos_api_key']) ) {
		$logger->add('liquid-assets', "No coinos.io API key provided, aborting.");
		return false;
	}

	$api_endpoint = "https://coinos.io/api/liquid/send";
	$fee_rate = 100;

	$payload = [
		'address' => $liquid_address,
		'asset' => $asset_id,
		'amount' => $quantity,
		'feeRate' => $fee_rate
	];

	$response = wp_remote_post($api_endpoint, [
		'body' => json_encode($payload),
		'blocking' => true, // making it non blocking here will remove all the response headers and data.
		'timeout' => 25,
		'headers' => [
			'Authorization' => 'Bearer ' . $jwt_token,
			'Content-Type' => 'application/json; charset=utf-8'
		]
	]);

	if ( is_wp_error( $response )) {
		$logger->add('liquid-assets', 'API call error: ' . $response->get_error_message());
		return false;
	}

	$logger->add('liquid-assets', 'API Response: ' . print_r($response, true));

	// If the wp_remote_post argument 'blocking' is false we will not get any status codes or response data although
	// the request succeeds. So if we make it non-blocking above we need to change the logic here and not check http
	// status code anymore but rely on the is_wp_error() handling above.
	if ( wp_remote_retrieve_response_code( $response ) === 200) {
		$logger->add('liquid-assets', 'API response success, all good.');
		return true;
	} else {
		$logger->add('liquid-assets', 'API response other than code 200 seems something went wrong.');
		return false;
	}
}

/**
 * Init Elements RPC client.
 *
 * @return WCLAElementsClient|null
 * @throws Exception
 */
function wcla_elements_rpc_init() {
	if (!empty($rpc_user = get_option('wcla_plugin_options')['rpc_user'])
	&& !empty($rpc_pass = get_option('wcla_plugin_options')['rpc_pass'])
	&& !empty($rpc_host = get_option('wcla_plugin_options')['rpc_host'])) {
		return new WCLAElementsClient($rpc_host, $rpc_user, $rpc_pass);
	}
	return null;
}

/**
 * Check if the input address is a valid Liquid address.
 *
 * @param $address
 *
 * @return bool
 */
function wcla_is_liquid_address($address) {
	if (empty($address)) {
		return false;
	}
	// If Elements RPC is configured we can use the node to do best possible address validation, otherwise use fallback.
	if (get_option( 'wcla_plugin_options' )['wcla_plugin_setting_mode'] === 'elements') {
		try {
			$client = wcla_elements_rpc_init();
			$response = $client->validateAddress($address)->send();
			return $response->result->isvalid;
		} catch (\Exception $e) {
			wcla_send_admin_mail("Liquid asset: Could not validate Liquid address on add to cart because of Elements RPC exception: " . $e->getMessage());
			return false;
		}
	} else { // Fallback check.
		// ported from coinos.io UI https://github.com/coinos/coinos-ui/blob/master/src/store/index.js#L83
		if (str_starts_with($address, 'Az') ||
		    str_starts_with($address, 'lq1') ||
		    str_starts_with($address, 'VJL') ||
		    str_starts_with($address, 'VT') ||
		    str_starts_with($address, 'XR') ||
		    str_starts_with($address, 'XC') ||
		    ((str_starts_with($address, 'H') || str_starts_with($address, 'G') || str_starts_with($address, 'Q')) &&
		     strlen($address) === 34) ||
		    (str_starts_with($address, 'ert1q') && strlen($address) === 43) ||
		    (str_starts_with($address, 'ex1q') && strlen($address) === 42) ||
		    str_starts_with($address, 'el1qq') ||
		    str_starts_with($address, 'lq1qq')
		) {
			return true;
		}
	}

	return false;
}


if ( ! function_exists('str_starts_with')) {
	/**
	 * Polyfill function for PHP 8 functionality to check if a string starts with a certain chars.
	 *
	 * @param $haystack
	 * @param $needle
	 *
	 * @return bool
	 */
	function str_starts_with($haystack, $needle) {
		return (string)$needle !== '' && strncmp($haystack, $needle, strlen($needle)) === 0;
	}
}

/**
 * Helper for sending mails.
 *
 * @param string $message
 */
function wcla_send_admin_mail($message) {
	if (!empty($mails = get_option('wcla_plugin_options')['admin_mails'])) {
		// Remove whitespaces.
		$mails = preg_replace('/\s+/', '', $mails);
		return wp_mail( $mails, 'Liquid Assets for WooCommerce Plugin', $message);
	}
	return false;
}
