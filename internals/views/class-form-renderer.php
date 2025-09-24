<?php
/**
 * Shortcode form renderer.
 *
 * @package   mzaworkdk\Citizenone
 * @author    Mindell Zamora <mz@awork.dk>
 * @copyright 2025 AWORK A/S
 * @license   GPL 2.0+
 * @link      https://awork.dk
 */

namespace mzaworkdk\Citizenone\Internals\Views;

/**
 * Shortcode form renderer.
 */
class Form_Renderer {

	/**
	 * Render the contact form.
	 */
	public function render_form(): string|false {
		$color          = \apply_filters( 'facioj_color', '#205E77' );
		$headline       = \apply_filters( 'facioj_headline', __( 'Get in Touch With Us', 'formular-af-citizenone-journalsystem' ) );
		$btn_color      = \apply_filters( 'facioj_button_color', '#42aed9' );
		$btn_text_color = \apply_filters( 'facioj_button_text_color', '#ffffff' );

		$hcaptcha_enabled = $this->is_hcaptcha_enabled();
		$form_id          = 'facioj-form-' . uniqid();

		ob_start();
		?>
		<div class="facioj-contact-form-inherit-parent">
			<div class="facioj-contact-form" id="<?php echo \esc_attr( $form_id ); ?>">
				<?php $this->render_headline( $color, $headline ); ?>
				<form class="facioj-form" id="<?php echo \esc_attr( $form_id ); ?>-form">
					<?php $this->render_form_fields( $hcaptcha_enabled ); ?>
					<?php $this->render_form_footer( $btn_color, $btn_text_color ); ?>
				</form>
				<div class="facioj-message"></div>
				<?php $this->render_powered_by(); ?>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Check if hCaptcha is enabled.
	 */
	private function is_hcaptcha_enabled(): bool {
		$opts                = \facioj_get_settings();
		$hcaptcha_site_key   = $opts['facioj_hcaptcha_site_key'] ?? '';
		$hcaptcha_secret_key = $opts['facioj_hcaptcha_secret_key'] ?? '';
		return ! empty( $hcaptcha_site_key ) && ! empty( $hcaptcha_secret_key );
	}

	/**
	 * Render the form headline.
	 *
	 * @param string $color   Color code.
	 * @param string $headline Headline text.
	 */
	private function render_headline( string $color, string $headline ): void {
		?>
		<h3 style="color: <?php echo \esc_attr( $color ); ?>" class="facioj-headline-config">
			<?php echo \esc_html( $headline ); ?>
		</h3>
		<?php
	}

	/**
	 * Render all form fields.
	 *
	 * @param bool $hcaptcha_enabled Whether hCaptcha is enabled.
	 */
	private function render_form_fields( bool $hcaptcha_enabled ): void {
		?>
		<div class="facioj-form-grid">
		<?php
		$this->render_text_input( 'name', __( 'Name', 'formular-af-citizenone-journalsystem' ), true, 'full' );
		$this->render_text_input( 'company', __( 'Company', 'formular-af-citizenone-journalsystem' ), true, 'full' );
		$this->render_email_input();
		$this->render_phone_input();
		$this->render_textarea();

		if ( $hcaptcha_enabled ) {
			$this->render_hcaptcha();
		}

		?>
		</div>
		<?php
	}

	/**
	 * Render a text input field.
	 *
	 * @param string $name        Field name.
	 * @param string $placeholder Placeholder text.
	 * @param bool   $required    Whether the field is required.
	 * @param string $size        Field size (empty or 'full').
	 */
	private function render_text_input(
		string $name,
		string $placeholder,
		bool $required = false,
		string $size = ''
	): void {
		$class = 'facioj-form-group';

		if ( ! empty( $size ) ) {
			$class .= ' facioj-form-group--' . $size;
		}

		?>
		<div class="<?php echo \esc_attr( $class ); ?>">
			<input
				type="text"
				id="facioj-<?php echo \esc_attr( $name ); ?>"
				name="<?php echo \esc_attr( $name ); ?>"
				placeholder="<?php echo \esc_attr( $placeholder ); ?>"
				<?php

				if ( $required ) {
					echo 'required';
				}

				?>
			>
		</div>
		<?php
	}

	/**
	 * Render email input field.
	 */
	private function render_email_input(): void {
		?>
		<div class="facioj-form-group">
			<input
				type="email"
				id="facioj-email"
				name="email"
				placeholder="<?php \esc_attr_e( 'Email', 'formular-af-citizenone-journalsystem' ); ?>"
				required
			>
		</div>
		<?php
	}

	/**
	 * Render phone input field.
	 */
	private function render_phone_input(): void {
		?>
		<div class="facioj-form-group">
			<input
				type="tel"
				id="facioj-phone"
				name="phone"
				placeholder="<?php \esc_attr_e( 'Phone', 'formular-af-citizenone-journalsystem' ); ?>"
				required
			>
		</div>
		<?php
	}

	/**
	 * Render textarea field.
	 */
	private function render_textarea(): void {
		?>
		<div class="facioj-form-group facioj-form-group--full">
			<textarea
				id="facioj-message"
				name="message"
				placeholder="<?php \esc_attr_e( 'Message', 'formular-af-citizenone-journalsystem' ); ?>"
				required
			></textarea>
		</div>
		<?php
	}

	/**
	 * Render hCaptcha field.
	 */
	private function render_hcaptcha(): void {
		$opts              = \facioj_get_settings();
		$hcaptcha_site_key = $opts['facioj_hcaptcha_site_key'] ?? '';
		?>
		<div class="facioj-form-group facioj-form-group--full">
			<div class="h-captcha" data-sitekey="<?php echo \esc_attr( $hcaptcha_site_key ); ?>"></div>
		</div>
		<?php
	}

	/**
	 * Render form footer with submit button.
	 *
	 * @param string $btn_color       Button color.
	 * @param string $btn_text_color  Button text color.
	 */
	private function render_form_footer( string $btn_color, string $btn_text_color ): void {
		?>
		<div class="facioj-form-footer">
			<button
				type="submit"
				class="facioj-submit-btn"
				style="background-color:<?php echo \esc_attr( $btn_color ); ?>;color:<?php echo \esc_attr( $btn_text_color ); ?>"
			>
				<?php \esc_attr_e( 'Submit', 'formular-af-citizenone-journalsystem' ); ?>
			</button>
		</div>
		<?php
	}

	/**
	 * Render powered by section.
	 */
	private function render_powered_by(): void {
		?>
		<div class="facioj-powered-by">
			Formular af
			<a href="https://citizenone.dk" target="_blank">
				CitizenOne - Journalsystem med alt inklusiv
			</a>
		</div>
		<?php
	}
}
