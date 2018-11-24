import "babel-polyfill";

/**
 * Image resize detection (IRS).
 *
 * Show all wrongly scaled images with a highlighted border and resize box.
 *
 * Made in pure JS.
 * DO NOT ADD JQUERY SUPPORT!!!
 *
 * @since 2.9
 */
( function() {
	'use strict';

	const WP_Smush_IRS = {
		bar: document.getElementById('smush-image-bar'),
		toggle: document.getElementById('smush-image-bar-toggle'),
		images: {
			bigger: [],
			smaller: []
		},

		/**
		 * Init scripts.
		 */
		init: function () {
			/** @var {array} wp_smush_resize_vars */
			if ( wp_smush_resize_vars ) {
				this.strings = wp_smush_resize_vars;
			}

			this.toggle.addEventListener('click', this.handleToggleClick.bind(this));

			this.detectImages();
			this.generateMarkup('bigger');
			this.generateMarkup('smaller');
			this.removeEmptyDivs();
		},

		/**
		 * Various checks to see if the image should be processed.
		 *
		 * @param {object} image
		 * @returns {boolean}
		 */
		shouldSkipImage: function(image) {
			// Skip avatars.
			if ( image.classList.contains('avatar') ) {
				return true;
			}

			// If width attribute is not set, do not continue.
			return null === image.clientWidth || null === image.clientHeight;
		},

		/**
		 * Get tooltip text.
		 *
		 * @param {object} props
		 * @returns {string}
		 */
		getTooltipText: function(props) {
			let tooltip_text = '';

			if ( props.bigger_width || props.bigger_height ) {
				/** @var {string} strings.large_image */
				tooltip_text = this.strings.large_image;
			} else if ( props.smaller_width || props.smaller_height ) {
				/** @var {string} strings.small_image */
				tooltip_text = this.strings.small_image;
			}

			return tooltip_text.replace('width', props.real_width)
				.replace('height', props.real_height);
		},

		/**
		 * Generate markup.
		 *
		 * @param {string} type  Accepts: 'bigger' or 'smaller'.
		 */
		generateMarkup: function(type) {
			this.images[type].forEach((image, index) => {
				const item = document.createElement('div'),
					tooltip = this.getTooltipText(image.props);

				item.setAttribute('class', 'smush-resize-box smush-tooltip smush-tooltip-constrained');
				item.setAttribute('data-tooltip', tooltip);
				item.setAttribute('data-image', image.class);
				item.addEventListener('click', (e) => this.highlightImage(e));

				item.innerHTML = `
					<div class="smush-image-info">
						<span>${index + 1}</span>
						<span class="smush-tag">${image.props.computed_width} x ${image.props.computed_height}px</span>
						<i class="smush-front-icons smush-front-icon-arrows-in" aria-hidden="true">&nbsp;</i>
						<span class="smush-tag smush-tag-success">${image.props.real_width} × ${image.props.real_height}px</span>					
					</div>
					<div class="smush-image-description">${tooltip}</div>
				`;

				document.getElementById('smush-image-bar-items-'+type).appendChild(item);
			});
		},

		/**
		 * Remove sections that don't have images.
		 */
		removeEmptyDivs: function() {
			const types = ['bigger', 'smaller'];
			types.forEach(type => {
				if ( 0 === this.images[type].length ) {
					const div = document.getElementById('smush-image-bar-items-'+type);
					div.style.display = 'none';
				}

			});
		},

		/**
		 * Scroll the selected image into view and highlight it.
		 */
		highlightImage: function(e) {
			this.removeSelection();

			const el = document.getElementsByClassName(e.currentTarget.dataset.image);
			if ('undefined' !== typeof el[0]) {
				// Display description box.
				e.currentTarget.classList.toggle('show-description');

				// Scroll and flash image.
				el[0].scrollIntoView({behavior: 'smooth', block: 'center', inline: 'nearest'});
				el[0].style.opacity = '0.5';
				setTimeout(() => {
					el[0].style.opacity = '1';
				}, 1000);
			}
		},

		/**
		 * Handle click on the toggle item.
		 */
		handleToggleClick: function(e) {
			this.bar.classList.toggle('closed');
			this.toggle.classList.toggle('closed');
			this.removeSelection();
		},

		/**
		 * Remove selected items.
		 */
		removeSelection: function() {
			const items = document.getElementsByClassName('show-description');
			if ( items.length > 0 ) {
				Array.from(items).forEach(div => div.classList.remove('show-description'));
			}
		},

		/**
		 * Function to highlight all scaled images.
		 *
		 * Add yellow border and then show one small box to
		 * resize the images as per the required size, on fly.
		 */
		detectImages: function() {
			const images = document.getElementsByTagName('img');

			for ( let image of images ) {
				if ( this.shouldSkipImage(image) ) {
					continue;
				}

				// Get defined width and height.
				const props = {
					real_width:      image.clientWidth,
					real_height:     image.clientHeight,
					computed_width:  image.naturalWidth,
					computed_height: image.naturalHeight,
					bigger_width:  ( image.clientWidth * 1.5 ) < image.naturalWidth,
					bigger_height: ( image.clientHeight * 1.5 ) < image.naturalHeight,
					smaller_width:   image.clientWidth > image.naturalWidth,
					smaller_height:  image.clientHeight > image.naturalHeight
				};

				// In case image is in correct size, do not continue.
				if ( ! props.bigger_width && ! props.bigger_height && ! props.smaller_width && ! props.smaller_height ) {
					continue;
				}

				const imgType = props.bigger_width || props.bigger_height ? 'bigger' : 'smaller',
					imageClass =  'smush-image-'+(this.images[imgType].length + 1);

				// Fill the images arrays.
				this.images[imgType].push({
					src: image,
					props: props,
					class: imageClass
				});

				/**
				 * Add class to original image.
				 * Can't add two classes in single add(), because no support in IE11.
				 * image.classList.add('smush-detected-img', imageClass);
				 */
				image.classList.add('smush-detected-img');
				image.classList.add(imageClass);
			}
		} // End detectImages()

	}; // End WP_Smush_IRS

	/**
	 * After page load, initialize toggle event.
	 */
	window.onload = WP_Smush_IRS.init();

}());