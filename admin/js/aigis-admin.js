/**
 * AI Governance and Infrastructure Suite — Core Admin JS
 *
 * Handles:
 *   - Prompt sandbox test (AJAX)
 *   - Prompt promotion (AJAX)
 *   - Policy / incident status changes (AJAX)
 *   - API key copy-to-clipboard
 *   - Inbox unread badge refresh (polling)
 *   - Generic tab switching
 *
 * Depends on: jQuery, aigisAdmin (wp_localize_script)
 */

/* global aigisAdmin, jQuery */
( function ( $ ) {
	'use strict';

	const nonces       = aigisAdmin.nonces || {};
	const ajaxUrl      = aigisAdmin.ajaxUrl;
	const i18n         = aigisAdmin.i18n || {};

	// -----------------------------------------------------------------------
	// Tabs
	// -----------------------------------------------------------------------
	$( document ).on( 'click', '.aigis-tab[data-tab]', function ( e ) {
		e.preventDefault();
		const target = $( this ).data( 'tab' );
		$( '.aigis-tab' ).removeClass( 'aigis-tab-active' );
		$( this ).addClass( 'aigis-tab-active' );
		$( '.aigis-tab-panel' ).hide();
		$( '#aigis-tab-' + target ).show();
	} );

	// On page load, activate the first tab (or the one marked active).
	const $firstTab = $( '.aigis-tab.aigis-tab-active' ).first();
	if ( $firstTab.length ) {
		$firstTab.trigger( 'click' );
	} else {
		$( '.aigis-tab' ).first().trigger( 'click' );
	}

	// -----------------------------------------------------------------------
	// Prompt sandbox test
	// -----------------------------------------------------------------------
	$( document ).on( 'click', '#aigis-sandbox-run', function () {
		const $btn     = $( this );
		const postId   = $btn.data( 'post' );
		const $input   = $( '#aigis-sandbox-input' );
		const $output  = $( '#aigis-sandbox-output' );
		const $wrap    = $( '#aigis-sandbox-wrap' );

		if ( $input.val().trim() === '' ) {
			$output.removeClass( 'aigis-visible aigis-error' )
				.text( i18n.enterPrompt || 'Please enter a test input.' )
				.addClass( 'aigis-visible aigis-error' );
			return;
		}

		$wrap.addClass( 'aigis-loading' );
		$btn.prop( 'disabled', true );
		$output.removeClass( 'aigis-visible aigis-error' );

		$.post( ajaxUrl, {
			action   : 'aigis_sandbox_test',
			nonce    : nonces.sandboxTest,
			post_id  : postId,
			test_input: $input.val(),
		} )
		.done( function ( res ) {
			if ( res.success ) {
				$output.text( res.data.output || '(empty response)' )
					.addClass( 'aigis-visible' )
					.removeClass( 'aigis-error' );
			} else {
				$output.text( res.data || i18n.error || 'An error occurred.' )
					.addClass( 'aigis-visible aigis-error' );
			}
		} )
		.fail( function () {
			$output.text( i18n.networkError || 'Network error. Please try again.' )
				.addClass( 'aigis-visible aigis-error' );
		} )
		.always( function () {
			$wrap.removeClass( 'aigis-loading' );
			$btn.prop( 'disabled', false );
		} );
	} );

	// -----------------------------------------------------------------------
	// Prompt promotion
	// -----------------------------------------------------------------------
	$( document ).on( 'click', '.aigis-promote-btn', function () {
		const $btn       = $( this );
		const postId     = $btn.data( 'post' );
		const targetEnv  = $btn.data( 'env' );
		const $log       = $( '#aigis-promotion-log' );

		if ( ! confirm( i18n.confirmPromote || 'Promote this prompt to ' + targetEnv + '?' ) ) {
			return;
		}

		$btn.prop( 'disabled', true );

		$.post( ajaxUrl, {
			action    : 'aigis_promote_prompt',
			nonce     : nonces.promotePrompt,
			post_id   : postId,
			target_env: targetEnv,
		} )
		.done( function ( res ) {
			if ( res.success ) {
				$log.prepend( '<li>' + res.data.message + '</li>' );
				$btn.closest( '.aigis-promotion-row' )
					.find( '.aigis-badge' )
					.attr( 'class', 'aigis-badge aigis-badge-' + res.data.status )
					.text( res.data.status_label );
			} else {
				alert( res.data || i18n.error || 'Error.' );
			}
		} )
		.fail( function () {
			alert( i18n.networkError || 'Network error.' );
		} )
		.always( function () {
			$btn.prop( 'disabled', false );
		} );
	} );

	// -----------------------------------------------------------------------
	// Policy / incident status changes
	// -----------------------------------------------------------------------
	$( document ).on( 'click', '.aigis-status-btn', function () {
		const $btn      = $( this );
		const postId    = $btn.data( 'post' );
		const action    = $btn.data( 'action' );   // e.g. 'aigis_set_policy_status'
		const newStatus = $btn.data( 'status' );
		const label     = $btn.data( 'label' ) || newStatus;

		if ( ! confirm( i18n.confirmStatus || 'Set status to "' + label + '"?' ) ) {
			return;
		}

		$btn.prop( 'disabled', true );

		// Pick the correct nonce based on the AJAX action name.
		const statusNonce = action === 'aigis_set_incident_status'
			? nonces.incidentStatus
			: nonces.policyStatus;

		$.post( ajaxUrl, {
			action    : action,
			nonce     : statusNonce,
			post_id   : postId,
			new_status: newStatus,
		} )
		.done( function ( res ) {
			if ( res.success ) {
				location.reload();
			} else {
				alert( res.data || i18n.error || 'Error.' );
				$btn.prop( 'disabled', false );
			}
		} )
		.fail( function () {
			alert( i18n.networkError || 'Network error.' );
			$btn.prop( 'disabled', false );
		} );
	} );

	// -----------------------------------------------------------------------
	// API key copy-to-clipboard
	// -----------------------------------------------------------------------
	$( document ).on( 'click', '.aigis-copy-btn', function () {
		const $btn   = $( this );
		const target = $btn.data( 'target' );
		const $field = $( '#' + target );

		if ( ! $field.length ) {
			return;
		}

		const text = $field.is( 'input' ) ? $field.val() : $field.text();

		if ( ! text ) {
			return;
		}

		if ( navigator.clipboard ) {
			navigator.clipboard.writeText( text ).then( function () {
				onCopied( $btn );
			} );
		} else {
			// Fallback for older browsers.
			$field.select();
			document.execCommand( 'copy' );
			onCopied( $btn );
		}
	} );

	function onCopied( $btn ) {
		const orig = $btn.text();
		$btn.text( i18n.copied || 'Copied!' ).addClass( 'aigis-copied' );
		setTimeout( function () {
			$btn.text( orig ).removeClass( 'aigis-copied' );
		}, 2000 );
	}

	// -----------------------------------------------------------------------
	// Inbox unread badge refresh (poll every 60 s)
	// -----------------------------------------------------------------------
	function refreshInboxBadge() {
		const $badge = $( '#aigis-inbox-badge' );
		if ( ! $badge.length ) {
			return;
		}

		$.post( ajaxUrl, {
			action: 'aigis_inbox_unread_count',
			nonce : nonces.inboxUnreadCount,
		} )
		.done( function ( res ) {
			if ( res.success ) {
				const count = parseInt( res.data.count, 10 );
				$badge.text( count > 0 ? count : '' );
			}
		} );
	}

	setInterval( refreshInboxBadge, 60000 );
	refreshInboxBadge();

	// -----------------------------------------------------------------------
	// Confirm-delete buttons
	// -----------------------------------------------------------------------
	$( document ).on( 'click', '.aigis-confirm-delete', function ( e ) {
		const msg = $( this ).data( 'confirm' ) || i18n.confirmDelete || 'Delete this item?';
		if ( ! confirm( msg ) ) {
			e.preventDefault();
		}
	} );

	// -----------------------------------------------------------------------
	// Developer Tools — test data generate/purge
	// -----------------------------------------------------------------------
	$( document ).on( 'click', '#aigis-generate-test-data', function () {
		const $btn = $( this );
		const $log = $( '#aigis-test-data-log' );
		if ( ! confirm( i18n.confirmGenerateTestData || 'Generate sample data for all sections?' ) ) {
			return;
		}
		$btn.prop( 'disabled', true ).text( i18n.generating || 'Generating\u2026' );
		$log.html( '' );
		$.post( ajaxUrl, { action: 'aigis_generate_test_data', nonce: nonces.generateTestData } )
		.done( function ( res ) {
			if ( res.success ) {
				$log.html( '<div class="notice notice-success inline"><p>' + res.data.message + '</p></div>' );
				setTimeout( function () { location.reload(); }, 1500 );
			} else {
				$log.html( '<div class="notice notice-error inline"><p>' + ( res.data || 'Error.' ) + '</p></div>' );
				$btn.prop( 'disabled', false ).text( 'Generate Test Data' );
			}
		} )
		.fail( function () {
			$log.html( '<div class="notice notice-error inline"><p>Network error.</p></div>' );
			$btn.prop( 'disabled', false ).text( 'Generate Test Data' );
		} );
	} );

	$( document ).on( 'click', '#aigis-purge-test-data', function () {
		const $btn = $( this );
		const $log = $( '#aigis-test-data-log' );
		if ( ! confirm( i18n.confirmPurgeTestData || 'Permanently delete ALL test data?' ) ) {
			return;
		}
		$btn.prop( 'disabled', true ).text( i18n.purging || 'Removing\u2026' );
		$log.html( '' );
		$.post( ajaxUrl, { action: 'aigis_purge_test_data', nonce: nonces.purgeTestData } )
		.done( function ( res ) {
			if ( res.success ) {
				$log.html( '<div class="notice notice-success inline"><p>' + res.data.message + '</p></div>' );
				setTimeout( function () { location.reload(); }, 1500 );
			} else {
				$log.html( '<div class="notice notice-error inline"><p>' + ( res.data || 'Error.' ) + '</p></div>' );
				$btn.prop( 'disabled', false ).text( 'Remove Test Data' );
			}
		} )
		.fail( function () {
			$log.html( '<div class="notice notice-error inline"><p>Network error.</p></div>' );
			$btn.prop( 'disabled', false ).text( 'Remove Test Data' );
		} );
	} );

	// -----------------------------------------------------------------------
	// Developer Tools — factory reset
	// -----------------------------------------------------------------------
	$( document ).on( 'click', '#aigis-factory-reset', function () {
		const $btn = $( this );
		const $log = $( '#aigis-factory-reset-log' );
		if ( ! confirm( i18n.confirmFactoryReset1 || 'FACTORY RESET: This will permanently delete every record created by this plugin and cannot be undone. Continue?' ) ) {
			return;
		}
		const confirm2 = prompt( i18n.confirmFactoryReset2 || 'Are you absolutely sure? Type YES to confirm.' );
		if ( ! confirm2 || confirm2.trim().toUpperCase() !== 'YES' ) {
			$log.html( '<div class="notice notice-warning inline"><p>Factory reset cancelled.</p></div>' );
			return;
		}
		$btn.prop( 'disabled', true ).text( i18n.resetting || 'Resetting\u2026' );
		$log.html( '' );
		$.post( ajaxUrl, { action: 'aigis_factory_reset', nonce: nonces.factoryReset } )
		.done( function ( res ) {
			if ( res.success ) {
				$log.html( '<div class="notice notice-success inline"><p>' + res.data.message + '</p></div>' );
				setTimeout( function () { location.reload(); }, 2000 );
			} else {
				$log.html( '<div class="notice notice-error inline"><p>' + ( res.data || 'Error.' ) + '</p></div>' );
				$btn.prop( 'disabled', false ).text( 'Reset All Plugin Data' );
			}
		} )
		.fail( function () {
			$log.html( '<div class="notice notice-error inline"><p>Network error.</p></div>' );
			$btn.prop( 'disabled', false ).text( 'Reset All Plugin Data' );
		} );
	} );

} )( jQuery );
