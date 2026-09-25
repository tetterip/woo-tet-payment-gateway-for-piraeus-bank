/* Piraeus Bank gateway settings: copy buttons and the "Test callback URL" check. */
( function () {
	'use strict';

	var config = window.piraeusbankAdmin;
	if ( ! config ) {
		return;
	}

	document.addEventListener( 'click', function ( event ) {
		var copy = event.target.closest( '[data-piraeusbank-copy]' );
		if ( copy ) {
			event.preventDefault();
			var source = document.getElementById( copy.getAttribute( 'data-piraeusbank-copy' ) );
			if ( source && navigator.clipboard ) {
				navigator.clipboard.writeText( source.value !== undefined ? source.value : source.textContent ).then( function () {
					var label = copy.textContent;
					copy.textContent = config.i18n.copied;
					setTimeout( function () {
						copy.textContent = label;
					}, 1500 );
				} );
			}
			return;
		}

		var test = event.target.closest( '#piraeusbank-test-callback' );
		if ( ! test ) {
			return;
		}

		event.preventDefault();
		var output = document.getElementById( 'piraeusbank-test-results' );
		test.disabled = true;
		output.textContent = config.i18n.testing;

		var body = new URLSearchParams();
		body.append( 'action', config.action );
		body.append( 'nonce', config.nonce );

		fetch( config.ajaxUrl, { method: 'POST', credentials: 'same-origin', body: body } )
			.then( function ( response ) {
				return response.json();
			} )
			.then( function ( json ) {
				output.textContent = '';
				if ( ! json || ! json.success ) {
					output.textContent = config.i18n.error;
					return;
				}
				json.data.results.forEach( function ( result ) {
					var row = document.createElement( 'p' );
					var icon = document.createElement( 'span' );
					icon.className = 'dashicons ' + ( result.status === 'pass' ? 'dashicons-yes-alt' : ( result.status === 'blocked' ? 'dashicons-dismiss' : 'dashicons-warning' ) );
					icon.style.color = result.status === 'pass' ? '#008a20' : ( result.status === 'blocked' ? '#d63638' : '#dba617' );
					var url = document.createElement( 'code' );
					url.textContent = result.url;
					row.appendChild( icon );
					row.appendChild( document.createTextNode( ' ' ) );
					row.appendChild( url );
					row.appendChild( document.createElement( 'br' ) );
					row.appendChild( document.createTextNode( result.message ) );
					output.appendChild( row );
				} );
			} )
			.catch( function () {
				output.textContent = config.i18n.error;
			} )
			.then( function () {
				test.disabled = false;
			} );
	} );
} )();
