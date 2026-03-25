<?php
/**
 * Workflow CPT — Node Registry metabox view.
 *
 * Variables: $post, $nodes (array), $models (array from inventory)
 *
 * @package AI_Governance_Suite
 */
if ( ! defined( 'ABSPATH' ) ) exit;
?>
<p class="description"><?php esc_html_e( 'Register each node in the workflow and assign it a model.', 'ai-governance-suite' ); ?></p>

<input type="hidden" name="aigis_workflow_nodes" id="aigis-workflow-nodes-json"
	value="<?php echo esc_attr( wp_json_encode( $nodes ) ); ?>">

<table class="aigis-table widefat" id="aigis-nodes-table">
	<thead>
		<tr>
			<th><?php esc_html_e( 'Node ID', 'ai-governance-suite' ); ?></th>
			<th><?php esc_html_e( 'Label', 'ai-governance-suite' ); ?></th>
			<th><?php esc_html_e( 'Type', 'ai-governance-suite' ); ?></th>
			<th><?php esc_html_e( 'AI Model', 'ai-governance-suite' ); ?></th>
			<th><?php esc_html_e( 'Description', 'ai-governance-suite' ); ?></th>
			<th></th>
		</tr>
	</thead>
	<tbody>
		<?php foreach ( $nodes as $i => $node ) : ?>
		<tr class="aigis-node-row" data-idx="<?php echo esc_attr( $i ); ?>">
			<td><input type="text" class="node-id small-text" value="<?php echo esc_attr( $node['id'] ?? '' ); ?>"></td>
			<td><input type="text" class="node-label regular-text" value="<?php echo esc_attr( $node['label'] ?? '' ); ?>"></td>
			<td>
				<select class="node-type">
					<?php foreach ( [ 'inference', 'router', 'tool', 'human_in_loop', 'output' ] as $t ) : ?>
						<option value="<?php echo esc_attr( $t ); ?>" <?php selected( $node['type'] ?? '', $t ); ?>>
							<?php echo esc_html( ucwords( str_replace( '_', ' ', $t ) ) ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</td>
			<td>
				<select class="node-model-id">
					<option value=""><?php esc_html_e( '— None —', 'ai-governance-suite' ); ?></option>
					<?php foreach ( $models as $m ) : ?>
						<option value="<?php echo esc_attr( $m['id'] ); ?>" <?php selected( $node['model_id'] ?? '', (string) $m['id'] ); ?>>
							<?php echo esc_html( $m['model_name'] ); ?>
						</option>
					<?php endforeach; ?>
				</select>
			</td>
			<td><input type="text" class="node-description large-text" value="<?php echo esc_attr( $node['description'] ?? '' ); ?>"></td>
			<td><button type="button" class="button button-small aigis-remove-node">&times;</button></td>
		</tr>
		<?php endforeach; ?>
	</tbody>
</table>

<p style="margin-top:8px;">
	<button type="button" class="button" id="aigis-add-node">+ <?php esc_html_e( 'Add Node', 'ai-governance-suite' ); ?></button>
</p>

<script>
( function($) {
	var models = <?php echo wp_json_encode( array_map( fn($m) => [ 'id' => $m['id'], 'name' => $m['model_name'] ], $models ) ); ?>;
	var types  = ['inference','router','tool','human_in_loop','output'];

	function buildModelOptions( selected ) {
		var html = '<option value=""><?php echo esc_js( __( '— None —', 'ai-governance-suite' ) ); ?></option>';
		models.forEach( function(m) {
			html += '<option value="' + m.id + '"' + (String(m.id) === String(selected) ? ' selected' : '') + '>' + m.name + '</option>';
		} );
		return html;
	}

	function buildTypeOptions( selected ) {
		var html = '';
		types.forEach( function(t) {
			html += '<option value="' + t + '"' + (t === selected ? ' selected' : '') + '>' + t.replace(/_/g,' ').replace(/\b\w/g,c=>c.toUpperCase()) + '</option>';
		} );
		return html;
	}

	function syncJson() {
		var nodes = [];
		$('#aigis-nodes-table tbody tr').each( function() {
			nodes.push( {
				id         : $(this).find('.node-id').val(),
				label      : $(this).find('.node-label').val(),
				type       : $(this).find('.node-type').val(),
				model_id   : $(this).find('.node-model-id').val(),
				description: $(this).find('.node-description').val(),
			} );
		} );
		$('#aigis-workflow-nodes-json').val( JSON.stringify(nodes) );
	}

	$(document).on('click', '#aigis-add-node', function() {
		var $row = $('<tr class="aigis-node-row">' +
			'<td><input type="text" class="node-id small-text"></td>' +
			'<td><input type="text" class="node-label regular-text"></td>' +
			'<td><select class="node-type">' + buildTypeOptions('inference') + '</select></td>' +
			'<td><select class="node-model-id">' + buildModelOptions('') + '</select></td>' +
			'<td><input type="text" class="node-description large-text"></td>' +
			'<td><button type="button" class="button button-small aigis-remove-node">&times;</button></td>' +
		'</tr>');
		$('#aigis-nodes-table tbody').append($row);
	});

	$(document).on('click', '.aigis-remove-node', function() {
		$(this).closest('tr').remove();
		syncJson();
	});

	$(document).on('input change', '#aigis-nodes-table', syncJson);

} )(jQuery);
</script>
