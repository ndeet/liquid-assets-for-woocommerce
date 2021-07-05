<?php

/**
 * Implements a small subset of Elements RPC methods.
 *
 * @see https://elementsproject.org/en/doc/0.18.1.11/rpc/
 */
class WCLAElementsClient {

	protected $host;

	protected $payload = [];

	protected $args = [];

	/**
	 * WCLAElementsClient constructor.
	 *
	 * @param $rpcHost
	 * @param $rpcUser
	 * @param $rpcPass
	 *
	 * @throws Exception
	 */
	public function __construct($rpcHost, $rpcUser, $rpcPass) {
		if (empty($rpcHost) || empty($rpcUser) || empty($rpcPass)) {
			throw new \Exception("RPC Error: Make sure you configured all RPC credentials.");
		}

		$this->host = $rpcHost;

		$this->args = [
			'headers' => [
				'Authorization' => 'Basic ' . base64_encode( "$rpcUser:$rpcPass" )
			],
			'timeout' => 20,
		];

		$this->payload = [
			'jsonrpc' => '1.0',
			'id' => 'wcla',
			'method' => '',
			'params' => []
		];

	}

	/**
	 * Sends the RPC call to the elements RPC host.
	 *
	 * @return mixed
	 * @throws Exception
	 */
	public function send() {
		// check if all data there
		if (empty($this->payload['method'])) {
			throw new \Exception('No elements RPC method called, aborting.');
		}

		// Prepare data structure, merge payload to args.
		$this->args['body'] = json_encode($this->payload);

		$response = wp_remote_post( $this->host, $this->args );

		if (is_wp_error( $response )) {
			throw new \Exception($response->get_error_message());
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( $response_code !== 200) {
			$response_data = json_decode(wp_remote_retrieve_body( $response ));
			$error = '';
			if (!empty($response_data->error)) {
				$error = "({$response_data->error->code}) {$response_data->error->message}";
			}
			if (empty($error) && !empty($response['response']['message'])) {
				$error = "({$response_code}) {$response['response']['message']}";
			}
			throw new \Exception('RPC error: ' . $error, $response_code);
		}
		return json_decode(wp_remote_retrieve_body( $response ));
	}

	/**
	 * Calls RPC getbalance method.
	 *
	 * @param string|null $assetId
	 *   Liquid asset ID.
	 * @param int $minConf
	 *   Minimum number of confirmations, defaults to 1.
	 *
	 * @return $this
	 *
	 * @see https://elementsproject.org/en/doc/0.18.1.11/rpc/wallet/getbalance/
	 */
	public function getBalance( $assetId = null, $minConf = 1) {
		$this->payload['method'] = 'getbalance';
		$this->payload['params'] = [ '*', $minConf, false, $assetId ];
		return $this;
	}

	/**
	 * Calls RPC validateAddress method.
	 *
	 * @param $address
	 *
	 * @return $this
	 *
	 * @see https://elementsproject.org/en/doc/0.18.1.11/rpc/util/validateaddress/
	 */
	public function validateAddress( $address ) {
		$this->payload['method'] = 'validateaddress';
		$this->payload['params'] = [ $address ];
		return $this;
	}

	/**
	 * Calls RPC sendToAddress method to send assets.
	 *
	 * @param string $address
	 * @param string $amount
	 * @param string $assetId
	 *
	 * @return $this
	 *
	 * @see https://elementsproject.org/en/doc/0.18.1.11/rpc/wallet/sendtoaddress/
	 */
	public function sendToAddress( $address, $amount, $assetId ) {
		// Internally elements handles 1 token as 1 satoshi, e.g. 0.00000001
		$amount = $amount * 0.00000001;
		$this->payload['method'] = 'sendtoaddress';
		$this->payload['params'] = [ $address,  number_format($amount,8,'.',''), '', '', false, true, 1, 'UNSET', $assetId ];
		return $this;
	}

}
