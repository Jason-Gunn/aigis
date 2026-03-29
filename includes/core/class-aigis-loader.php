<?php
/**
 * Registers and dispatches WordPress hooks.
 *
 * Maintains arrays of actions, filters, and REST route registrations.
 * Components call add_action() / add_filter() on this loader, then
 * the plugin calls run() once to wire everything up.
 *
 * @package AI_Governance_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AIGIS_Loader {

	/** @var array[] Registered action hooks. */
	protected array $actions = [];

	/** @var array[] Registered filter hooks. */
	protected array $filters = [];

	/** @var AIGIS_REST_Controller[] REST controllers that implement register_routes(). */
	protected array $rest_routes = [];

	/**
	 * Add an action hook.
	 *
	 * @param string $hook          The WordPress action hook name.
	 * @param object $component     The object instance.
	 * @param string $callback      The method name on $component.
	 * @param int    $priority      Optional. Hook priority. Default 10.
	 * @param int    $accepted_args Optional. Number of accepted arguments. Default 1.
	 */
	public function add_action(
		string $hook,
		object $component,
		string $callback,
		int $priority = 10,
		int $accepted_args = 1
	): void {
		$this->actions[] = compact( 'hook', 'component', 'callback', 'priority', 'accepted_args' );
	}

	/**
	 * Add a filter hook.
	 *
	 * @param string $hook          The WordPress filter hook name.
	 * @param object $component     The object instance.
	 * @param string $callback      The method name on $component.
	 * @param int    $priority      Optional. Hook priority. Default 10.
	 * @param int    $accepted_args Optional. Number of accepted arguments. Default 1.
	 */
	public function add_filter(
		string $hook,
		object $component,
		string $callback,
		int $priority = 10,
		int $accepted_args = 1
	): void {
		$this->filters[] = compact( 'hook', 'component', 'callback', 'priority', 'accepted_args' );
	}

	/**
	 * Register a REST API controller.
	 *
	 * @param AIGIS_REST_Controller $controller Controller to register.
	 */
	public function add_rest_controller( object $controller ): void {
		$this->rest_routes[] = $controller;
	}

	/**
	 * Run the loader: register all collected hooks with WordPress.
	 */
	public function run(): void {
		foreach ( $this->actions as $action ) {
			add_action(
				$action['hook'],
				[ $action['component'], $action['callback'] ],
				$action['priority'],
				$action['accepted_args']
			);
		}

		foreach ( $this->filters as $filter ) {
			add_filter(
				$filter['hook'],
				[ $filter['component'], $filter['callback'] ],
				$filter['priority'],
				$filter['accepted_args']
			);
		}

		// REST routes are registered via the rest_api_init action.
		if ( ! empty( $this->rest_routes ) ) {
			add_action( 'rest_api_init', function () {
				foreach ( $this->rest_routes as $controller ) {
					$controller->register_routes();
				}
			} );
		}
	}
}
