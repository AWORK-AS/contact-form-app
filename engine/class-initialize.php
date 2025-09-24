<?php
/**
 * Formular af CitizenOne
 *
 * @package   mzaworkdk\Citizenone
 * @author    Mindell Zamora <mz@awork.dk>
 * @copyright 2025 AWORK A/S
 * @license   GPL 2.0+
 * @link      https://awork.dk
 */

namespace mzaworkdk\Citizenone\Engine;

use mzaworkdk\Citizenone\Engine;

/**
 * Formular af CitizenOne Initializer
 */
class Initialize {

	/**
	 * List of class to initialize.
	 *
	 * @var array
	 */
	public $classes = array();

	/**
	 * Instance of this Context.
	 *
	 * @var object
	 */
	protected $content = null;

	/**
	 * Composer autoload file list.
	 *
	 * @var \Composer\Autoload\ClassLoader
	 */
	private $composer;

	/**
	 * The Constructor that load the entry classes
	 *
	 * @param \Composer\Autoload\ClassLoader $composer Composer autoload output.
	 * @since 1.0.0
	 */
	public function __construct( \Composer\Autoload\ClassLoader $composer ) {
		$this->content  = new Engine\Context();
		$this->composer = $composer;

		$this->get_classes( 'Integrations' );

		if ( $this->content->request( 'cli' ) ) {
			$this->get_classes( 'Cli' );
		}

		if ( $this->content->request( 'ajax' ) ) {
			$this->get_classes( 'Ajax' );
		}

		if ( $this->content->request( 'backend' ) ) {
			$this->get_classes( 'Backend' );
		}

		if ( $this->content->request( 'frontend' ) ) {
			$this->get_classes( 'Frontend' );
		}

		if ( $this->content->request( 'rest' ) ) {
			$this->get_classes( 'Rest' );
		}

		$this->get_classes( 'Internals' );
		$this->load_classes();
		\add_action( 'admin_notices', array( $this, 'display_admin_notices' ) );
	}

	/**
	 * Initialize all the classes.
	 *
	 * @since 1.0.0
	 * @throws \Exception If class not initialized.
	 * @return void If successful.
	 */
	private function load_classes() {
		$this->classes = \apply_filters( 'facioj_classes_to_execute', $this->classes );

		foreach ( $this->classes as $class ) {
			try {
				$this->initialize_plugin_class( $class );
			} catch ( \Throwable $err ) {
				\do_action( 'facioj_initialize_failed', $err );

				if ( \WP_DEBUG ) {
					throw new \Exception( $err->getMessage() ); //phpcs:ignore
				}
			}
		}
	}

	/**
	 * Validate the class and initialize it.
	 *
	 * @param class-string $classtovalidate Class name to validate.
	 * @since 1.0.0
	 * @SuppressWarnings("MissingImport")
	 * @return void
	 */
	private function initialize_plugin_class( $classtovalidate ) {
		$reflection = new \ReflectionClass( $classtovalidate );

		if ( $reflection->isAbstract() ) {
			return;
		}

		if ( strpos( $classtovalidate, 'Widgets' ) !== false ) {
			\add_action(
				'widgets_init',
				function () use ( $classtovalidate ) {
					$temp = new $classtovalidate();

					if ( ! \method_exists( $temp, 'initialize' ) ) {
						return;
					}

					$temp->initialize();
					\add_filter(
						'facioj_instance_' . $classtovalidate,
						function () use ( $temp ) {
							return $temp;
						}
					);
				}
			);
		} else {
			$temp = new $classtovalidate();

			if ( ! \method_exists( $temp, 'initialize' ) ) {
				return;
			}

			$temp->initialize();

			\add_filter(
				'facioj_instance_' . $classtovalidate,
				function () use ( $temp ) {
					return $temp;
				}
			);
		}
	}

