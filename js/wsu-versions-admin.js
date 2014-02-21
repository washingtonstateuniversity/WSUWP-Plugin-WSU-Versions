(function( $, window, undefined ){

	function handle_click() {
		if ( 'wsu-create-fork' === this.id ) {
			create_fork();
		} else if ( 'wsu-update-fork' === this.id ) {
			update_fork();
		}
	}

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
				if ( '-1' == response ) {
					return; // nonce error
				}

				response = $.parseJSON( response );

				if ( response.success ) {
					var post_id = response.success;
				} else {
					var error = response.error;
				}
			});
		}
	}

	function update_fork() {
		var data = {
			action: 'update_fork',
			fork_template: $( '#wsu-fork-template' ).val(),
			fork_post_id: $( '#wsu-versions-post-id' ).val(),
			_ajax_nonce: $( '#wsu-versions-fork-nonce' ).val()
		};

		$.post( window.ajaxurl, data, function( response ) {
			if ( '-1' == response ) {
				return; // nonce error.
			}

			response = $.parseJSON( response );

			$( '#wsu-versions-response' ).addClass('updated' ).text( 'Template changed.' );
		});
	}

	$( '#wsu-versions-meta' ).on( 'click', '.button-secondary', handle_click );
}( jQuery, window ) );