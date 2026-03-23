/* INC Stats Tracker — Frontend JS */
/* global istFrontend, jQuery */

( function ( $ ) {
	'use strict';

	/**
	 * TYFCB source type toggle.
	 *
	 * Switches between the group-member dropdown and the other-source text field
	 * based on the thank_you_to_type radio selection.
	 *
	 * Behaviour:
	 *   - The active panel's inputs are enabled and visible.
	 *   - The inactive panel's inputs are disabled (so they do not submit) and hidden.
	 *   - aria-hidden is managed so screen readers ignore the inactive panel.
	 *   - State is initialised on DOMReady by triggering change on the checked radio.
	 */
	function initTyfcbSourceToggle() {
		var $form = $( '.ist-form--tyfcb' );
		if ( ! $form.length ) {
			return;
		}

		$form.on( 'change', '.ist-source-toggle', function () {
			var targetPanelId = $( this ).data( 'panel' );

			// Deactivate all panels: hide and disable their inputs.
			$form.find( '.ist-source-panel' ).each( function () {
				$( this )
					.removeClass( 'ist-visible' )
					.attr( 'aria-hidden', 'true' )
					.find( 'input, select' )
					.prop( 'disabled', true );
			} );

			// Activate the target panel: show and enable its inputs.
			$( '#' + targetPanelId )
				.addClass( 'ist-visible' )
				.removeAttr( 'aria-hidden' )
				.find( 'input, select' )
				.prop( 'disabled', false );
		} );

		// Set initial state based on whichever radio is checked on page load.
		$form.find( '.ist-source-toggle:checked' ).trigger( 'change' );
	}

	$( document ).ready( function () {
		initTyfcbSourceToggle();
	} );

} )( jQuery );