	/**
	 * Based on the folder loads the classes automatically using the Composer autoload to detect the classes of a Namespace.
	 *
	 * @param string $namespacetofind Class name to find.
	 * @since 1.0.0
	 * @return array Return the classes.
	 */
	private function get_classes( string $namespacetofind ) {
		$prefix          = $this->composer->getPrefixesPsr4();
		$classmap        = $this->composer->getClassMap();
		$namespacetofind = 'mzaworkdk\\Citizenone\\' . $namespacetofind;

		// In case composer has autoload optimized.
		if ( isset( $classmap['mzaworkdk\\Citizenone\\Engine\\Initialize'] ) ) {
			$classes = \array_keys( $classmap );

			foreach ( $classes as $class ) {
				if ( 0 !== \strncmp( (string) $class, $namespacetofind, \strlen( $namespacetofind ) ) ) {
					continue;
				}

				$this->classes[] = $class;
			}

			return $this->classes;
		}

		$namespacetofind .= '\\';

		// In case composer is not optimized.
		if ( isset( $prefix[ $namespacetofind ] ) ) {
			$folder    = $prefix[ $namespacetofind ][0];
			$php_files = $this->scandir( $folder );
			$this->find_classes( $php_files, $folder, $namespacetofind );

			// ✅ SOLUTION: Set a transient to show an admin notice.
			// A transient is a temporary option in the database.
			// We'll only set it if it doesn't exist yet.
			if ( false === get_transient( 'facioj_autoloader_not_optimized' ) ) {
				// We'll set it to expire in one week so it doesn't constantly check.
				set_transient( 'facioj_autoloader_not_optimized', true, WEEK_IN_SECONDS );
			}

			return $this->classes;
		}

		return $this->classes;
	}

	/**
	 * Get php files inside the folder/subfolder that will be loaded.
	 * This class is used only when Composer is not optimized.
	 *
	 * @param string $folder Path.
	 * @param string $exclude_str Exclude all files whose filename contain this. Defaults to `~`.
	 * @since 1.0.0
	 * @return array List of files.
	 */
	private function scandir( string $folder, string $exclude_str = '~' ) {
		// Also exclude these specific scandir findings.
		$blacklist = array( '..', '.', 'index.php' );
		// Scan for files.
		$temp_files = \scandir( $folder );
		$files      = array();

		if ( \is_array( $temp_files ) ) {
			foreach ( $temp_files as $temp_file ) {
				// Only include filenames that DO NOT contain the excluded string and ARE NOT on the scandir result blacklist.
				if (
					false !== \mb_strpos( $temp_file, $exclude_str )
					|| '.' === $temp_file[0]
					|| \in_array( $temp_file, $blacklist, true )
				) {
					continue;
				}

				$files[] = $temp_file;
			}
		}

		return $files;
	}

	/**
	 * Load namespace classes by files.
	 *
	 * @param array  $php_files List of files with the Class.
	 * @param string $folder Path of the folder.
	 * @param string $base Namespace base.
	 * @since 1.0.0
	 * @return void
	 */
	private function find_classes( array $php_files, string $folder, string $base ) {
		foreach ( $php_files as $php_file ) {
			$class_name = \substr( $php_file, 0, -4 );
			if ( 0 === \strpos( $class_name, 'class-' ) ) {
				$class_name = \substr( $class_name, 6 );
			}

			$path       = $folder . '/' . $php_file;
			$class_name = \str_replace( '-', ' ', $class_name );
			$class_name = \str_replace( ' ', '_', \ucwords( $class_name ) );

			if ( \is_file( $path ) ) {
				$this->classes[] = $base . $class_name;

				continue;
			}

			// Verify the Namespace level.
			if ( \substr_count( $base . $class_name, '\\' ) < 2 ) {
				continue;
			}

			if ( ! \is_dir( $path ) || \strtolower( $php_file ) === $php_file ) {
				continue;
			}

			$sub_php_files = $this->scandir( $folder . '/' . $php_file );
			$this->find_classes( $sub_php_files, $folder . '/' . $php_file, $base . $php_file . '\\' );
		}
	}

	/**
	 * Display admin notices when needed.
	 */
	public function display_admin_notices(): void {
		// Check if the user has the capability to install plugins.
		if ( ! current_user_can( 'install_plugins' ) ) {
			return;
		}

		// Check if our transient is set.
		if ( get_transient( 'facioj_autoloader_not_optimized' ) ) {
			$message = sprintf(
				// Use esc_html() for security.
				/* translators: %s is a link to dismiss the notice */
				\esc_html__( 'For better performance, the Formular af CitizenOne journalsystem plugin recommends regenerating the autoloader. This is a developer-level task. %s', 'formular-af-citizenone-journalsystem' ),
				// Add a link to dismiss the notice.
				'<a href="' . esc_url( add_query_arg( 'facioj_dismiss_notice', 'autoloader_warning' ) ) . '">' . esc_html__( 'Dismiss this notice', 'formular-af-citizenone-journalsystem' ) . '</a>'
			);

			// Show the notice. 'notice-warning' gives a yellow color.
			echo '<div class="notice notice-warning"><p>' . $message . '</p></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			// We'll ignore PHPCS here because we built the $message with escaping.
		}
	}
}
