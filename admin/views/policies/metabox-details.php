<?php
/**
 * Policy CPT — Details metabox view.
 *
 * Variables: $post, $version, $effective_date, $review_date, $owner
 *
 * @package AI_Governance_Suite
 */
if ( ! defined( 'ABSPATH' ) ) exit;
wp_nonce_field( 'aigis_save_policy_details', 'aigis_policy_details_nonce' );
?>
<table class="form-table" role="presentation">
	<tr>
		<th scope="row"><label for="aigis_policy_version"><?php esc_html_e( 'Version', 'ai-governance-suite' ); ?></label></th>
		<td><input type="text" id="aigis_policy_version" name="aigis_policy_version" class="regular-text"
			value="<?php echo esc_attr( $version ); ?>" placeholder="e.g. 1.0"></td>
	</tr>
	<tr>
		<th scope="row"><label for="aigis_effective_date"><?php esc_html_e( 'Effective Date', 'ai-governance-suite' ); ?></label></th>
		<td><input type="date" id="aigis_effective_date" name="aigis_effective_date"
			value="<?php echo esc_attr( $effective_date ); ?>"></td>
	</tr>
	<tr>
		<th scope="row"><label for="aigis_review_date"><?php esc_html_e( 'Next Review Date', 'ai-governance-suite' ); ?></label></th>
		<td><input type="date" id="aigis_review_date" name="aigis_review_date"
			value="<?php echo esc_attr( $review_date ); ?>"></td>
	</tr>
	<tr>
		<th scope="row"><label for="aigis_policy_owner"><?php esc_html_e( 'Policy Owner', 'ai-governance-suite' ); ?></label></th>
		<td><input type="text" id="aigis_policy_owner" name="aigis_policy_owner" class="regular-text"
			value="<?php echo esc_attr( $owner ); ?>"></td>
	</tr>
</table>
