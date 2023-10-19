<?php
/**
 * JetGallery Anchor Nav main image template.
 */

$image_id   = get_post_thumbnail_id( $post_id );
$image_src  = wp_get_attachment_image_src( $image_id, 'full' );
$image      = $this->get_gallery_image( $image_id, $settings['image_size'], $image_src, true );
$link_attrs = $this->get_image_link_attrs( $image_id );

if ( $this->gallery_has_video() && $first_place_video ) {
	array_push( $anchor_nav_controller_ids, $this->get_unique_controller_id() );
}
?>

<div class="jet-woo-product-gallery__image-item featured" id="<?php echo $anchor_nav_controller_ids[0]; ?>">
	<div class="jet-woo-product-gallery__image<?php echo $zoom_class; ?>">
		<?php
		printf( '<a %s>%s</a>', jet_woo_product_gallery_tools()->implode_html_attributes( $link_attrs ), $image );

		if ( $enable_gallery && 'button' === $gallery_trigger ) {
			$this->get_gallery_trigger_button( $this->render_icon( 'gallery_button_icon', '%s', '', false ) );
		}
		?>
	</div>
</div>