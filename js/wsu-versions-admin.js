(function( $, window, undefined ){
	var $create_fork = $( '#wsu-create-fork' );

	$create_fork.on( 'click', create_fork );

	function create_fork() {
		var unique_id  = $( '#wsu-version-id' ).val(),
			fork_nonce = $( '#wsu-versions-fork-nonce' ).val();

		if ( '' !== unique_id ) {
			var data = {
				action: 'create_fork',
				version_id: unique_id,
				_ajax_nonce: fork_nonce
			};

			// Make the ajax call
			$.post( window.ajaxurl, data, function( response ) {
				console.log( response );
			} );
		}
	}
}( jQuery, window ) );