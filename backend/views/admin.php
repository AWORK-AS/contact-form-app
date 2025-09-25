<?php
/**
 * Represents the view for the administration dashboard.
 *
 * This includes the header, options, and other information that should provide
 * The User Interface to the end user.
 *
 * @package   mzaworkdk\Citizenone
 * @author    Mindell Zamora <mz@awork.dk>
 * @copyright 2025 AWORK A/S
 * @license   GPL 2.0+
 * @link      https://awork.dk
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

?>

<div class="wrap">

	<h2><?php esc_html_e( 'Formular af CitizenOne journalsystem', 'formular-af-citizenone-journalsystem' ); ?></h2>
	<?php
		settings_errors( 'facioj_messages' );
	?>
	<div id="tabs" class="settings-tab">
		<ul>
			<li><a href="#tabs-1"><?php esc_html_e( 'Configuration', 'formular-af-citizenone-journalsystem' ); ?></a></li>
			<li><a href="#tabs-2"><?php esc_html_e( 'Instructions', 'formular-af-citizenone-journalsystem' ); ?></a></li>
		</ul>
		<?php
		require_once plugin_dir_path( __FILE__ ) . 'formular-af-citizenone-journalsystem-settings.php';
		require_once plugin_dir_path( __FILE__ ) . 'instructions.php';
		?>
	</div>

	<div class="right-column-settings-page metabox-holder">

			<?php
			require_once plugin_dir_path( __FILE__ ) . 'connection-svg.php';
			?>

	</div>
</div>
