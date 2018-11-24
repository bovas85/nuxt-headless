/**
 * Adds a Smush Now button and displays stats in Media Attachment Details Screen
 */
(function ( $, _ ) {
	'use strict';

	// Local reference to the WordPress media namespace.
	const smush_media  = wp.media,
		sharedTemplate = "<label class='setting smush-stats' data-setting='description'><span class='name'><%= label %></span><span class='value'><%= value %></span></label>",
		template       = _.template( sharedTemplate );

	/**
	 * Create the template.
	 *
	 * @param {string} smushHTML
	 * @returns {Object}
	 */
	const prepareTemplate = function ( smushHTML ) {
		/**
		 * @var {array}  smush_vars.strings  Localization strings.
		 * @var {object} smush_vars          Object from wp_localize_script()
		 */
		return template( {
			label: smush_vars.strings['stats_label'],
			value: smushHTML
		} );
	};

	if ( 'undefined' !== typeof smush_media.view &&
		'undefined' !== typeof smush_media.view.Attachment.Details.TwoColumn ) {
		// Local instance of the Attachment Details TwoColumn used in the edit attachment modal view
		let smushMediaTwoColumn = smush_media.view.Attachment.Details.TwoColumn;

		/**
		 * Add Smush details to attachment.
		 *
		 * A similar view to media.view.Attachment.Details
		 * for use in the Edit Attachment modal.
		 *
		 * @see wp-includes/js/media-grid.js
		 */
		smush_media.view.Attachment.Details.TwoColumn = smushMediaTwoColumn.extend( {
			initialize: function () {
				this.listenTo( this.model, 'change:smush', this.render );
			},

			render: function () {
				// Ensure that the main attachment fields are rendered.
				smush_media.view.Attachment.prototype.render.apply( this, arguments );

				const smushHTML = this.model.get( 'smush' );
				if ( typeof smushHTML === 'undefined' ) {
					return this;
				}

				this.model.fetch();

				/**
				 * Detach the views, append our custom fields, make sure that our data is fully updated
				 * and re-render the updated view.
				 */
				this.views.detach();
				this.$el.find( '.settings' ).append( prepareTemplate( smushHTML ) );
				this.views.render();

				return this;
			}
		} );
	}

	// Local instance of the Attachment Details TwoColumn used in the edit attachment modal view
	let smushAttachmentDetails = smush_media.view.Attachment.Details;

	/**
	 * Add Smush details to attachment.
	 */
	smush_media.view.Attachment.Details = smushAttachmentDetails.extend( {
		initialize: function () {
			smushAttachmentDetails.prototype.initialize.apply( this, arguments );
			this.listenTo( this.model, 'change:smush', this.render );
		},

		render: function () {
			// Ensure that the main attachment fields are rendered.
			smush_media.view.Attachment.prototype.render.apply( this, arguments );

			const smushHTML = this.model.get( 'smush' );
			if ( typeof smushHTML === 'undefined' ) {
				return this;
			}

			this.model.fetch();

			/**
			 * Detach the views, append our custom fields, make sure that our data is fully updated
			 * and re-render the updated view.
			 */
			this.views.detach();
			this.$el.append( prepareTemplate( smushHTML ) );

			return this;
		}
	} );

	/**
	 * Create a new MediaLibraryTaxonomyFilter we later will instantiate
	 *
	 * @since 2.9.0
	 */
/*
	const MediaLibraryTaxonomyFilter = wp.media.view.AttachmentFilters.extend({
		id: 'media-attachment-smush-filter',

		createFilters: function() {
			this.filters = {
				all: {
					text: smush_vars.strings['filter_all'],
					props: { stats: 'all' },
					priority: 10
				},

				excluded: {
					text: smush_vars.strings['filter_excl'],
					props: { stats: 'null' },
					priority: 20
				}
			};
		}
	});
*/
	/**
	 * Extend and override wp.media.view.AttachmentsBrowser to include our new filter.
	 *
	 * @since 2.9.0
	 */
/*
	let AttachmentsBrowser = wp.media.view.AttachmentsBrowser;
	wp.media.view.AttachmentsBrowser = wp.media.view.AttachmentsBrowser.extend({
		createToolbar: function() {
			// Make sure to load the original toolbar
			AttachmentsBrowser.prototype.createToolbar.call( this );
			this.toolbar.set( 'MediaLibraryTaxonomyFilter', new MediaLibraryTaxonomyFilter({
				controller: this.controller,
				model:      this.collection.props,
				priority: -75
			}).render() );
		}
	});
*/

})( jQuery, _ );