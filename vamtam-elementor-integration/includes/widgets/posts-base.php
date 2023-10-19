<?php
namespace VamtamElementor\Widgets\PostsBase;

// Extending the Posts/Posts_Archive widgets.

// Is Pro Widget.
if ( ! \VamtamElementorIntregration::is_elementor_pro_active() ) {
	return;
}

if ( vamtam_theme_supports( 'posts-base.classic--theme-image-anim' ) ) {
	function add_use_theme_image_anim_control_for_skin( $controls_manager, $widget, $skin ) {
		$widget->start_injection( [
			'of' => "{$skin}_img_border_radius",
			'at' => 'before',
		] );
		$widget->add_control(
			"{$skin}_use_theme_image_anim",
			[
				'label' => __( 'Use Theme Animation', 'vamtam-elementor-integration' ),
				'type' => $controls_manager::SWITCHER,
				'prefix_class' => 'vamtam-has-',
				'return_value' => 'image-theme-anim',
				'default' => 'image-theme-anim',
				'condition' => [
					'_skin' => $skin,
				]
			]
		);
		$widget->end_injection();
	}
	// Style - Image Section (Classic Layout).
	function section_design_image_before_section_end( $widget, $args ) {
		$controls_manager = \Elementor\Plugin::instance()->controls_manager;
		if ( $widget->get_name() === 'posts' ) {
			add_use_theme_image_anim_control_for_skin( $controls_manager, $widget, 'classic' );
		}
		if ( $widget->get_name() === 'archive-posts' ) {
			add_use_theme_image_anim_control_for_skin( $controls_manager, $widget, 'archive_classic' );
		}
	}
	add_action( 'elementor/element/posts/classic_section_design_image/before_section_end', __NAMESPACE__ . '\section_design_image_before_section_end', 10, 2 );
	add_action( 'elementor/element/archive-posts/archive_classic_section_design_image/before_section_end', __NAMESPACE__ . '\section_design_image_before_section_end', 10, 2 );
}

if ( vamtam_theme_supports( 'posts-base.classic--fitness-content-style' ) ) {
	function add_use_theme_content_style_control_for_skin( $controls_manager, $widget, $skin ) {
		$widget->start_injection( [
			'of' => "{$skin}_heading_title_style",
			'at' => "before",
		] );
		$widget->add_control(
			"{$skin}_use_theme_content_style",
			[
				'label' => __( 'Use Theme Style', 'vamtam-elementor-integration' ),
				'type' => $controls_manager::SWITCHER,
				'prefix_class' => 'vamtam-has-',
				'return_value' => 'theme-content-style',
				'default' => 'theme-content-style',
				'condition' => [
					'_skin' => $skin,
				]
			]
		);
		$widget->end_injection();
	}

	// Style - Content Section (Classic Layout).
	function section_design_content_before_section_end( $widget, $args ) {
		$controls_manager = \Elementor\Plugin::instance()->controls_manager;
		if ( $widget->get_name() === 'posts' ) {
			add_use_theme_content_style_control_for_skin( $controls_manager, $widget, 'classic' );
		}
		if ( $widget->get_name() === 'archive-posts' ) {
			add_use_theme_content_style_control_for_skin( $controls_manager, $widget, 'archive_classic' );
		}
	}
	add_action( 'elementor/element/posts/classic_section_design_content/before_section_end', __NAMESPACE__ . '\section_design_content_before_section_end', 10, 2 );
	add_action( 'elementor/element/archive-posts/archive_classic_section_design_content/before_section_end', __NAMESPACE__ . '\section_design_content_before_section_end', 10, 2 );
}