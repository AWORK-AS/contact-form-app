<?php
define( 'FACIOJ_PLUGIN_ABSOLUTE', __FILE__ );
define( 'FACIOJ_PLUGIN_ROOT', __DIR__ );
define( 'FACIOJ_TEXTDOMAIN', 'formular-af-citizenone-journalsystem' );
define( 'FACIOJ_VERSION', '1.2.0' );
define( 'FACIOJ_MIN_PHP_VERSION', '7.4' );
define( 'FACIOJ_WP_VERSION', '5.8' );
define( 'FACIOJ_PLUGIN_API_URL', 'https://appserver.citizenone.dk/api' );
define( 'FACIOJ_PLUGIN_API_NAME', 'CitizenOne journalsystem' );
define( 'FACIOJ_NAME', 'Formular af CitizenOne journalsystem' );



// Define the prefix you use in scoper.inc.php
$prefix = 'mzaworkdk\\Citizenone\\Dependencies';

// Define the classes that we know are scoped but are used in the source code.
// The format is: 'Original\Class\Name' => 'Scoped\Class\Name'
$class_aliases = [
    $prefix . '\\Micropackage\\Requirements\\Requirements' => 'Micropackage\\Requirements\\Requirements',
    $prefix . '\\Inpsyde\\WpContext' => 'Inpsyde\\WpContext',
	// Add other classes that are directly called in your code here.
    // For example, if you use WPDesk Notice:
    // 'WPDesk_Notice' => $prefix . '\\WPDesk_Notice',
];

// Register the aliases so PHPStan can understand them.
foreach ( $class_aliases as $original => $alias ) {
    if ( ! class_exists( $original ) && class_exists( $alias ) ) {
        class_alias( $alias, $original );
    }
}

// For classes that you did NOT scope (like I18n_Notice),
// but PHPStan might look for them with the prefix.
$global_classes = [
    'I18n_Notice',
    'I18n_Notice_WordPressOrg',
];

foreach ( $global_classes as $class ) {
    // This ensures that if the scoped version is looked for,
    // it will point to the global version.
    if ( ! class_exists( $prefix . '\\' . $class ) && class_exists( $class ) ) {
        class_alias( $class, $prefix . '\\' . $class );
    }
}
