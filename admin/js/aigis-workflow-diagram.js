/**
 * AI Governance and Infrastructure Suite — Workflow Diagram JS
 *
 * Initialises Mermaid.js on the aigis_workflow CPT edit screen and
 * live-renders the diagram whenever the source textarea changes.
 *
 * Depends on: Mermaid.js 10.x (registered as 'mermaid' by AIGIS_Admin)
 */

/* global mermaid */
( function () {
	'use strict';

	const mermaidApi = typeof mermaid !== 'undefined'
		? mermaid
		: ( window.mermaid && window.mermaid.default ? window.mermaid.default : null );

	if ( ! mermaidApi ) {
		return;
	}

	mermaidApi.initialize( {
		startOnLoad : false,
		theme       : 'default',
		flowchart   : { useMaxWidth: true, htmlLabels: true },
		securityLevel: 'strict',
	} );

	const source   = document.getElementById( 'aigis-diagram-source' );
	const preview  = document.getElementById( 'aigis-diagram-preview' );
	const errorBox = document.getElementById( 'aigis-diagram-error' );

	if ( ! source || ! preview ) {
		return;
	}

	let debounceTimer = null;

	async function render( code ) {
		const trimmed = ( code || '' ).trim();

		if ( ! trimmed ) {
			preview.innerHTML = '<p style="color:#646970;font-size:.875rem;">Enter a Mermaid diagram above to see the preview.</p>';
			hideError();
			return;
		}

		try {
			const id = 'aigis-mermaid-' + Date.now();
			const result = await renderMermaid( id, trimmed );
			preview.innerHTML = result.svg || '';
			if ( typeof result.bindFunctions === 'function' ) {
				result.bindFunctions( preview );
			}
			hideError();
		} catch ( err ) {
			preview.innerHTML = '';
			showError( err.message || 'Parse error.' );
		}
	}

	function showError( msg ) {
		if ( errorBox ) {
			errorBox.textContent = msg;
			errorBox.style.display = 'block';
		}
	}

	function hideError() {
		if ( errorBox ) {
			errorBox.textContent = '';
			errorBox.style.display = 'none';
		}
	}

	async function renderMermaid( id, code ) {
		if ( typeof mermaidApi.render === 'function' ) {
			return mermaidApi.render( id, code );
		}

		if ( mermaidApi.mermaidAPI && typeof mermaidApi.mermaidAPI.render === 'function' ) {
			return new Promise( function( resolve, reject ) {
				try {
					mermaidApi.mermaidAPI.render( id, code, function( svg, bindFunctions ) {
						resolve( { svg: svg, bindFunctions: bindFunctions } );
					}, preview );
				} catch ( err ) {
					reject( err );
				}
			} );
		}

		throw new Error( 'No supported Mermaid render API found.' );
	}

	// Render immediately on page load.
	render( source.value );

	// Debounced re-render on input.
	source.addEventListener( 'input', function () {
		clearTimeout( debounceTimer );
		debounceTimer = setTimeout( function () {
			render( source.value );
		}, 600 );
	} );

} )();
