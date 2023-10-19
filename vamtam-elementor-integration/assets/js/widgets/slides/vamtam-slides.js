const VamtamSlidesHandler = elementorModules.frontend.handlers.Base.extend( {
	getDefaultSettings: function getDefaultSettings() {
	  return {
		selectors: {
		  slider: '.elementor-slides-wrapper',
		  slide: '.swiper-slide',
		  slideBackground: '.swiper-slide-bg',
		  slideInnerContents: '.swiper-slide-contents',
		  activeSlide: '.swiper-slide-active',
		  activeDuplicate: '.swiper-slide-duplicate-active'
		},
		classes: {
		  animated: 'animated',
		  kenBurnsActive: 'elementor-ken-burns--active'
		},
		attributes: {
		  dataSliderOptions: 'slider_options',
		  dataAnimation: 'animation'
		}
	  };
	},
	getDefaultElements: function getDefaultElements() {
	  var selectors = this.getSettings('selectors');
	  var elements = {
		$slider: this.$element.find(selectors.slider)
	  };
	  elements.$mainSwiperSlides = elements.$slider.find(selectors.slide);
	  return elements;
	},
	getSlidesCount: function getSlidesCount() {
	  return this.elements.$mainSwiperSlides.length;
	},
	getInitialSlide: function getInitialSlide() {
	  var editSettings = this.getEditSettings();
	  return editSettings.activeItemIndex ? editSettings.activeItemIndex - 1 : 0;
	},
	getSwiperOptions: function getSwiperOptions() {
	  var _this = this;

	  var elementSettings = this.getElementSettings();
	  var swiperOptions = {
		grabCursor: true,
		initialSlide: this.getInitialSlide(),
		slidesPerView: 1,
		slidesPerGroup: 1,
		loop: 'yes' === elementSettings.infinite,
		speed: elementSettings.transition_speed,
		effect: elementSettings.transition,
		observeParents: true,
		observer: true,
		handleElementorBreakpoints: true,
		on: {
		  slideChange: function slideChange() {
			_this.handleKenBurns();

			if ( elementSettings.infinite ) {
				_this.ensureSlidesContentVisibility();
			}

			_this.triggerInnerAnims();
		  }
		}
	  };
	  var showArrows = 'arrows' === elementSettings.navigation || 'both' === elementSettings.navigation,
		  pagination = 'dots' === elementSettings.navigation || 'both' === elementSettings.navigation;

	  if (showArrows) {
		swiperOptions.navigation = {
		  prevEl: '.elementor-swiper-button-prev',
		  nextEl: '.elementor-swiper-button-next'
		};
	  }

	  if (pagination) {
		swiperOptions.pagination = {
		  el: '.swiper-pagination',
		  type: 'bullets',
		  clickable: true
		};
	  }

	  if (!this.isEdit && elementSettings.autoplay) {
		swiperOptions.autoplay = {
		  delay: elementSettings.autoplay_speed,
		  disableOnInteraction: !!elementSettings.pause_on_interaction
		};
	  }

	  if (true === swiperOptions.loop) {
		swiperOptions.loopedSlides = this.getSlidesCount();
	  }

	  if ('fade' === swiperOptions.effect) {
		swiperOptions.fadeEffect = {
		  crossFade: true
		};
	  }

	  return swiperOptions;
	},
	handleKenBurns: function handleKenBurns() {
	  var settings = this.getSettings();

	  if (this.$activeImageBg) {
		this.$activeImageBg.removeClass(settings.classes.kenBurnsActive);
	  }

	  this.activeItemIndex = this.swipers.main ? this.swipers.main.activeIndex : this.getInitialSlide();

	  if (this.swipers.main) {
		this.$activeImageBg = jQuery(this.swipers.main.slides[this.activeItemIndex]).children(settings.selectors.slideBackground);
	  } else {
		this.$activeImageBg = jQuery(this.elements.$mainSwiperSlides[0]).children(settings.selectors.slideBackground);
	  }

	  this.$activeImageBg.addClass(settings.classes.kenBurnsActive);
	},
	initSingleSlideAnimations: function initSingleSlideAnimations() {
	  var settings = this.getSettings(),
		  animation = this.elements.$slider.data(settings.attributes.dataAnimation);
	  this.elements.$slider.find(settings.selectors.slideBackground).addClass(settings.classes.kenBurnsActive); // If there is an animation, get the container of the slide's inner contents and add the animation classes to it

	  if (animation) {
		this.elements.$slider.find(settings.selectors.slideInnerContents).addClass(settings.classes.animated + ' ' + animation);
	  }
	},
	initSlider: async function initSlider() {
		var _this2 = this;

		var $slider = this.elements.$slider,
			settings = this.getSettings(),
			elementSettings = this.getElementSettings(),
			animation = $slider.data(settings.attributes.dataAnimation);

		if (!$slider.length) {
		return;
		}

		this.swipers = {};

		if (1 >= this.getSlidesCount()) {
		return;
		}

		if ( 'undefined' === typeof Swiper ) {
			const Swiper = elementorFrontend.utils.swiper;
			this.swipers.main = await new Swiper($slider, this.getSwiperOptions()); // Expose the swiper instance in the frontend
		} else {
			this.swipers.main = new Swiper($slider, this.getSwiperOptions()); // Expose the swiper instance in the frontend
		}

		$slider.data('swiper', this.swipers.main); // The Ken Burns effect will only apply on the specific slides that toggled the effect ON,
		// since it depends on an additional class besides 'elementor-ken-burns--active'

		this.handleKenBurns();

		if (elementSettings.pause_on_hover) {
			$slider.on({
			mouseenter: function mouseenter() {
				_this2.swipers.main.autoplay.stop();
			},
			mouseleave: function mouseleave() {
				_this2.swipers.main.autoplay.start();
			}
			});
		}

		if (!animation) {
			return;
		}

		this.swipers.main.on('slideChangeTransitionStart', function () {
			var $sliderContent = $slider.find(settings.selectors.slideInnerContents);
			$sliderContent.removeClass(settings.classes.animated + ' ' + animation).hide();
		});
		this.swipers.main.on('slideChangeTransitionEnd', function () {
			var $currentSlide = $slider.find(settings.selectors.slideInnerContents);
			$currentSlide.show().addClass(settings.classes.animated + ' ' + animation);
		});
	},
	onInit: function onInit() {
		elementorModules.frontend.handlers.Base.prototype.onInit.apply(this, arguments);

		if (2 > this.getSlidesCount()) {
			this.initSingleSlideAnimations();
			return;
		}

		this.initSlider();
	},
	onElementChange: function onElementChange(propertyName) {
	  if (1 >= this.getSlidesCount()) {
		return;
	  }

	  if (0 === propertyName.indexOf('width')) {
		this.swipers.main.update();
	  }
	},
	onEditSettingsChange: function onEditSettingsChange(propertyName) {
	  if (1 >= this.getSlidesCount()) {
		return;
	  }

	  if ('activeItemIndex' === propertyName) {
		this.swipers.main.slideToLoop(this.getEditSettings('activeItemIndex') - 1);
	  }
	},
	triggerInnerAnims: function triggerInnerAnims() {
		  const activeItemIndex = this.activeItemIndex || ( this.swipers.main ? this.swipers.main.activeIndex : this.getInitialSlide() ),
		  	realIndex           = this.swipers.main ? this.swipers.main.realIndex : this.getInitialSlide(),
	  		$activeSlide        = jQuery( this.swipers.main.slides[ activeItemIndex ] ),
			$animsInSlide       = $activeSlide.find( '[data-settings*="animation"]' );

		if ( activeItemIndex === 0 || realIndex === 0 ) {
			// First slide already properly animated.
			return;
		}

		if ( this.slidesAnimated ) {
			if ( this.slidesAnimated.includes( realIndex ) ) {
				// Already animated the current slide once.
				return;
			}
		} else {
			this.slidesAnimated = [];
		}

		function getAnimation( settings ) {
			return elementorFrontend.getCurrentDeviceSetting( settings, 'animation' ) || elementorFrontend.getCurrentDeviceSetting( settings, '_animation' );
		}

		function getAnimationDelay( settings ) {
			return elementorFrontend.getCurrentDeviceSetting( settings, 'animation_delay' ) || elementorFrontend.getCurrentDeviceSetting( settings, '_animation_delay' ) || 0;
		}

		const _self = this;
		$animsInSlide.each( function ( i, el ) {
			const $el      = jQuery( el ),
				settings   = $el.data( 'settings' ),
				anim       = settings && getAnimation( settings ),
				animDelay  = settings && getAnimationDelay( settings );

			if ( anim ) {
				_self.slidesAnimated.push( realIndex );
				$el.addClass( 'elementor-invisible' ).removeClass( 'animated' ).removeClass( anim );
				setTimeout( function() {
					$el.removeClass( 'elementor-invisible' ).addClass( 'animated ' + anim );
				}, animDelay );
			}
		} );

	},
	ensureSlidesContentVisibility: function ensureSlidesContentVisibility( $slide ) {
		jQuery( this.swipers.main.slides ).each( function ( i, slide ) {
			jQuery( slide ).find( '.elementor-invisible' ).each( function ( i, el ) {
				jQuery( el ).removeClass( 'elementor-invisible' );
			} );
		} );
	},
} );

jQuery( window ).on( 'elementor/frontend/init', () => {
	if ( ! elementorFrontend.elementsHandler || ! elementorFrontend.elementsHandler.attachHandler ) {
		const addHandler = ( $element ) => {
			elementorFrontend.elementsHandler.addHandler( VamtamSlidesHandler, {
				$element,
			} );
		};

		elementorFrontend.hooks.addAction( 'frontend/element_ready/slides.default', addHandler, 9999 );
	} else {
		elementorFrontend.elementsHandler.attachHandler( 'slides', VamtamSlidesHandler );
	}
} );
