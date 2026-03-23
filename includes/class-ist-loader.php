<?php
/**
 * Bootstraps and wires up all plugin components.
 *
 * @package INC_Stats_Tracker
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class IST_Loader {

	/**
	 * Registered actions.
	 *
	 * @var array
	 */
	protected array $actions = array();

	/**
	 * Registered filters.
	 *
	 * @var array
	 */
	protected array $filters = array();

	/**
	 * Load all dependencies and register components.
	 */
	public function __construct() {
		$this->load_dependencies();
	}

	/**
	 * Require all component files.
	 */
	private function load_dependencies(): void {
		// Core.
		require_once IST_PLUGIN_DIR . 'includes/class-ist-db.php';
		require_once IST_PLUGIN_DIR . 'includes/class-ist-capabilities.php';

		// Models.
		require_once IST_PLUGIN_DIR . 'includes/models/class-ist-model-tyfcb.php';
		require_once IST_PLUGIN_DIR . 'includes/models/class-ist-model-referral.php';
		require_once IST_PLUGIN_DIR . 'includes/models/class-ist-model-connect.php';

		// Services — members must load first; other services depend on it.
		require_once IST_PLUGIN_DIR . 'includes/services/class-ist-service-members.php';
		require_once IST_PLUGIN_DIR . 'includes/services/class-ist-service-tyfcb.php';
		require_once IST_PLUGIN_DIR . 'includes/services/class-ist-service-referrals.php';
		require_once IST_PLUGIN_DIR . 'includes/services/class-ist-service-connects.php';

		// Import / Export.
		require_once IST_PLUGIN_DIR . 'includes/import-export/class-ist-importer.php';
		require_once IST_PLUGIN_DIR . 'includes/import-export/class-ist-exporter.php';

		// Admin.
		if ( is_admin() ) {
			require_once IST_PLUGIN_DIR . 'admin/class-ist-admin.php';
			require_once IST_PLUGIN_DIR . 'admin/class-ist-admin-tyfcb.php';
			require_once IST_PLUGIN_DIR . 'admin/class-ist-admin-referrals.php';
			require_once IST_PLUGIN_DIR . 'admin/class-ist-admin-connects.php';
			require_once IST_PLUGIN_DIR . 'admin/class-ist-admin-members.php';
			require_once IST_PLUGIN_DIR . 'admin/class-ist-admin-reports.php';
		}

		// Frontend.
		require_once IST_PLUGIN_DIR . 'frontend/class-ist-frontend.php';
		require_once IST_PLUGIN_DIR . 'frontend/class-ist-forms.php';
	}

	/**
	 * Add an action to the collection.
	 */
	public function add_action( string $hook, object $component, string $callback, int $priority = 10, int $accepted_args = 1 ): void {
		$this->actions[] = compact( 'hook', 'component', 'callback', 'priority', 'accepted_args' );
	}

	/**
	 * Add a filter to the collection.
	 */
	public function add_filter( string $hook, object $component, string $callback, int $priority = 10, int $accepted_args = 1 ): void {
		$this->filters[] = compact( 'hook', 'component', 'callback', 'priority', 'accepted_args' );
	}

	/**
	 * Register all collected hooks with WordPress.
	 */
	public function run(): void {
		foreach ( $this->filters as $hook ) {
			add_filter( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
		}
		foreach ( $this->actions as $hook ) {
			add_action( $hook['hook'], array( $hook['component'], $hook['callback'] ), $hook['priority'], $hook['accepted_args'] );
		}
	}
}
