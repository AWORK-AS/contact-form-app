<?php
/**
 * The "Contact Form" submit handler
 *
 * @package   mzaworkdk\Citizenone
 * @author    Mindell Zamora <mz@awork.dk>
 * @copyright 2025 AWORK A/S
 * @license   GPL 2.0+
 * @link      https://awork.dk
 */

namespace mzaworkdk\Citizenone\Rest;

use WP_REST_Request;

/**
 * Form submission handler.
 */
class Form_Handler {

	/**
	 * Add action rest_api_init in constructor.
	 */
	public function __construct() {
		\add_action( 'rest_api_init', array( $this, 'register_api_endpoint' ) );
	}

	/**
	 * Register API endpoint
	 */
	public function register_api_endpoint(): void {
		\register_rest_route(
			'formular-af-citizenone-journalsystem/v1',
			'/submit',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'facioj_handle_form_submission' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'_wpnonce' => array(
						'required'          => false,
						'validate_callback' => function ( $param ) {
							return ! empty( $param );
						},
					),
				),
			)
		);
	}

	/**
	 * Handle form submission
	 *
	 * @param \WP_REST_Request $request WP Rest Request.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 */
	public function facioj_handle_form_submission( WP_REST_Request $request ): \WP_REST_Response|\WP_Error {
		// Verify nonce.
		$nonce_verification = $this->verify_nonce( $request );

		if ( is_wp_error( $nonce_verification ) ) {
			return $nonce_verification;
		}

		$data = $request->get_params();
		$opts = \facioj_get_settings();

		// Check if already configured.
		if ( ! $opts ) {
			return new \WP_Error(
				'not_configured',
				__( 'Error occured. Please contact the administrator.', 'formular-af-citizenone-journalsystem' ),
				array( 'status' => 403 )
			);
		}

		// Validate hCaptcha if enabled.
		$hcaptcha_validation = $this->validate_hcaptcha( $opts, $data );

		if ( is_wp_error( $hcaptcha_validation ) ) {
			return $hcaptcha_validation;
		}

		// Check if token exists.
		$token_validation = $this->validate_token( $opts );

		if ( is_wp_error( $token_validation ) ) {
			return $token_validation;
		}

		// Submit lead.
		return $this->submit_lead( $data );
	}

	/**
	 * Verify nonce from request
	 *
	 * @param \WP_REST_Request $request WP REST Request.
	 * @phpstan-param \WP_REST_Request<array<string, mixed>> $request
	 */
	private function verify_nonce( WP_REST_Request $request ): ?\WP_Error {
		$nonce = $request->get_header( 'X-WP-Nonce' ) ?? '';

		if ( ! \wp_verify_nonce( $nonce, 'wp_rest' ) ) {
			return new \WP_Error( 'invalid_nonce', 'Security check failed', array( 'status' => 403 ) );
		}

		return null;
	}

	/**
	 * Validate hCaptcha if enabled
	 *
	 * @param array $opts Options.
	 * @param array $data Data.
	 */
	private function validate_hcaptcha( array $opts, array $data ): ?\WP_Error {
		$hcaptcha_site_key   = $opts['facioj_hcaptcha_site_key'] ?? false;
		$hcaptcha_secret_key = $opts['facioj_hcaptcha_secret_key'] ?? false;
		$hcaptcha_enabled    = false;

		if ( $hcaptcha_site_key && $hcaptcha_secret_key ) {
			$hcaptcha_enabled = true;
		}

		if ( ! $hcaptcha_enabled ) {
			return null;
		}

		if ( ! isset( $data['h-captcha-response'] ) || empty( $data['h-captcha-response'] ) ) {
			return new \WP_Error(
				'hcaptcha_missing',
				__( 'Please complete the hCaptcha challenge.', 'formular-af-citizenone-journalsystem' ),
				array( 'status' => 400 )
			);
		}

		if ( is_string( $data['h-captcha-response'] ) ) {
			$hcaptcha_response_value = $data['h-captcha-response'];
		} else {
			$hcaptcha_response_value = '';
		}

		$hcaptcha_response   = \sanitize_text_field( $hcaptcha_response_value );
		$verification_result = $this->verify_hcaptcha( $hcaptcha_response );

		if ( ! $verification_result['success'] ) {
			return new \WP_Error(
				'hcaptcha_failed',
				__( 'hCaptcha verification failed. Please try again.', 'formular-af-citizenone-journalsystem' ),
				array( 'status' => 403 )
			);
		}

		return null;
	}

	/**
	 * Validate token exists
	 *
	 * @param array $opts Options.
	 */
	private function validate_token( array $opts ): ?\WP_Error {
		$token = $opts[ FACIOJ_TEXTDOMAIN . '_token' ] ?? false;

		if ( ! $token ) {
			return new \WP_Error(
				'not_connected',
				__( 'Error occured. Please contact the administrator.', 'formular-af-citizenone-journalsystem' ),
				array( 'status' => 403 )
			);
		}

		return null;
	}

	/**
	 * Submit lead and return response
	 *
	 * @param array $data Data.
	 */
	private function submit_lead( array $data ): \WP_REST_Response {
		$submission = new \mzaworkdk\Citizenone\Internals\Models\Contact_Submission();

		if ( $submission->submit_lead( $data ) ) {
			return new \WP_REST_Response(
				array(
					'success' => true,
					'message' => __( 'Thank you! Your message has been sent.', 'formular-af-citizenone-journalsystem' ),
				),
				200
			);
		}

		return new \WP_REST_Response(
			array(
				'success' => false,
				'message' => __(
					'Failed to send message. Please try again.',
					'formular-af-citizenone-journalsystem'
				),
			),
			500
		);
	}

	/**
	 * Verify the hCaptcha token
	 *
	 * @param string $token The 'h-captcha-response' from the form.
	 * @return array
	 */
	private function verify_hcaptcha( $token ) {
		$options    = \facioj_get_settings();
		$secret_key = $options['facioj_hcaptcha_secret_key'] ?? '';

		if ( empty( $secret_key ) ) {
			// Error: No secret key configured.
			return array(
				'success'     => false,
				'error-codes' => array( 'missing-secret-key' ),
			);
		}

		$remote_ip = '';

		if ( isset( $_SERVER['REMOTE_ADDR'] ) ) {
			$remote_ip = \sanitize_text_field( \wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		$data = array(
			'secret'   => $secret_key,
			'response' => $token,
			'remoteip' => $remote_ip,
		);

		$verify_url = 'https://hcaptcha.com/siteverify';

		$verify_data = array(
			'method'  => 'POST',
			'body'    => $data,
			'timeout' => 15,
		);
		// Use WordPress HTTP API for the request.
		$response = \wp_remote_post( $verify_url, $verify_data );

		if ( \is_wp_error( $response ) ) {
			// Connection error to hCaptcha server.
			return array(
				'success'     => false,
				'error-codes' => array( 'wp-remote-error' ),
			);
		}

		$body   = \wp_remote_retrieve_body( $response );
		$result = json_decode( $body, true );

		// Ensure we always return the expected array structure.
		if ( ! is_array( $result ) || ! isset( $result['success'] ) ) {
			return array(
				'success'     => false,
				'error-codes' => array( 'invalid-response' ),
			);
		}

		return $result; // Result will contain 'success' = true/false.
	}
}
