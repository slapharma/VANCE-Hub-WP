/**
 * VHH Annotations — REST client + shared namespace/event bus.
 * Loaded first; all other vhh scripts hang off window.VHH.
 */
(function () {
	'use strict';
	if ( typeof window.VHH_CFG === 'undefined' ) {
		return;
	}

	var listeners = {};

	window.VHH = {
		cfg: window.VHH_CFG,

		bus: {
			on: function ( event, fn ) {
				( listeners[ event ] = listeners[ event ] || [] ).push( fn );
			},
			emit: function ( event, payload ) {
				( listeners[ event ] || [] ).forEach( function ( fn ) {
					try { fn( payload ); } catch ( e ) { console.error( 'VHH bus handler', e ); }
				} );
			}
		},

		api: {
			request: function ( method, path, body ) {
				return fetch( window.VHH_CFG.restUrl + path, {
					method: method,
					credentials: 'same-origin',
					headers: ( function () {
						var h = { 'Content-Type': 'application/json' };
						// Only send the nonce when we have one: a present-but-
						// empty X-WP-Nonce fails core's cookie check for
						// logged-in users opening token review links.
						if ( window.VHH_CFG.nonce ) {
							h['X-WP-Nonce'] = window.VHH_CFG.nonce;
						}
						return h;
					} )(),
					body: body ? JSON.stringify( body ) : undefined
				} ).then( function ( res ) {
					return res.json().catch( function () { return {}; } ).then( function ( data ) {
						if ( ! res.ok ) {
							var err = new Error( ( data && data.message ) || ( 'HTTP ' + res.status ) );
							err.code = data && data.code;
							err.status = res.status;
							throw err;
						}
						return data;
					} );
				} );
			},
			list: function ( postId ) {
				if ( window.VHH_CFG.reviewToken ) {
					return this.request( 'GET', '/review/' + window.VHH_CFG.reviewToken + '/annotations' );
				}
				return this.request( 'GET', '/annotations?post=' + postId + '&status=all' );
			},
			/** Site-wide recent annotations (post=0) — not available in review-token mode. */
			listOthers: function () {
				if ( window.VHH_CFG.reviewToken ) {
					return Promise.resolve( { annotations: [] } );
				}
				return this.request( 'GET', '/annotations?post=0&status=all' );
			},
			create: function ( payload ) {
				if ( window.VHH_CFG.reviewToken ) {
					return this.request( 'POST', '/review/' + window.VHH_CFG.reviewToken + '/annotate', payload );
				}
				return this.request( 'POST', '/annotations', payload );
			},
			reply: function ( parentId, postId, text ) {
				return this.request( 'POST', '/annotations', {
					post: postId,
					parent: parentId,
					comment: text
				} );
			},
			patch: function ( id, action ) {
				if ( window.VHH_CFG.reviewToken ) {
					return Promise.reject( new Error( 'Reviewers cannot change note status.' ) );
				}
				return this.request( 'PATCH', '/annotations/' + id, { action: action } );
			},
			approve: function () {
				return this.request( 'POST', '/review/' + window.VHH_CFG.reviewToken + '/approve', {} );
			}
		}
	};
})();
