<?php
/**
 * Formular af CitizenOne journalsystem
 *
 * @package   mzaworkdk\Citizenone
 * @author    Mindell Zamora <mz@awork.dk>
 * @copyright 2025 AWORK A/S
 * @license   GPL 2.0+
 * @link      https://awork.dk
 */

namespace mzaworkdk\Citizenone\Backend;

use mzaworkdk\Citizenone\Engine\Base;
use mzaworkdk\Citizenone\Internals\Models\Retrieve_Token;

/**
 * Create the settings page in the backend
 */
class Settings_Page extends Base {

	/**
	 * Option group key.
	 *
	 * @var string The key for the options in the database.
	 */
	private $option_group = FACIOJ_TEXTDOMAIN . '_group';

	/**
	 * Option name.
	 *
	 * @var string The name of the option in the wp_options table.
	 */
	private $option_name = FACIOJ_TEXTDOMAIN . '-settings';

	/**
	 * Initialize the class.
	 *
	 * @return void|bool
	 */
	public function initialize() {
		if ( ! parent::initialize() ) {
			return;
		}

		// Add the options page and menu item.
		\add_action( 'admin_menu', array( $this, 'add_plugin_admin_menu' ) );

		// Register settings, sections, and fields.
		\add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Intercept form submission BEFORE settings are saved.
		\add_filter( 'pre_update_option_' . $this->option_name, array( $this, 'before_save_settings' ), 10, 2 );

		\add_action( 'admin_init', array( $this, 'handle_notice_dismissal' ) );
		\add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );

