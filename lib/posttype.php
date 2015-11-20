<?php
//* This file add the custom post type to our slider plugin.

//* Create slides custom post type 
add_action( 'init', 'fcwp_slides_post_type' );
function fcwp_slides_post_type() {
    register_post_type( 'fcwp-slides',
        array(
            'labels' 		=> array(
                'name' => __( 'Slides' ),
                'singular_name' => __( 'Slide' ),
            	),
            'has_archive' 	=> true,
            'public' 		=> true,
            'rewrite' 		=> array( 'slug' => 'slide' ),
            'supports' 		=> array( 'title', 'excerpt', 'thumbnail' ),
	        'menu_position' => 5,
	        'menu_icon'     => 'dashicons-images-alt',
            'register_meta_box_cb' => 'add_slides_metaboxes'
        )
    );
}

//* Create slider taxonomy for slides CPT
add_action( 'init', 'fcwp_sliders_tax' );
function fcwp_sliders_tax() {
		$labels = array(
		'name'              => _x( 'Slider', 'taxonomy general name' ),
		'singular_name'     => _x( 'Sliders', 'taxonomy singular name' ),
		'search_items'      => __( 'Search Sliders' ),
		'all_items'         => __( 'All Sliders' ),
		'parent_item'       => __( 'Parent Slider' ),
		'parent_item_colon' => __( 'Parent Slider:' ),
		'edit_item'         => __( 'Edit Slider' ),
		'update_item'       => __( 'Update Slider' ),
		'add_new_item'      => __( 'Add New Slider' ),
		'new_item_name'     => __( 'New Slider' ),
		'menu_name'         => __( 'Sliders' ),
	);

	$args = array(
		'hierarchical'      => true,
		'labels'            => $labels,
		'show_ui'           => true,
		'show_admin_column' => true,
		'query_var'         => true,
		'rewrite'           => array( 'slug' => 'slider' ),
	);
	register_taxonomy( 'sliders-tax', 'fcwp-slides', $args);
}

//* Change CPT title text
add_action( 'gettext', 'fcwp_change_title_text' );
function fcwp_change_title_text( $translation ) {
    global $post;
    if( isset( $post ) ) {
        switch( $post->post_type ){
            case 'fcwp-slides' :
                if( $translation == 'Enter title here' ) return 'Enter Slide Title Here';
            break;
        }
    }
    return $translation;
}


//* Add slides to dashboard "At A Glance" metabox
add_action( 'dashboard_glance_items', 'fcwp_cpt_at_glance' );
function fcwp_cpt_at_glance() {
    $args = array(
        'public' => true,
        '_builtin' => false
    );
    $output = 'object';
    $operator = 'and';

    $post_types = get_post_types( $args, $output, $operator );
    foreach ( $post_types as $post_type ) {
        $num_posts = wp_count_posts( $post_type->name );
        $num = number_format_i18n( $num_posts->publish );
        $text = _n( $post_type->labels->singular_name, $post_type->labels->name, intval( $num_posts->publish ) );
        if ( current_user_can( 'edit_posts' ) ) {
            $output = '<a href="edit.php?post_type=' . $post_type->name . '">' . $num . ' ' . $text . '</a>';
            echo '<li class="post-count ' . $post_type->name . '-count">' . $output . '</li>';
            } else {
            $output = '<span>' . $num . ' ' . $text . '</span>';
                echo '<li class="post-count ' . $post_type->name . '-count">' . $output . '</li>';
            }
    }
}

//* Set custom icons for slides on dashboard
add_action('admin_head', 'fcwp_dashboard_cpts_css');
function fcwp_dashboard_cpts_css() {
       echo '<style type="text/css">#dashboard_right_now .fcwp-slides-count a:before, #dashboard_right_now .fcwp-slides-count span:before { content: "\f232" !important; } </style>';
}

//* Add slide details metabox
function add_slides_metaboxes() {
    add_meta_box('fcwp_slide_details', 'Slide Details', 'fcwp_slide_details', 'fcwp-slides', 'normal', 'default');
}

//* Add fields to slide details metabox
function fcwp_slide_details() {
    global $post;
    
    // Noncename needed to verify where the data originated
    echo '<input type="hidden" name="slidemeta_noncename" id="slidemeta_noncename" value="' . 
    wp_create_nonce( plugin_basename(__FILE__) ) . '" />';
    
    // Get the slide details if they have already been entered
    $slidelink = get_post_meta($post->ID, '_slidelink', true);
    $mobileimgsrc = get_post_meta($post->ID, '_mobileimgsrc', true);
    
    // Display the fields
    echo '<p>Enter Slide Link</p>';
    echo '<input type="text" name="_slidelink" value="' . $slidelink  . '" class="widefat" />';
    echo '<p>Enter URL to Mobile Image</p>';
    echo '<input type="text" name="_mobileimgsrc" value="' . $mobileimgsrc  . '" class="widefat" />';

}

//* Save the metabox data when slide is saved
add_action('save_post', 'fcwp_save_slide_meta', 1, 2);
function fcwp_save_slide_meta($post_id, $post) {
    
    // verify this came from the our screen and with proper authorization because save_post can be triggered at other times
    if ( !wp_verify_nonce( $_POST['slidemeta_noncename'], plugin_basename(__FILE__) )) {
    return $post->ID;
    }

    // Is the user allowed to edit the post or page?
    if ( !current_user_can( 'edit_post', $post->ID ))
        return $post->ID;

    // After authentication, find and save the data using an array    
    $slides_meta['_slidelink'] = $_POST['_slidelink'];
    $slides_meta['_mobileimgsrc'] = $_POST['_mobileimgsrc'];
    
    // Add values of $slides_meta as custom fields    
    foreach ($slides_meta as $key => $value) { // Cycle through the $slides_meta array
        if( $post->post_type == 'revision' ) return; // Don't store custom data twice
        $value = implode(',', (array)$value); // If $value is an array, make it a CSV (unlikely)
        if(get_post_meta($post->ID, $key, FALSE)) { // If the custom field already has a value
            update_post_meta($post->ID, $key, $value);
        } else { // If the custom field doesn't have a value
            add_post_meta($post->ID, $key, $value);
        }
        if(!$value) delete_post_meta($post->ID, $key); // Delete if blank
    }

}