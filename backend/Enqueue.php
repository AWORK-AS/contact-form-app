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

/**
 * This class contain the Enqueue stuff for the backend
 */
class Enqueue extends Base {

	/**
	 * Initialize the class.
	 *
	 * @return void|bool
	 */
	public function initialize() {
		if ( !parent::initialize() ) {
			return;
		}

        // Register and enqueue assets
        
		\add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
		\add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_block_editor_assets' ) );
	}

	/**
	 * Enqueue admin assets
	 *
	 * @return void
	 */
	public function enqueue_admin_assets() {
		$admin_page = \get_current_screen();
		
		// Enqueue admin styles
		$this->enqueue_admin_styles( $admin_page );
		
		// Enqueue admin scripts
		$this->enqueue_admin_scripts( $admin_page );
	}

	/**
	 * Enqueue block editor assets
	 *
	 * @return void
	 */
	public function enqueue_block_editor_assets() {
		// Enqueue block editor styles
		\wp_enqueue_style(
			FACIOJ_TEXTDOMAIN . '-block-editor-style',
			\plugins_url( 'assets/build/plugin-block.css', FACIOJ_PLUGIN_ABSOLUTE ),
			array( 'wp-edit-blocks' ),
			FACIOJ_VERSION
		);
		// Unregister editor script from block.json
		\wp_deregister_script( 'formular-af-citizenone-journalsystem-contact-form-editor-script' );

		// Enqueue block editor script
		\wp_enqueue_script(
			'formular-af-citizenone-journalsystem-contact-form-editor-script',
			\plugins_url( 'assets/build/plugin-block.js', FACIOJ_PLUGIN_ABSOLUTE ),
			array( 'wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n', 'wp-api-fetch' ),
			FACIOJ_VERSION,
			false
		);
		
		// Handle translations
		if ( function_exists( 'wp_set_script_translations' ) ) {
			\wp_set_script_translations(
				'formular-af-citizenone-journalsystem-contact-form-editor-script',
				'formular-af-citizenone-journalsystem',
				FACIOJ_PLUGIN_ROOT . 'languages'
			);
		}
		
		// Localize script with hCaptcha settings
		$options             = \get_option( FACIOJ_TEXTDOMAIN . '-settings' );
		$hcaptcha_site_key   = $options[FACIOJ_TEXTDOMAIN . '_hcaptcha_site_key'] ?? false;
		$hcaptcha_secret_key = $options[FACIOJ_TEXTDOMAIN . '_hcaptcha_secret_key'] ?? false;

		if ( $hcaptcha_site_key && $hcaptcha_secret_key ) {
			$hcaptcha_enabled = true;
		}
		
		\wp_localize_script(
			'formular-af-citizenone-journalsystem-contact-form-editor-script',
			'cfaBlockhCaptcha',
			array(
				'hCaptchaEnabled' => $hcaptcha_enabled,
				'hCaptchaSiteKey' => $hcaptcha_site_key,
			)
		);
	}

	/**
	 * Register and enqueue admin-specific style sheet.
	 *
	 * @param \WP_Screen $admin_page The current admin screen.
	 * @return void
	 */
	public function enqueue_admin_styles( $admin_page ) {
		// Main admin style
		\wp_enqueue_style(
			FACIOJ_TEXTDOMAIN . '-admin-style',
			\plugins_url( 'assets/build/plugin-admin.css', FACIOJ_PLUGIN_ABSOLUTE ),
			array( 'dashicons' ),
			FACIOJ_VERSION
		);
		
		// Settings page style
		if ( \is_null( $admin_page ) || 'toplevel_page_formular-af-citizenone-journalsystem' !== $admin_page->id ) {
			return;
		}

		\wp_enqueue_style(
			FACIOJ_TEXTDOMAIN . '-settings-style',
			\plugins_url( 'assets/build/plugin-settings.css', FACIOJ_PLUGIN_ABSOLUTE ),
			array( 'dashicons' ),
			FACIOJ_VERSION
		);
	}
    
	/**
	 * Register and enqueue admin-specific JavaScript.
	 *
	 * @param \WP_Screen $admin_page The current admin screen.
	 * @return void
	 */
	public function enqueue_admin_scripts( $admin_page ) {
		// Main admin script
		\wp_enqueue_script(
			FACIOJ_TEXTDOMAIN . '-settings-admin',
			\plugins_url( 'assets/build/plugin-admin.js', FACIOJ_PLUGIN_ABSOLUTE ),
			array(),
			FACIOJ_VERSION,
			true
		);
		
		// Settings page script
		if ( \is_null( $admin_page ) || 'toplevel_page_formular-af-citizenone-journalsystem' !== $admin_page->id ) {
			return;
		}

		\wp_enqueue_script(
			FACIOJ_TEXTDOMAIN . '-settings-script',
			\plugins_url( 'assets/build/plugin-settings.js', FACIOJ_PLUGIN_ABSOLUTE ),
			array( 'jquery-ui-tabs' ),
			FACIOJ_VERSION,
			true
		);
	}

}
