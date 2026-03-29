/* INC Stats Tracker — Frontend JS */
/* global jQuery */

( function ( $ ) {
	'use strict';

	// =========================================================================
	// TYFCB source type toggle
	//
	// Switches between the group-member dropdown and the other-source text field
	// based on the thank_you_to_type radio selection.
	//
	// Behaviour:
	//   - The active panel's inputs are enabled and visible.
	//   - The inactive panel's inputs are disabled (so they do not submit) and hidden.
	//   - aria-hidden is managed so screen readers ignore the inactive panel.
	//   - State is initialised on DOMReady by triggering change on the checked radio.
	// =========================================================================

	// =========================================================================
	// TYFCB attribution source toggle
	//
	// Shows/hides the #ist-tyfcb-referrer-details section based on whether
	// the selected revenue_attribution_source requires a referrer (data-shows-referrer="1").
	// Disables all inputs inside the hidden section so they do not submit.
	// =========================================================================

	function initTyfcbAttributionToggle() {
		var $form = $( '.ist-form--tyfcb' );
		if ( ! $form.length ) {
			return;
		}

		var $referrerSection = $( '#ist-tyfcb-referrer-details' );

		// Maps attribution source value → default original_referrer_type radio value.
		var sourceToReferrerType = {
			'current_member_referral':       'current_member',
			'former_member_referral':         'former_member',
			'third_party_extended_referral':  'other',
		};

		function updateReferrerVisibility( $radio ) {
			var showsReferrer    = $radio.data( 'shows-referrer' );
			var attributionValue = $radio.val();

			if ( showsReferrer ) {
				$referrerSection
					.addClass( 'ist-visible' )
					.removeAttr( 'aria-hidden' );

				// Auto-select the matching referrer type radio if the attribution source
				// has a natural mapping — this prevents the user having to make an obvious
				// second choice (e.g. "Former Member Referral" → auto-selects "Former Member").
				var defaultReferrerType = sourceToReferrerType[ attributionValue ];
				if ( defaultReferrerType ) {
					var $referrerRadio = $form.find(
						'[name="original_referrer_type"][value="' + defaultReferrerType + '"]'
					);
					if ( $referrerRadio.length && ! $referrerRadio.prop( 'checked' ) ) {
						$referrerRadio.prop( 'checked', true ).trigger( 'change' );
						return; // initTyfcbReferrerToggle's change handler re-enables inputs.
					}
				}

				// No auto-select (or already correct) — re-enable inputs for visible panel only.
				$referrerSection.find( 'input, select' ).each( function () {
					var $panel = $( this ).closest( '.ist-source-panel' );
					if ( ! $panel.length || $panel.hasClass( 'ist-visible' ) ) {
						$( this ).prop( 'disabled', false );
					}
				} );
			} else {
				$referrerSection
					.removeClass( 'ist-visible' )
					.attr( 'aria-hidden', 'true' )
					.find( 'input, select' )
					.prop( 'disabled', true );
			}
		}

		$form.on( 'change', '.ist-attribution-source', function () {
			updateReferrerVisibility( $( this ) );
		} );

		// Initialise on load in case a value is pre-selected.
		var $checked = $form.find( '.ist-attribution-source:checked' );
		if ( $checked.length ) {
			updateReferrerVisibility( $checked );
		} else {
			// Nothing selected yet — hide referrer section.
			$referrerSection
				.removeClass( 'ist-visible' )
				.attr( 'aria-hidden', 'true' )
				.find( 'input, select' )
				.prop( 'disabled', true );
		}
	}

	// =========================================================================
	// TYFCB referrer type toggle (member vs other-person panel inside the
	// referrer details section).
	// =========================================================================

	function initTyfcbReferrerToggle() {
		var $form = $( '.ist-form--tyfcb' );
		if ( ! $form.length ) {
			return;
		}

		var $section = $( '#ist-tyfcb-referrer-details' );

		$form.on( 'change', '.ist-referrer-toggle', function () {
			var targetPanelId = $( this ).data( 'panel' );

			$section.find( '.ist-source-panel' ).each( function () {
				$( this )
					.removeClass( 'ist-visible' )
					.attr( 'aria-hidden', 'true' )
					.find( 'input, select' )
					.prop( 'disabled', true );
			} );

			$( '#' + targetPanelId )
				.addClass( 'ist-visible' )
				.removeAttr( 'aria-hidden' )
				.find( 'input, select' )
				.prop( 'disabled', false );
		} );

		// Trigger on the checked referrer-type radio only when the referrer section
		// is already visible (i.e. a referral attribution source is pre-selected).
		if ( $section.hasClass( 'ist-visible' ) ) {
			$form.find( '.ist-referrer-toggle:checked' ).trigger( 'change' );
		}
	}

	function initTyfcbSourceToggle() {
		// This function is preserved for legacy (non-enhanced) form contexts.
		// The enhanced form uses initTyfcbAttributionToggle + initTyfcbReferrerToggle.
		var $form = $( '.ist-form--tyfcb' );
		if ( ! $form.length ) {
			return;
		}

		$form.on( 'change', '.ist-source-toggle', function () {
			var targetPanelId = $( this ).data( 'panel' );

			$form.find( '.ist-source-panel' ).each( function () {
				$( this )
					.removeClass( 'ist-visible' )
					.attr( 'aria-hidden', 'true' )
					.find( 'input, select' )
					.prop( 'disabled', true );
			} );

			$( '#' + targetPanelId )
				.addClass( 'ist-visible' )
				.removeAttr( 'aria-hidden' )
				.find( 'input, select' )
				.prop( 'disabled', false );
		} );

		$form.find( '.ist-source-toggle:checked' ).trigger( 'change' );
	}

	// =========================================================================
	// Recipient type toggle — shared by Referral and Connect forms
	//
	// Switches between a group-member dropdown and a free-text name/email field
	// based on the referred_to_type / connected_with_type radio selection.
	// =========================================================================

	function initRecipientToggle( formSelector ) {
		var $form = $( formSelector );
		if ( ! $form.length ) {
			return;
		}

		$form.on( 'change', '.ist-recipient-toggle', function () {
			var targetPanelId = $( this ).data( 'panel' );

			$form.find( '.ist-recipient-panel' ).each( function () {
				$( this )
					.removeClass( 'ist-visible' )
					.attr( 'aria-hidden', 'true' )
					.find( 'input, select' )
					.prop( 'disabled', true );
			} );

			$( '#' + targetPanelId )
				.addClass( 'ist-visible' )
				.removeAttr( 'aria-hidden' )
				.find( 'input, select' )
				.prop( 'disabled', false );
		} );

		$form.find( '.ist-recipient-toggle:checked' ).trigger( 'change' );
	}

	function initReferralRecipientToggle() {
		initRecipientToggle( '.ist-form--referral' );
	}

	function initConnectRecipientToggle() {
		initRecipientToggle( '.ist-form--connect' );
	}

	// =========================================================================
	// Charts — Chart.js 4.x driven by data-chart-type + data-chart JSON attrs
	//
	// Each <canvas class="ist-chart"> carries:
	//   data-chart-type  "business-trend" | "ref-con-comparison" | "leaderboard-horizontal" | "attribution-horizontal"
	//   data-chart       JSON { labels: [...], datasets: [{ data: [...] }, ...] }
	//
	// Visual config (colors, axes, tooltips) lives here, not in the server-
	// rendered JSON, so it stays consistent and is easy to update.
	// =========================================================================

	function buildChartConfig( type, raw ) {
		var blue      = '#1e4e8c';
		var blueTint  = 'rgba(30, 78, 140, 0.10)';
		var lightBlue = '#6b9fd4';
		var gridColor = 'rgba(0, 0, 0, 0.06)';
		var tickColor = '#9ca3af';

		var xBase = {
			border: { display: false },
			grid:   { display: false },
			ticks:  { color: tickColor, font: { size: 12 } },
		};
		var yBase = {
			border:      { display: false },
			beginAtZero: true,
			grid:        { color: gridColor },
			ticks:       { color: tickColor, font: { size: 12 } },
		};

		if ( type === 'business-trend' ) {
			return {
				type: 'line',
				data: {
					labels:   raw.labels,
					datasets: [ {
						label:              'Closed Business',
						data:               raw.datasets[ 0 ].data,
						borderColor:        blue,
						backgroundColor:    blueTint,
						fill:               true,
						tension:            0.35,
						pointBackgroundColor: blue,
						pointRadius:        4,
						pointHoverRadius:   6,
						borderWidth:        2,
					} ],
				},
				options: {
					responsive:          true,
					maintainAspectRatio: true,
					plugins: {
						legend: { display: false },
						tooltip: {
							callbacks: {
								label: function ( ctx ) {
									var v = ctx.parsed.y;
									return ' $' + v.toLocaleString( undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 } );
								},
							},
						},
					},
					scales: {
						x: xBase,
						y: Object.assign( {}, yBase, {
							ticks: Object.assign( {}, yBase.ticks, {
								callback: function ( val ) {
									return val >= 1000 ? '$' + ( val / 1000 ).toFixed( 0 ) + 'k' : '$' + val;
								},
							} ),
						} ),
					},
				},
			};
		}

		if ( type === 'ref-con-comparison' ) {
			return {
				type: 'bar',
				data: {
					labels:   raw.labels,
					datasets: [
						{
							label:           'Referrals',
							data:            raw.datasets[ 0 ].data,
							backgroundColor: blue,
							borderRadius:    4,
							borderSkipped:   false,
							minBarLength:    2,
						},
						{
							label:           'Connects',
							data:            raw.datasets[ 1 ].data,
							backgroundColor: lightBlue,
							borderRadius:    4,
							borderSkipped:   false,
							minBarLength:    2,
						},
					],
				},
				options: {
					responsive:          true,
					maintainAspectRatio: true,
					plugins: {
						legend: {
							display:  true,
							position: 'top',
							labels:   { color: '#374151', font: { size: 12 }, boxWidth: 10, padding: 12 },
						},
					},
					scales: {
						x: xBase,
						y: Object.assign( {}, yBase, {
							ticks: Object.assign( {}, yBase.ticks, { precision: 0 } ),
						} ),
					},
				},
			};
		}

		if ( type === 'leaderboard-horizontal' ) {
			return {
				type: 'bar',
				data: {
					labels:   raw.labels,
					datasets: [ {
						label:           raw.datasets[ 0 ].label || 'Count',
						data:            raw.datasets[ 0 ].data,
						backgroundColor: blue,
						borderRadius:    4,
						borderSkipped:   false,
					} ],
				},
				options: {
					indexAxis:           'y',
					responsive:          true,
					maintainAspectRatio: false,
					plugins: {
						legend: { display: false },
					},
					scales: {
						x: Object.assign( {}, yBase, {
							ticks: Object.assign( {}, yBase.ticks, { precision: 0 } ),
						} ),
						y: {
							border: { display: false },
							grid:   { display: false },
							ticks:  { color: '#374151', font: { size: 13 } },
						},
					},
				},
			};
		}

		if ( type === 'attribution-horizontal' ) {
			return {
				type: 'bar',
				data: {
					labels:   raw.labels,
					datasets: [ {
						label:           raw.datasets[ 0 ].label || 'Amount',
						data:            raw.datasets[ 0 ].data,
						backgroundColor: blue,
						borderRadius:    4,
						borderSkipped:   false,
					} ],
				},
				options: {
					indexAxis:           'y',
					responsive:          true,
					maintainAspectRatio: false,
					plugins: {
						legend: { display: false },
						tooltip: {
							callbacks: {
								label: function ( ctx ) {
									var v = ctx.parsed.x;
									return ' $' + v.toLocaleString( undefined, { minimumFractionDigits: 0, maximumFractionDigits: 0 } );
								},
							},
						},
					},
					scales: {
						x: Object.assign( {}, yBase, {
							ticks: Object.assign( {}, yBase.ticks, {
								callback: function ( val ) {
									return val >= 1000 ? '$' + ( val / 1000 ).toFixed( 0 ) + 'k' : '$' + val;
								},
							} ),
						} ),
						y: {
							border: { display: false },
							grid:   { display: false },
							ticks:  { color: '#374151', font: { size: 13 } },
						},
					},
				},
			};
		}

		return null;
	}

	function initCharts() {
		if ( typeof Chart === 'undefined' ) {
			return; // Chart.js not loaded (CDN failure or no-JS) — degrade silently.
		}

		document.querySelectorAll( '.ist-chart[data-chart-type]' ).forEach( function ( canvas ) {
			var type   = canvas.dataset.chartType;
			var rawStr = canvas.dataset.chart;
			if ( ! type || ! rawStr ) {
				return;
			}
			var raw;
			try {
				raw = JSON.parse( rawStr );
			} catch ( e ) {
				return; // Malformed JSON — skip this canvas.
			}
			var config = buildChartConfig( type, raw );
			if ( config ) {
				new Chart( canvas, config );
			}
		} );
	}

	// =========================================================================
	// Modal — lightweight inline form overlay
	//
	// Triggered by any element with [data-ist-modal="tyfcb|referral|connect"].
	// The form HTML lives in hidden .ist-modal-form-src containers on the page.
	// JS moves (not clones) the form node into the modal panel so DOM state,
	// event listeners, and nonce values are all preserved.
	//
	// Submit flow:
	//   1. Intercept form submit with fetch() (credentials: same-origin).
	//   2. admin-post.php processes the POST and redirects to referer + ?ist_saved=1
	//      or ?ist_error=…
	//   3. Check response.url for ist_saved=1 → show success → close → reload.
	//   4. Error → show message inline, re-enable submit button.
	// =========================================================================

	function initModals() {
		var $modal = $( '#ist-modal' );
		if ( ! $modal.length ) {
			return;
		}

		var $modalBody = $modal.find( '.ist-modal__body' );
		var activeType = null;

		function openModal( type ) {
			var $source = $( '#ist-modal-form-' + type );
			if ( ! $source.length ) {
				return;
			}

			// Move form content into the modal body.
			$modalBody.append( $source.children().detach() );
			activeType = type;

			// Clear any stale notices that may have been server-rendered from URL params.
			$modalBody.find( '.ist-notice' ).remove();

			$modal.removeAttr( 'hidden' );
			document.body.style.overflow = 'hidden';

			// Focus the close button for keyboard accessibility.
			$modal.find( '.ist-modal__close' ).trigger( 'focus' );
		}

		function closeModal() {
			if ( activeType ) {
				var $source = $( '#ist-modal-form-' + activeType );
				// Move form content back to its hidden container.
				$source.append( $modalBody.children().detach() );
				activeType = null;
			}
			$modal.attr( 'hidden', '' );
			document.body.style.overflow = '';
		}

		// Open on explicit data-attribute trigger (kept for any future in-content use).
		$( document ).on( 'click', '[data-ist-modal]', function ( e ) {
			e.preventDefault();
			openModal( $( this ).data( 'ist-modal' ) );
		} );

		// ── BuddyBoss subnav interceptor ─────────────────────────────────────
		// Intercept clicks on the "Log …" subnav links inside the My Stats tab
		// and open the corresponding modal instead of navigating away.
		//
		// Progressive enhancement: this handler only runs when initModals()
		// itself ran to completion, which only happens when #ist-modal exists
		// on the page (i.e. the My Stats summary page). On direct form sub-nav
		// pages there is no #ist-modal so initModals() returns early and these
		// click handlers are never bound — those pages work as normal.
		//
		// Matching is done on the trailing slug segment so it works regardless
		// of the site's member-base URL structure.
		$( document ).on( 'click', 'a', function ( e ) {
			var href = $( this ).attr( 'href' ) || '';
			var type = null;

			if ( /\/log-tyfcb\/?(\?.*)?$/.test( href ) )    { type = 'tyfcb';    }
			else if ( /\/log-referral\/?(\?.*)?$/.test( href ) ) { type = 'referral'; }
			else if ( /\/log-connect\/?(\?.*)?$/.test( href ) )  { type = 'connect';  }

			if ( ! type ) {
				return; // Not a form link — let it navigate normally.
			}

			e.preventDefault();
			openModal( type );
		} );

		// Close on backdrop or close button.
		$modal.on( 'click', '.ist-modal__backdrop, .ist-modal__close', closeModal );

		// Close on Escape key.
		$( document ).on( 'keydown', function ( e ) {
			if ( e.key === 'Escape' && ! $modal.attr( 'hidden' ) ) {
				closeModal();
			}
		} );

		// Intercept form submit inside the modal.
		$modal.on( 'submit', '.ist-form', function ( e ) {
			e.preventDefault();

			var $form      = $( this );
			var $submitBtn = $form.find( '[type="submit"]' );

			// Disable the button to prevent double-submit.
			$submitBtn.prop( 'disabled', true );

			// Remove any previous inline notices.
			$modalBody.find( '.ist-modal-notice' ).remove();

			fetch( $form.attr( 'action' ), {
				method:      'POST',
				body:        new FormData( $form[ 0 ] ),
				credentials: 'same-origin',
			} )
			.then( function ( response ) {
				var url = response.url || '';

				if ( url.indexOf( 'ist_saved=1' ) !== -1 ) {
					// Success — show confirmation, then close and reload.
					$modalBody.prepend(
						'<div class="ist-notice ist-notice--success ist-modal-notice">' +
						'Record saved successfully.' +
						'</div>'
					);
					setTimeout( function () {
						closeModal();
						window.location.reload();
					}, 1400 );
				} else {
					// Error — extract message from URL and show inline.
					var match = url.match( /ist_error=([^&#]+)/ );
					var msg   = match
						? decodeURIComponent( match[ 1 ].replace( /\+/g, ' ' ) )
						: 'An error occurred. Please try again.';
					$modalBody.prepend(
						'<div class="ist-notice ist-notice--error ist-modal-notice">' +
						msg +
						'</div>'
					);
					$submitBtn.prop( 'disabled', false );
				}
			} )
			.catch( function () {
				$modalBody.prepend(
					'<div class="ist-notice ist-notice--error ist-modal-notice">' +
					'Network error. Please try again.' +
					'</div>'
				);
				$submitBtn.prop( 'disabled', false );
			} );
		} );
	}

	// =========================================================================
	// Field help popup
	//
	// A single #ist-field-help element is created once and appended to
	// document.body so it sits outside any modal stack and is never clipped
	// by overflow:hidden containers.
	//
	// Any button with class .ist-help-icon triggers the popup.
	// Required data attributes on the button:
	//   data-help-title  Popup heading text.
	//   data-help-body   Body text. Use '||' as a paragraph separator.
	//
	// The popup is positioned fixed, near the clicked button.
	// =========================================================================

	function initFieldHelp() {
		// Create the popup element once and append to body.
		var $popup = $( '#ist-field-help' );
		if ( ! $popup.length ) {
			$popup = $(
				'<div id="ist-field-help" class="ist-help-popup" role="dialog" aria-modal="true" hidden>' +
				'<div class="ist-help-popup__header">' +
				'<span class="ist-help-popup__title"></span>' +
				'<button type="button" class="ist-help-popup__close" aria-label="Close help">&times;</button>' +
				'</div>' +
				'<div class="ist-help-popup__body"></div>' +
				'</div>'
			);
			$( 'body' ).append( $popup );
		}

		// Track the button that opened the popup so re-clicking it closes it.
		var $activeBtn = null;

		function openHelp( $btn ) {
			var title = $btn.data( 'help-title' ) || '';
			var body  = $btn.data( 'help-body' )  || '';

			$popup.find( '.ist-help-popup__title' ).text( title );

			var paragraphs = body.split( '||' );
			var $body = $popup.find( '.ist-help-popup__body' ).empty();
			paragraphs.forEach( function ( para ) {
				if ( para.trim() ) {
					$body.append( $( '<p>' ).text( para.trim() ) );
				}
			} );

			// Position below the button; clamp so popup stays inside viewport.
			var rect        = $btn[ 0 ].getBoundingClientRect();
			var maxHeight   = 320;
			var top         = rect.bottom + 8;
			var left        = Math.min( rect.left, window.innerWidth - 320 );

			// If popup would overflow the bottom, flip above the button.
			if ( top + maxHeight > window.innerHeight - 8 ) {
				top = Math.max( 8, rect.top - maxHeight - 8 );
			}

			$popup.css( { top: top + 'px', left: Math.max( 8, left ) + 'px' } );
			$popup.removeAttr( 'hidden' );
			$activeBtn = $btn;
			$popup.find( '.ist-help-popup__close' ).trigger( 'focus' );
		}

		function closeHelp() {
			$popup.attr( 'hidden', '' );
			$activeBtn = null;
		}

		$( document ).on( 'click', '.ist-help-icon', function ( e ) {
			e.preventDefault();
			e.stopPropagation();
			var $btn = $( this );
			// Second click on the same icon closes the popup (toggle).
			if ( ! $popup.attr( 'hidden' ) && $activeBtn && $activeBtn.is( $btn ) ) {
				closeHelp();
			} else {
				openHelp( $btn );
			}
		} );

		$( document ).on( 'click', '.ist-help-popup__close', function () {
			closeHelp();
		} );

		// Close on outside click.
		$( document ).on( 'click', function ( e ) {
			if ( ! $popup.attr( 'hidden' ) &&
				! $( e.target ).closest( '#ist-field-help' ).length &&
				! $( e.target ).hasClass( 'ist-help-icon' ) ) {
				closeHelp();
			}
		} );

		// Close on Escape key.
		$( document ).on( 'keydown', function ( e ) {
			if ( e.key === 'Escape' && ! $popup.attr( 'hidden' ) ) {
				closeHelp();
			}
		} );
	}

	// =========================================================================
	// Init
	// =========================================================================

	$( document ).ready( function () {
		initTyfcbAttributionToggle();
		initTyfcbReferrerToggle();
		initTyfcbSourceToggle();
		initReferralRecipientToggle();
		initConnectRecipientToggle();
		initModals();
		initCharts();
		initFieldHelp();
	} );

} )( jQuery );