		$realpath        = (string) \realpath( __DIR__ );
		$plugin_basename = \plugin_basename( \plugin_dir_path( $realpath ) . FACIOJ_TEXTDOMAIN . '.php' );
		\add_filter( 'plugin_action_links_' . $plugin_basename, array( $this, 'add_action_links' ) );
	}

	/**
	 * Register the administration menu for this plugin.
	 */
	public function add_plugin_admin_menu(): void {
		\add_menu_page(
			\__( 'Contact Form App Settings', 'formular-af-citizenone-journalsystem' ),
			FACIOJ_NAME,
			'manage_options',
			FACIOJ_TEXTDOMAIN, // Page slug.
			array( $this, 'display_plugin_admin_page' ),
			'dashicons-hammer',
			90
		);
	}

	/**
	 * Render the settings page for this plugin.
	 */
	public function display_plugin_admin_page(): void {
		// This view file will also be modified.
		include_once FACIOJ_PLUGIN_ROOT . 'backend/views/admin.php';
	}

	/**
	 * Register all settings, sections, and fields.
	 */
	public function register_settings(): void {
		// Register the setting. This will create the entry in the wp_options table.
		\register_setting(
			$this->option_group,
			$this->option_name,
			// ✅ Add the sanitization callback.
			array(
				'sanitize_callback' => array( $this, 'sanitize_main_settings' ),
			)
		);

		// Section 1: Main Settings.
		\add_settings_section(
			'facioj_main_section',
			__( 'API Credentials', 'formular-af-citizenone-journalsystem' ),
			// @phpstan-ignore argument.type
			null, // No callback needed for the section description.
			FACIOJ_TEXTDOMAIN // Page slug.
		);

		// Section 2: hCaptcha Settings.
		\add_settings_section(
			'facioj_hcaptcha_section',
			__( 'hCaptcha Settings (Optional)', 'formular-af-citizenone-journalsystem' ),
			// @phpstan-ignore argument.type
			null, // No callback needed for the section description.
			FACIOJ_TEXTDOMAIN
		);

		// Add fields for Main Settings.
		\add_settings_field(
			'facioj_field_email',
			__( 'Email', 'formular-af-citizenone-journalsystem' ),
			array( $this, 'render_text_field' ),
			FACIOJ_TEXTDOMAIN,
			'facioj_main_section',
			array(
				'id'       => 'facioj_field_email',
				'required' => true,
			)
		);
		\add_settings_field(
			'facioj_field_company_cvr',
			__( 'Company CVR', 'formular-af-citizenone-journalsystem' ),
			array( $this, 'render_text_field' ),
			FACIOJ_TEXTDOMAIN,
			'facioj_main_section',
			array(
				'id'       => 'facioj_field_company_cvr',
				'required' => true,
			)
		);
		\add_settings_field(
			'facioj_field_company_id',
			__( 'CitizenOne Company ID', 'formular-af-citizenone-journalsystem' ),
			array( $this, 'render_text_field' ),
			FACIOJ_TEXTDOMAIN,
			'facioj_main_section',
			array(
				'id'       => 'facioj_field_company_id',
				'required' => true,
			)
		);

		// Add fields for hCaptcha.
		\add_settings_field(
			'facioj_hcaptcha_secret_key',
			'hCaptcha ' . __( 'secret key', 'formular-af-citizenone-journalsystem' ),
			array( $this, 'render_text_field' ),
			FACIOJ_TEXTDOMAIN,
			'facioj_hcaptcha_section',
			array( 'id' => 'facioj_hcaptcha_secret_key' )
		);
		\add_settings_field(
			'facioj_hcaptcha_site_key',
			'hCaptcha ' . __( 'site key', 'formular-af-citizenone-journalsystem' ),
			array( $this, 'render_text_field' ),
			FACIOJ_TEXTDOMAIN,
			'facioj_hcaptcha_section',
			array( 'id' => 'facioj_hcaptcha_site_key' )
		);
	}

	/**
	 * Renders a generic text input field.
	 *
	 * @param array $args Arguments passed from add_settings_field.
	 */
	public function render_text_field( array $args ): void {
		// 1. Get all options.
		$all_options = \get_option( $this->option_name, array() );
		if ( ! is_array( $all_options ) ) {
			$all_options = array();
		}

		// 2. Define the default value for this field.
		$defaults = array(
			$args['id'] => '', // Make sure the default is a string.
		);

		// 3. Merge the saved options with defaults.
		// If $all_options is not an array, wp_parse_args will convert it to an array.
		$options = \wp_parse_args( $all_options, $defaults );

		// Now, we're sure that $options[$args['id']] is a string
		// unless it was intentionally saved as an array.
		// Let's still add is_scalar for 100% safety.
		$value = \is_scalar( $options[ $args['id'] ] ) ? (string) $options[ $args['id'] ] : '';

		printf(
			'<input type="text" id="%s" name="%s" value="%s" class="regular-text"',
			\esc_attr( $args['id'] ),
			\esc_attr( "{$this->option_name}[{$args['id']}]" ), // name attribute.
			\esc_attr( $value )                               // value attribute.
		);
		// Use separate 'if' for conditional attribute.
		if ( isset( $args['required'] ) && $args['required'] ) {
			// Directly print the static string. Since it doesn't contain variables,
			// PHPCS knows it's safe.
			echo ' required="required"';
		}
		// Close the tag.
		echo ' />';
	}

	/**
	 * Action before settings are saved.
	 *
	 * @param array $new_value The new, unsanitized value for the option.
	 * @param array $old_value The old value of the option.
	 * @return array The new value to save, or the old value if there's an error.
	 */
	public function before_save_settings( $new_value, $old_value ) {
		$submitted_values = $this->get_sanitized_submitted_values( $new_value );

		if ( $this->has_validation_errors( $submitted_values ) ) {
			// Return the old value to prevent saving if there are errors.
			return $old_value;
		}

		$token_data = $this->process_token_retrieval( $submitted_values );

		// Merge the new token data with the submitted values.
		if ( $token_data ) {
			$new_value[ FACIOJ_TEXTDOMAIN . '_token' ] = $token_data;
		} else {
			// If API fails, remove the old token.
			unset( $new_value[ FACIOJ_TEXTDOMAIN . '_token' ] );
		}

		// Sanitize all fields before returning.
		foreach ( $new_value as $key => $value ) {
			$new_value[ $key ] = \sanitize_text_field( $value );
		}

		return $new_value;
	}

	/**
	 * Get and sanitize submitted form values.
	 *
	 * @param array $values The raw $_POST values for our option.
	 * @return array Sanitized values.
	 */
	private function get_sanitized_submitted_values( array $values ): array {
		$data = array(
			'company_cvr' => isset( $values['facioj_field_company_cvr'] ) ? \sanitize_text_field( $values['facioj_field_company_cvr'] ) : '',
			'company_id'  => isset( $values['facioj_field_company_id'] ) ? \sanitize_text_field( $values['facioj_field_company_id'] ) : '',
			'email'       => isset( $values['facioj_field_email'] ) ? \sanitize_email( $values['facioj_field_email'] ) : '',
		);
		return $data;
	}

	/**
	 * Process token retrieval from API.
	 *
	 * @param array $values The data.
	 * @return mixed The token data on success, false on failure.
	 */
	private function process_token_retrieval( array $values ) {
		$token = new Retrieve_Token();
		$data  = $token->submit(
			array(
				'company_cvr'           => $values['company_cvr'],
				'citizenone_company_id' => $values['company_id'],
				'email'                 => $values['email'],
			)
		);

		if ( ! is_object( $data ) || ! isset( $data->data ) ) {
			$error_message = __( 'The API did not accept the provided data. Please check your information and try again.', 'formular-af-citizenone-journalsystem' );
			\add_settings_error( 'facioj_messages', 'api_error', $error_message, 'error' );
			return false;
		}
		$success_message = __( '✅ Successfully connected to CitizenOne', 'formular-af-citizenone-journalsystem' );
		\add_settings_error( 'facioj_messages', 'api_success', $success_message, 'updated' ); // 'updated' is the class for green notices.
		return $data->data;
	}

	/**
	 * Validate required fields.
	 *
	 * @param array $values Data.
	 */
	private function has_validation_errors( array $values ): bool {
		$has_errors = false;
		if ( empty( $values['company_cvr'] ) ) {
			\add_settings_error( 'facioj_messages', 'cvr_required', __( 'Company CVR is required.', 'formular-af-citizenone-journalsystem' ), 'error' );
			$has_errors = true;
		}
		if ( empty( $values['company_id'] ) ) {
			\add_settings_error( 'facioj_messages', 'id_required', __( 'CitizenOne Company ID is required.', 'formular-af-citizenone-journalsystem' ), 'error' );
			$has_errors = true;
		}
		if ( empty( $values['email'] ) ) {
			\add_settings_error( 'facioj_messages', 'email_required', __( 'Email address is required.', 'formular-af-citizenone-journalsystem' ), 'error' );
			$has_errors = true;
		}
		return $has_errors;
	}

	/**
	 * Add settings action link to the plugins page.
	 *
	 * @param array $links Action links.
	 */
	public function add_action_links( array $links ): array {
		// Update the URL to point to the correct menu page.
		return array_merge(
			array(
				'settings' => '<a href="' . \admin_url( 'admin.php?page=' . FACIOJ_TEXTDOMAIN ) . '">' . \__( 'Settings', 'formular-af-citizenone-journalsystem' ) . '</a>',
			),
			$links
		);
	}

	/**
	 * Display admin notices when needed.
	 */
	public function display_admin_notices(): void {
		// Check if the user has the capability to install plugins.
		if ( ! \current_user_can( 'install_plugins' ) ) {
			return;
		}

		// Check if our transient is set.
		if ( \get_transient( 'facioj_autoloader_not_optimized' ) ) {
			$message = sprintf(
				// Use esc_html() for security.
				/* translators: %s is a link to dismiss the notice */
				\esc_html__( 'For better performance, the Formular af CitizenOne plugin recommends regenerating the autoloader. This is a developer-level task. %s', 'formular-af-citizenone-journalsystem' ),
				// Add a link to dismiss the notice.
				'<a href="' . \esc_url( \add_query_arg( 'facioj_dismiss_notice', 'autoloader_warning' ) ) . '">' . esc_html__( 'Dismiss this notice', 'formular-af-citizenone-journalsystem' ) . '</a>'
			);

			// Show the notice. 'notice-warning' gives a yellow color.
			echo '<div class="notice notice-warning"><p>' . $message . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			// We'll ignore PHPCS here because we built the $message with escaping.
		}
	}

	/**
	 * Handle the dismissal of the notice.
	 */
	public function handle_notice_dismissal(): void {
		if ( isset( $_GET['facioj_dismiss_notice'] ) && 'autoloader_warning' === $_GET['facioj_dismiss_notice'] ) { //phpcs:ignore
			// Remove the transient so the notice doesn't appear again.
			\delete_transient( 'facioj_autoloader_not_optimized' );
		}
	}

	/**
	 * Main sanitization callback for the settings.
	 *
	 * @param array $input The raw input from the form.
	 * @return array The sanitized input.
	 */
	public function sanitize_main_settings( array $input ): array {
		$new_input = array();
		// Sanitize each expected field.
		if ( isset( $input['facioj_field_email'] ) ) {
			$new_input['facioj_field_email'] = \sanitize_email( $input['facioj_field_email'] );
		}
		if ( isset( $input['facioj_field_company_cvr'] ) ) {
			$new_input['facioj_field_company_cvr'] = \sanitize_text_field( $input['facioj_field_company_cvr'] );
		}
		if ( isset( $input['facioj_field_company_id'] ) ) {
			$new_input['facioj_field_company_id'] = \sanitize_text_field( $input['facioj_field_company_id'] );
		}
		if ( isset( $input['facioj_hcaptcha_secret_key'] ) ) {
			$new_input['facioj_hcaptcha_secret_key'] = \sanitize_text_field( $input['facioj_hcaptcha_secret_key'] );
		}
		if ( isset( $input['facioj_hcaptcha_site_key'] ) ) {
			$new_input['facioj_hcaptcha_site_key'] = \sanitize_text_field( $input['facioj_hcaptcha_site_key'] );
		}
		if ( isset( $input[ FACIOJ_TEXTDOMAIN . '_token' ] ) ) {
			$new_input[ FACIOJ_TEXTDOMAIN . '_token' ] = \sanitize_text_field( $input[ FACIOJ_TEXTDOMAIN . '_token' ] );
		}

		return $new_input;
	}
}
