<?php
 
/*
Plugin Name: Custom Post Images
Plugin URI: http://darrenkrape.com/
Description: Adds custom images to posts
Author: Darren Krape
Version: 1.0
Author URI: http://darrenkrape.com/
*/

//http://codex.wordpress.org/Function_Reference/add_action#Using_with_a_Class
//http://codex.wordpress.org/Function_Reference/add_meta_box

class CustomPostImages {
	
	// Create array of images (could be added to a settings page instead of hardcoded)
	private $cpi_images_array = array(
        '0' => array(
            'title' => 'Breadcrumbs Background',
            'slug' => 'breadcrumbs_bg'
        ),
		/*'1' => array(
			'title' => 'Page Header',
			'slug' => 'header'
		),*/
		/*'2' => array(
			'title' => 'Featured Image',
			'slug' => 'featured'
		)*/
	);
	
	private $cpi_post_types = array('post', 'page'); //limit meta box to certain post types

    public function __construct() {
	    
	    add_action( 'add_meta_boxes', array( $this, 'cpi_add_meta_box' ) ); // Add the meta box.
	    
		add_action( 'save_post', array( $this, 'cpi_save' ) ); // Save meta box data
	    
	    add_action( 'admin_print_styles', array( $this, 'cpi_admin_styles' ) ); // Add CSS styles
	    
	    add_action( 'admin_enqueue_scripts', array( $this, 'cpi_image_enqueue' ) ); // Add JavaScript

    }
    
	/**
	 * Adds the meta box container.
	 */
	 
	public function cpi_add_meta_box( $post_type ) {
        
        if ( in_array( $post_type, $this->cpi_post_types ) ) {
            
			add_meta_box(
				'cpi_meta_box'
				,__( 'Post Images', 'cpi-textdomain' )
				,array( $this, 'cpi_render_meta_box_content' )
				,$post_type
				,'advanced'
				,'high'
			);
			
        }
	}
	
	/**
	 * Render Meta Box content.
	 *
	 * @param WP_Post $post The post object.
	 */
	
	public function cpi_render_meta_box_content( $post ) {
		
		// Add an nonce field so we can check for it later.
		wp_nonce_field( basename( __FILE__ ), 'cpi_nonce' );

		$cpi_stored_meta = get_post_meta( $post->ID );

		echo '<ul id="cpi">';

		foreach( $this->cpi_images_array as $cpi_image ) {
            $cpi_type_name = "cpi-type-" . $cpi_image['slug'];
            
		?>

		<li class="cpi-upload" id="<?php echo $cpi_type_name; ?>">
		
			<p class="cpi-upload-header"><?php echo $cpi_image['title']; ?></p>
			
			<div class="cpi-upload-thumbnail">
				<?php if( isset($cpi_stored_meta[$cpi_type_name]) ) { ?>
                        <div style="background-image: url(<?php echo wp_get_attachment_url( $cpi_stored_meta[$cpi_type_name][0] ); ?>);"></div>
                    <?php } ?>
			</div>
			
			<input type="button" class="button cpi-button cpi-upload-button" value="<?php _e( 'Choose Image ', 'cpi-textdomain' )?>" />
	
			<input type="button" class="button cpi-button cpi-upload-clear" value="&#215;" />
			
			<input class="cpi-upload-id" type="hidden" name="<?php echo $cpi_type_name ?>" value="<?php if ( isset ( $cpi_stored_meta[$cpi_type_name] ) ) echo $cpi_stored_meta[$cpi_type_name][0]; ?>" />
			
		</li>

        <?php

		}
		
		echo '<ul>';
		
	}
	
	/**
	 * Save the meta when the post is saved.
	 *
	 * @param int $post_id The ID of the post being saved.
	 */
	 
	public function cpi_save( $post_id ) {
	
		/*
		 * We need to verify this came from the our screen and with proper authorization,
		 * because save_post can be triggered at other times.
		 */

		// Check if our nonce is set.
		if ( ! isset( $_POST['cpi_nonce'] ) )
			return $post_id;

		$nonce = $_POST['cpi_nonce'];

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $nonce, basename( __FILE__ ) ) )
			return $post_id;

		// If this is an autosave, our form has not been submitted,
                //     so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return $post_id;

		// Check the user's permissions.
		if ( 'page' == $_POST['post_type'] ) {

			if ( ! current_user_can( 'edit_page', $post_id ) )
				return $post_id;
	
		} else {

			if ( ! current_user_can( 'edit_post', $post_id ) )
				return $post_id;
		}

		/* OK, its safe for us to save the data now. */
		
		foreach( $this->cpi_images_array as $cpi_image ) {
		    
		    $cpi_type_name = "cpi-type-" . $cpi_image['slug'];
			    
			// Sanitize the user input.
		    $cpi_data = sanitize_text_field( $_POST[ $cpi_type_name ] );
			    
			// Update the meta field.
		    if( $cpi_data ) {
	            update_post_meta( $post_id, $cpi_type_name, $cpi_data );
		    } else {
			    delete_post_meta( $post_id, $cpi_type_name );
		    }
		    
		}
		
	}

	/**
	 * Adds the image management javascript.
	 */
	 
	public function cpi_image_enqueue() {
		
		global $typenow;
		
        if ( in_array( $typenow, $this->cpi_post_types )) {
	        
			wp_enqueue_media();
	 
			// Registers and enqueues the required javascript.
			wp_register_script( 'cpi-meta-box-image', plugin_dir_url( __FILE__ ) . 'custom-post-images.js', array( 'jquery' ) );
			wp_localize_script( 'cpi-meta-box-image', 'meta_image',
				array(
					'title' => __( 'Choose or Upload an Image test', 'cpi-textdomain' ),
					'button' => __( 'Use this image', 'cpi-textdomain' ),
				)
			);
			
			wp_enqueue_script( 'cpi-meta-box-image' );
			
		}
	}

	/**
	 * Adds the meta box stylesheet.
	 */
	 
	public function cpi_admin_styles() {
		
		global $typenow;
        
        if ( in_array( $typenow, $this->cpi_post_types )) {
			wp_enqueue_style( 'cpi_meta_box_styles', plugin_dir_url( __FILE__ ) . 'custom-post-images.css' );
		}
	}
}

$custom_post_images = new CustomPostImages();

/**
 *   Custom Filter: for using in theme templates
 *
 * @param $value
 * @return bool|false|string
 */
function cpi_featured_image_callback( $value, $image_slug ) { // $image_slug = 'breadcrumbs_bg' | 'header' | 'featured'

    global $post;

    $featured_meta = get_post_meta( $post->ID );
    $featured_meta_image = false;

    if( isset($featured_meta['cpi-type-' . $image_slug][0]) ) { // eg.: 2060 (post ID)
        $featured_meta_image = wp_get_attachment_url($featured_meta['cpi-type-' . $image_slug][0]);
    }
    return $featured_meta_image;
}
add_filter( 'cpi_featured_image', 'cpi_featured_image_callback', $priority = 10, $accepted_args = 2);


/**
 *  Get featured image in your theme
 */
# $featured_meta = get_post_meta( $post->ID );
# $featured_meta_image = wp_get_attachment_url( $featured_meta['cpi-type-header'][0] );
# $featured_meta_image = wp_get_attachment_url( $featured_meta['cpi-type-featured'][0] );
  // or with underscore sign:
# $featured_meta_image = wp_get_attachment_url( $featured_meta['_cpi-type-header'][0] );
# $featured_meta_image = wp_get_attachment_url( $featured_meta['_cpi-type-featured'][0] );