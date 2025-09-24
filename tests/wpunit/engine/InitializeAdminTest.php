<?php

namespace mzaworkdk\Citizenone\Tests\WPUnit;

class InitializeAdminTest extends \Codeception\TestCase\WPTestCase {
	/**
	 * @var string
	 */
	protected $root_dir;

	public function setUp(): void {
		parent::setUp();

		// your set up methods here
		$this->root_dir = dirname( dirname( dirname( __FILE__ ) ) );

		$user_id = $this->factory->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $user_id );
		set_current_screen( 'edit.php' );
	}

	public function tearDown(): void {
		parent::tearDown();
	}

	/**
	 * @test
	 * it should be admin
	 */
	public function it_should_be_admin() {
		add_filter( 'wp_doing_ajax', '__return_false' );
		do_action('plugins_loaded');

		$classes   = array();
		$classes[] = 'mzaworkdk\Citizenone\Internals\PostTypes';
		$classes[] = 'mzaworkdk\Citizenone\Internals\Shortcode';
		$classes[] = 'mzaworkdk\Citizenone\Integrations\Cron';
		$classes[] = 'mzaworkdk\Citizenone\Integrations\Template';
		$classes[] = 'mzaworkdk\Citizenone\Integrations\Widgets\My_Recent_Posts_Widget';
		$classes[] = 'mzaworkdk\Citizenone\Backend\ActDeact';
		$classes[] = 'mzaworkdk\Citizenone\Backend\Enqueue';
		$classes[] = 'mzaworkdk\Citizenone\Backend\ImpExp';
		$classes[] = 'mzaworkdk\Citizenone\Backend\Notices';
		$classes[] = 'mzaworkdk\Citizenone\Backend\Pointers';
		$classes[] = 'mzaworkdk\Citizenone\Backend\Settings_Page';

		$all_classes = get_declared_classes();
		foreach( $classes as $class ) {
			$this->assertTrue( in_array( $class, $all_classes ) );
		}
	}

	/**
	 * @test
	 * it should be ajax
	 */
	public function it_should_be_admin_ajax() {
		add_filter( 'wp_doing_ajax', '__return_true' );
		do_action('plugins_loaded');

		$classes   = array();
		$classes[] = 'mzaworkdk\Citizenone\Ajax\Ajax';
		$classes[] = 'mzaworkdk\Citizenone\Ajax\Ajax_Admin';

		$all_classes = get_declared_classes();
		foreach( $classes as $class ) {
			$this->assertTrue( in_array( $class, $all_classes ) );
		}
	}

}
