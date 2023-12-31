<?php
/**
 * JetGallery Anchor template.
 */

$enable_gallery            = filter_var( $settings['enable_gallery'], FILTER_VALIDATE_BOOLEAN );
$gallery_trigger           = $settings['gallery_trigger_type'];
$zoom_class                = filter_var( $settings['enable_zoom'], FILTER_VALIDATE_BOOLEAN ) ? ' jet-woo-product-gallery__image--with-zoom' : '';
$video_type                = jet_woo_gallery_video_integration()->get_video_type( $settings );
$video                     = $this->get_video_html();
$first_place_video         = 'content' === $settings['video_display_in'] ? filter_var( $settings['first_place_video'], FILTER_VALIDATE_BOOLEAN ) : false;
$wrapper_classes           = $this->get_wrapper_classes( [ 'jet-woo-product-gallery-anchor-nav' ], $settings );
$anchor_nav_controller_ids = [];

if ( isset( $settings['navigation_controller_position'] ) ) {
	$wrapper_classes[] = 'jet-woo-product-gallery-anchor-nav-controller-' . $settings['navigation_controller_position'];
}

if ( ! $with_featured_image && $first_place_video || $with_featured_image ) {
	$anchor_nav_controller_ids = [ $this->get_unique_controller_id() ];
}
?>

<div class="<?php echo implode( ' ', $wrapper_classes ); ?>" data-featured-image="<?php echo $with_featured_image; ?>">
	<div class="jet-woo-product-gallery-anchor-nav-items">
		<?php
		if ( 'content' === $settings['video_display_in'] && $this->gallery_has_video() && $first_place_video ) {
			include $this->get_global_template( 'video' );
		}

		if ( $with_featured_image ) {
			if ( has_post_thumbnail( $post_id ) ) {
				include $this->get_global_template( 'image' );
			} else {
				if ( $this->gallery_has_video() && $first_place_video ) {
					array_push( $anchor_nav_controller_ids, $this->get_unique_controller_id() );
				}

				printf(
					'<div class="jet-woo-product-gallery__image-item featured no-image" id="%s"><div class="jet-woo-product-gallery__image image-with-placeholder"><img src="%s" alt="%s" class="%s"></div></div>',
					$this->gallery_has_video() && $first_place_video ? $anchor_nav_controller_ids[1] : $anchor_nav_controller_ids[0],
					$this->get_featured_image_placeholder(),
					__( 'Placeholder', 'jet-woo-product-gallery' ),
					'wp-post-image'
				);
			}
		}

		if ( $attachment_ids ) {
			foreach ( $attachment_ids as $attachment_id ) {
				include $this->get_global_template( 'thumbnails' );
			}
		}

		if ( 'content' === $settings['video_display_in'] && $this->gallery_has_video() && ! $first_place_video ) {
			include $this->get_global_template( 'video' );
			array_push( $anchor_nav_controller_ids, $anchor_nav_controller_id );
		}
		?>
	</div>

	<?php include $this->get_global_template( 'controller' ); ?>

</div>