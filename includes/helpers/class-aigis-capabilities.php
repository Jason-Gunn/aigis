<?php
/**
 * Capability definitions and role setup.
 *
 * All 22 AIGIS capabilities are defined as constants here.
 * Call AIGIS_Capabilities::register_roles_and_caps() during plugin activation.
 *
 * @package AI_Governance_Suite
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class AIGIS_Capabilities {

	// -----------------------------------------------------------------------
	// Capability constants
	// -----------------------------------------------------------------------

	// AI Inventory
	const MANAGE_AI_INVENTORY  = 'aigis_manage_ai_inventory';
	const VIEW_AI_INVENTORY    = 'aigis_view_ai_inventory';

	// Prompt management
	const MANAGE_PROMPTS       = 'aigis_manage_prompts';
	const APPROVE_PROMPTS      = 'aigis_approve_prompts';
	const USE_PROMPTS          = 'aigis_use_prompts';

	// Skill management
	const MANAGE_SKILLS        = 'aigis_manage_skills';
	const APPROVE_SKILLS       = 'aigis_approve_skills';
	const USE_SKILLS           = 'aigis_use_skills';
	const VIEW_SKILLS          = 'aigis_view_skills';

	// Policy management
	const MANAGE_POLICIES      = 'aigis_manage_policies';
	const APPROVE_POLICIES     = 'aigis_approve_policies';
	const VIEW_POLICIES        = 'aigis_view_policies';

	// Workflows
	const MANAGE_WORKFLOWS     = 'aigis_manage_workflows';
	const VIEW_WORKFLOWS       = 'aigis_view_workflows';

	// Incidents
	const MANAGE_INCIDENTS     = 'aigis_manage_incidents';
	const VIEW_INCIDENTS       = 'aigis_view_incidents';

	// Analytics & reporting
	const VIEW_ANALYTICS       = 'aigis_view_analytics';
	const EXPORT_DATA          = 'aigis_export_data';

	// Settings & admin
	const MANAGE_SETTINGS      = 'aigis_manage_settings';
	const MANAGE_API_KEYS      = 'aigis_manage_api_keys';
	const VIEW_AUDIT_LOG       = 'aigis_view_audit_log';

	// Stress-testing & evaluation
	const RUN_STRESS_TESTS     = 'aigis_run_stress_tests';
	const MANAGE_EVAL          = 'aigis_manage_eval';
	const VIEW_EVAL            = 'aigis_view_eval';

	// Cost management
	const MANAGE_BUDGETS       = 'aigis_manage_budgets';
	const VIEW_COSTS           = 'aigis_view_costs';

	// -----------------------------------------------------------------------
	// Role definitions
	// -----------------------------------------------------------------------

	/**
	 * Custom roles: slug => [ display_name, capabilities[] ]
	 */
	private static function role_definitions(): array {
		return [
			'aigis_super_admin' => [
				'name' => __( 'AI Super Admin', 'ai-governance-suite' ),
				'caps' => array_merge( [ 'read' ], self::all_caps() ),
			],
			'aigis_ai_manager' => [
				'name' => __( 'AI Manager', 'ai-governance-suite' ),
				'caps' => [
					'read',
					self::MANAGE_AI_INVENTORY,
					self::VIEW_AI_INVENTORY,
					self::MANAGE_PROMPTS,
					self::APPROVE_PROMPTS,
					self::USE_PROMPTS,
					self::MANAGE_SKILLS,
					self::APPROVE_SKILLS,
					self::USE_SKILLS,
					self::VIEW_SKILLS,
					self::MANAGE_POLICIES,
					self::APPROVE_POLICIES,
					self::VIEW_POLICIES,
					self::MANAGE_WORKFLOWS,
					self::VIEW_WORKFLOWS,
					self::MANAGE_INCIDENTS,
					self::VIEW_INCIDENTS,
					self::VIEW_ANALYTICS,
					self::EXPORT_DATA,
					self::VIEW_AUDIT_LOG,
					self::MANAGE_EVAL,
					self::VIEW_EVAL,
					self::MANAGE_BUDGETS,
					self::VIEW_COSTS,
				],
			],
			'aigis_ai_developer' => [
				'name' => __( 'AI Developer', 'ai-governance-suite' ),
				'caps' => [
					'read',
					self::VIEW_AI_INVENTORY,
					self::MANAGE_PROMPTS,
					self::USE_PROMPTS,
					self::MANAGE_SKILLS,
					self::USE_SKILLS,
					self::VIEW_SKILLS,
					self::VIEW_POLICIES,
					self::MANAGE_WORKFLOWS,
					self::VIEW_WORKFLOWS,
					self::VIEW_ANALYTICS,
					self::RUN_STRESS_TESTS,
					self::MANAGE_EVAL,
					self::VIEW_EVAL,
					self::VIEW_COSTS,
				],
			],
			'aigis_ai_reviewer' => [
				'name' => __( 'AI Reviewer', 'ai-governance-suite' ),
				'caps' => [
					'read',
					self::VIEW_AI_INVENTORY,
					self::APPROVE_PROMPTS,
					self::APPROVE_SKILLS,
					self::VIEW_SKILLS,
					self::VIEW_POLICIES,
					self::VIEW_WORKFLOWS,
					self::VIEW_INCIDENTS,
					self::VIEW_ANALYTICS,
					self::VIEW_AUDIT_LOG,
					self::VIEW_EVAL,
				],
			],
		];
	}

	/**
	 * All 22 capability slugs as a flat array.
	 *
	 * @return string[]
	 */
	public static function all_caps(): array {
		return [
			self::MANAGE_AI_INVENTORY,
			self::VIEW_AI_INVENTORY,
			self::MANAGE_PROMPTS,
			self::APPROVE_PROMPTS,
			self::USE_PROMPTS,
			self::MANAGE_SKILLS,
			self::APPROVE_SKILLS,
			self::USE_SKILLS,
			self::VIEW_SKILLS,
			self::MANAGE_POLICIES,
			self::APPROVE_POLICIES,
			self::VIEW_POLICIES,
			self::MANAGE_WORKFLOWS,
			self::VIEW_WORKFLOWS,
			self::MANAGE_INCIDENTS,
			self::VIEW_INCIDENTS,
			self::VIEW_ANALYTICS,
			self::EXPORT_DATA,
			self::MANAGE_SETTINGS,
			self::MANAGE_API_KEYS,
			self::VIEW_AUDIT_LOG,
			self::RUN_STRESS_TESTS,
			self::MANAGE_EVAL,
			self::VIEW_EVAL,
			self::MANAGE_BUDGETS,
			self::VIEW_COSTS,
		];
	}

	// -----------------------------------------------------------------------
	// Registration methods
	// -----------------------------------------------------------------------

	/**
	 * Create the four custom roles and grant all caps to WP administrator.
	 * Called during plugin activation.
	 */
	public static function register_roles_and_caps(): void {
		foreach ( self::role_definitions() as $role_slug => $def ) {
			$cap_map = array_fill_keys( $def['caps'], true );

			if ( get_role( $role_slug ) === null ) {
				add_role( $role_slug, $def['name'], $cap_map );
			} else {
				// Ensure capabilities are current.
				$role = get_role( $role_slug );
				foreach ( $def['caps'] as $cap ) {
					$role->add_cap( $cap );
				}
			}
		}

		// Grant all capabilities to site administrators.
		$admin_role = get_role( 'administrator' );
		if ( $admin_role ) {
			foreach ( self::all_caps() as $cap ) {
				$admin_role->add_cap( $cap );
			}
		}
	}

	/**
	 * Remove all AIGIS capabilities from all roles and delete the four custom roles.
	 * Called during plugin uninstall.
	 */
	public static function remove_roles_and_caps(): void {
		foreach ( wp_roles()->roles as $role_slug => $role_data ) {
			$role = get_role( $role_slug );
			if ( ! $role ) {
				continue;
			}
			foreach ( self::all_caps() as $cap ) {
				$role->remove_cap( $cap );
			}
		}

		foreach ( array_keys( self::role_definitions() ) as $role_slug ) {
			remove_role( $role_slug );
		}
	}
}
