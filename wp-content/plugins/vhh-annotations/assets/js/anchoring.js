/**
 * VHH Annotations — text anchoring engine.
 *
 * Persists W3C-style TextQuoteSelectors { exact, prefix, suffix } and
 * re-anchors them against the article DOM:
 *
 *  - getTextContent(): TreeWalker concatenation of text nodes + offset map
 *    (text inside existing vhh marks stays included, so later annotations
 *    can anchor across already-highlighted passages)
 *  - matching is whitespace-flexible and case-insensitive (Chromium's
 *    Selection.toString() uppercases text-transform:uppercase headings)
 *  - multiple matches disambiguated by prefix/suffix similarity
 *  - wrapRange(): Range.surroundContents, falling back to wrapping each
 *    intersected text node in its own <mark> (cross-element selections)
 */
(function () {
	'use strict';
	if ( ! window.VHH ) { return; }

	var MARK_CLASS = 'vhh-mark';

	function isSkippable( node ) {
		var el = node.parentElement;
		while ( el ) {
			var tag = el.tagName;
			if ( tag === 'SCRIPT' || tag === 'STYLE' || tag === 'NOSCRIPT' ) { return true; }
			if ( el.classList && el.classList.contains( 'vhh-ui' ) ) { return true; }
			el = el.parentElement;
		}
		return false;
	}

	/**
	 * Walk text nodes under root.
	 * @returns {{text: string, nodes: Array<{node: Text, start: number, end: number}>}}
	 */
	function getTextContent( root ) {
		var walker = document.createTreeWalker( root, NodeFilter.SHOW_TEXT, null );
		var text = '';
		var nodes = [];
		var n;
		while ( ( n = walker.nextNode() ) ) {
			if ( isSkippable( n ) ) { continue; }
			nodes.push( { node: n, start: text.length, end: text.length + n.data.length } );
			text += n.data;
		}
		return { text: text, nodes: nodes };
	}

	/** Character offset of a DOM position within the concatenated text. */
	function positionToOffset( container, offset, map ) {
		// Text-node container: find its entry.
		if ( container.nodeType === Node.TEXT_NODE ) {
			for ( var i = 0; i < map.nodes.length; i++ ) {
				if ( map.nodes[ i ].node === container ) {
					return map.nodes[ i ].start + offset;
				}
			}
			return -1;
		}
		// Element container: offset counts child nodes. Use the first text
		// node at/after the boundary.
		var boundary = container.childNodes[ offset ] || null;
		for ( var j = 0; j < map.nodes.length; j++ ) {
			var entry = map.nodes[ j ];
			if ( boundary && ( boundary === entry.node || boundary.contains( entry.node ) ||
				( boundary.compareDocumentPosition( entry.node ) & Node.DOCUMENT_POSITION_FOLLOWING ) ) ) {
				return entry.start;
			}
			if ( ! boundary && container.contains( entry.node ) ) {
				// offset was past the last child: end of container text
				continue;
			}
		}
		if ( ! boundary ) {
			// End of container — last contained node's end.
			for ( var k = map.nodes.length - 1; k >= 0; k-- ) {
				if ( container.contains( map.nodes[ k ].node ) ) {
					return map.nodes[ k ].end;
				}
			}
		}
		return -1;
	}

	/** Describe a live Range as a TextQuoteSelector. */
	function describeRange( range, root ) {
		var map = getTextContent( root );
		var start = positionToOffset( range.startContainer, range.startOffset, map );
		var end = positionToOffset( range.endContainer, range.endOffset, map );
		if ( start < 0 || end < 0 || end <= start ) { return null; }
		var exact = map.text.slice( start, end );
		if ( ! exact.trim() || exact.length > 1000 ) { return null; }
		return {
			type: 'TextQuoteSelector',
			exact: exact,
			prefix: map.text.slice( Math.max( 0, start - 64 ), start ),
			suffix: map.text.slice( end, end + 64 )
		};
	}

	function escapeRegex( s ) {
		return s.replace( /[.*+?^${}()|[\]\\]/g, '\\$&' );
	}

	/** Build a whitespace-flexible, case-insensitive regex for a quote. */
	function quoteToRegex( quote ) {
		var pattern = quote
			.split( /\s+/ )
			.filter( Boolean )
			.map( escapeRegex )
			.join( '\\s+' );
		if ( ! pattern ) { return null; }
		try {
			return new RegExp( pattern, 'gi' );
		} catch ( e ) {
			return null;
		}
	}

	function similarity( a, b ) {
		// Cheap suffix/prefix agreement score: shared chars from the joining edge.
		a = ( a || '' ).replace( /\s+/g, ' ' ).toLowerCase();
		b = ( b || '' ).replace( /\s+/g, ' ' ).toLowerCase();
		var n = Math.min( a.length, b.length );
		var score = 0;
		for ( var i = 0; i < n; i++ ) {
			if ( a[ i ] === b[ i ] ) { score++; } else { break; }
		}
		return score;
	}

	/**
	 * Locate a selector in the concatenated text.
	 * @returns {{start:number,end:number,map:Object}|null}
	 */
	function findSelector( selector, root ) {
		var map = getTextContent( root );
		var re = quoteToRegex( selector.exact );
		if ( ! re ) { return null; }

		var candidates = [];
		var m;
		while ( ( m = re.exec( map.text ) ) ) {
			candidates.push( { start: m.index, end: m.index + m[ 0 ].length } );
			if ( candidates.length > 50 ) { break; }
			re.lastIndex = m.index + 1; // allow overlapping scan positions
		}
		if ( ! candidates.length ) { return null; }
		if ( candidates.length === 1 ) {
			return { start: candidates[ 0 ].start, end: candidates[ 0 ].end, map: map };
		}

		// Disambiguate with prefix/suffix context.
		var best = candidates[ 0 ];
		var bestScore = -1;
		candidates.forEach( function ( c ) {
			var before = map.text.slice( Math.max( 0, c.start - 64 ), c.start );
			var after = map.text.slice( c.end, c.end + 64 );
			// prefix compares from its END against before's END → reverse both.
			var score = similarity(
					( selector.prefix || '' ).split( '' ).reverse().join( '' ),
					before.split( '' ).reverse().join( '' )
				) + similarity( selector.suffix || '', after );
			if ( score > bestScore ) {
				bestScore = score;
				best = c;
			}
		} );
		return { start: best.start, end: best.end, map: map };
	}

	/** Convert concatenated-text offsets back to a DOM Range. */
	function offsetsToRange( start, end, map ) {
		var range = document.createRange();
		var startSet = false;
		for ( var i = 0; i < map.nodes.length; i++ ) {
			var e = map.nodes[ i ];
			if ( ! startSet && start >= e.start && start < e.end ) {
				range.setStart( e.node, start - e.start );
				startSet = true;
			}
			if ( startSet && end > e.start && end <= e.end ) {
				range.setEnd( e.node, end - e.start );
				return range;
			}
		}
		return null;
	}

	function makeMark( annotationId ) {
		var mark = document.createElement( 'mark' );
		mark.className = MARK_CLASS;
		mark.setAttribute( 'data-annotation-id', String( annotationId ) );
		return mark;
	}

	/**
	 * Wrap a range in <mark> element(s).
	 * @returns {Element[]} the created marks (possibly several).
	 */
	function wrapRange( range, annotationId ) {
		// Fast path: selection within one text node / element.
		try {
			var mark = makeMark( annotationId );
			range.surroundContents( mark );
			return [ mark ];
		} catch ( e ) {
			// Cross-element selection: wrap every intersected text node.
		}

		var root = range.commonAncestorContainer;
		if ( root.nodeType === Node.TEXT_NODE ) { root = root.parentNode; }
		var walker = document.createTreeWalker( root, NodeFilter.SHOW_TEXT, null );
		var targets = [];
		var n;
		while ( ( n = walker.nextNode() ) ) {
			if ( isSkippable( n ) ) { continue; }
			if ( ! range.intersectsNode( n ) ) { continue; }
			var s = ( n === range.startContainer ) ? range.startOffset : 0;
			var epos = ( n === range.endContainer ) ? range.endOffset : n.data.length;
			if ( epos > s ) {
				targets.push( { node: n, start: s, end: epos } );
			}
		}

		var marks = [];
		targets.forEach( function ( t ) {
			var node = t.node;
			if ( t.end < node.data.length ) { node.splitText( t.end ); }
			if ( t.start > 0 ) { node = node.splitText( t.start ); }
			if ( ! node.data.trim() ) { return; } // skip whitespace-only fragments
			var m2 = makeMark( annotationId );
			node.parentNode.insertBefore( m2, node );
			m2.appendChild( node );
			marks.push( m2 );
		} );
		return marks;
	}

	/** Remove marks (all, or for one annotation id) and re-normalize. */
	function unwrapMarks( root, annotationId ) {
		var sel = 'mark.' + MARK_CLASS + ( annotationId ? '[data-annotation-id="' + annotationId + '"]' : '' );
		var parents = [];
		root.querySelectorAll( sel ).forEach( function ( mark ) {
			var parent = mark.parentNode;
			while ( mark.firstChild ) {
				parent.insertBefore( mark.firstChild, mark );
			}
			parent.removeChild( mark );
			if ( parents.indexOf( parent ) === -1 ) { parents.push( parent ); }
		} );
		parents.forEach( function ( p ) { p.normalize(); } );
	}

	window.VHH.anchoring = {
		getTextContent: getTextContent,
		describeRange: describeRange,
		findSelector: findSelector,
		offsetsToRange: offsetsToRange,
		wrapRange: wrapRange,
		unwrapMarks: unwrapMarks
	};
})();
