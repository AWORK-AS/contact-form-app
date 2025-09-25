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

	declare(strict_types=1);

	use Isolated\Symfony\Component\Finder\Finder;


	function getWpExcludedSymbols(string $fileName): mixed
	{
		$filePath = __DIR__.'/vendor/sniccowp/php-scoper-wordpress-excludes/generated/'.$fileName;
		$contents = file_get_contents($filePath);

		if(!$contents) {
			$contents = '';
		}

		return json_decode(
			$contents,
			true,
		);
	}

	$wpConstants = getWpExcludedSymbols('exclude-wordpress-constants.json');
    $wpClasses   = getWpExcludedSymbols('exclude-wordpress-classes.json');
    $wpFunctions = getWpExcludedSymbols('exclude-wordpress-functions.json');

	$my_excluded_classes = [
		'I18n_Notice',
		'I18n_Notice_WordPressOrg',
	];
	return array(
		// The prefix to apply to all namespaces. Use your unique plugin prefix.
		'prefix'             => 'mzaworkdk\Citizenone\Dependencies',
		// Files and folders to scope. Typically, you want to scope the 'vendor' directory.
		'finders'            => array(
			// @phpstan-ignore class.notFound
			Finder::create()
					->files()
					->ignoreVCS(true)
					->notName('/.*\\.(test|spec)\\.php/')
					->exclude([
						'test',
						'tests',
						'Test',
						'Tests',
					])
					->in( 'vendor' )
					->notPath([
						'/php-webdriver/',
						'/codeception/',
						'/phpunit/',
						'/wp-browser/',
						'/grumphp/',
						'/phpcs/',
						'/szepeviktor/',
					]),
			// Add other directories if needed.
		),
		'exclude-files' => [
			'ajax/**',
			'backend/**',
			'cli/**',
			'engine/**',
			'frontend/**',
			'integrations/**',
			'internals/**',
			'rest/**',
		],
		// Symbols (classes, functions, constants) that should NOT be prefixed.
		// This often includes WordPress core, PHP built-in symbols, or interfaces that need to remain global.
		'exclude-namespaces' => array(
			'WP',
			'Composer',
			'mzaworkdk\Citizenone',
		),
		// @phpstan-ignore argument.type
		'exclude-classes'    => array_merge($wpClasses, $my_excluded_classes),
		'exclude-functions'  => $wpFunctions,
		'exclude-constants'  => $wpConstants,
	);
