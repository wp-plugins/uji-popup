<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Uji_Popups_Admin extends Uji_Popups_Admin_API{
	var $version;
	private $file;
	public static $plugin_url;
	public static $plugin_path;
        public static function location_types() { 
           return array("left-top" => "Top Left",
                        "center-top" => "Top Center",
                        "right-top" => "Top Right",
                        "left-center" => "Middle Left",
                        "center" => "Middle Center",
                        "right-center" => "Middle Right",
                        "left-bottom" => "Bottom Left",
                        "center-bottom" => "Bottom Center",
                        "right-bottom" => "Bottom Right");				
        }
	
	/**
	 * __construct function.
	 * 
	 */
	public function __construct ( $file ) {
		parent::__construct(); // Required in extended classes.
		
		$this->token = 'ujipopup';
		$this->page_slug = 'ujipopup-api';
		$this->opt_name = __( 'Uji Popup Options', 'ujipopup' );
		
		$this->post_meta = 'popups_meta';
		
		self::$plugin_url = trailingslashit( plugins_url( '', $plugin = $file ) );
		self::$plugin_path = trailingslashit( dirname( $file ) );
		
		$this->labels = array();
		$this->setup_post_type_labels_base();
		
		//Add Post Type
		add_action( 'init', array( &$this, 'add_post_type_ads' ), 100 );
	
		//Add Columns
		add_filter( 'manage_edit-popups_columns', array( &$this, 'add_column_headings' ), 10, 1 );
		add_action( 'manage_posts_custom_column', array( &$this, 'add_column_data' ), 10, 2 );
		
		//Change Ad Title Here
		add_filter( 'enter_title_here', array( &$this, 'change_default_title' ) );

		//add admin .css
		add_action( 'admin_enqueue_scripts', array( &$this, 'admin_styles_popups' ), 9999 );
		//add admin .js
		add_action( 'admin_print_scripts', array( &$this, 'admin_enqueue_scripts' ) );
		
		//add menu
		add_action( 'admin_menu', array( &$this, 'ujipopup_menu' ) );
		
		//Metaboxes
		add_action( 'add_meta_boxes', array( &$this, 'popups_meta_boxes' ) );
		
		//AdminInit::Save Post
		add_action( 'save_post', array( &$this, 'popups_save') );	
		
		//Remove
		add_action( 'admin_head', array( &$this, 'remove_pop' ) );
		
	}

	
	/**
	 * Setup the singular, plural and menu label names for the post types.
	 * @since  1.0.0
	 * @return void
	 */
	private function setup_post_type_labels_base () {
		$this->labels = array( 'popups' => array() );
		
		$this->labels['popups'] = array( 'singular' => __( 'Popup', 'ujipopup' ), 'plural' => __( 'Popups', 'ujipopup' ), 'menu' => __( 'Uji Popup', 'ujipopup' ) );
	} // End setup_post_type_labels_base()
	
	/**
	 * Location
	 * @since  1.1.1
	 * @return void
	 */
	private function ujip_select( $name, $loc_arr, $sel, $chk ){
            if(!empty( $loc_arr ) ){
                
                $sel = ( isset($sel) && !empty($sel) && array_key_exists($sel, $loc_arr) ) ? $sel : $chk;
                
                $select   = '<select name="'.$name.'" id="popup_' . $name . '">';
                foreach ( $loc_arr as $v => $n ) {
                        $selected = (isset( $sel ) && !empty( $sel ) && $sel == $v ) ? ' selected="selected"' : '';
                        $select  .= '<option value="' . $v . '" ' . $selected . '> ' . $n . ' </option>';
                }
                $select  .= '</select>';
            }
            
            return $select;
        }
        
	/**
	 * Setup the "Uji Popups" post type
	 * @since  1.0.0
	 * @return void
	 */
	public function add_post_type_ads () {
		$args = array(
		    'labels' => $this->create_post_type_labels( 'popups', $this->labels['popups']['singular'], $this->labels['popups']['plural'], $this->labels['popups']['menu'] ),
		    'public' => false,
		    'publicly_queryable' => true,
		    'show_ui' => true, 
		    'show_in_menu' => true, 
		    'query_var' => true,
		    'rewrite' => array( 'slug' => 'popups', 'with_front' => false, 'feeds' => false, 'pages' => false ),
		    'capability_type' => 'post',
		    'has_archive' => false, 
		    'hierarchical' => false,
		    'menu_position' => 100, // Below "Pages"
		    'menu_icon' => esc_url( self::$plugin_url . 'images/icon_popups.png' ), 
		    'supports' => array( 'title' )
		);

		register_post_type( 'popups', $args );
	} // End setup_zodiac_post_type()
	
	/**
	 * Add column headings to the "slides" post list screen.
	 * @access public
	 * @since  1.0.0
	 */
	public function add_column_headings ( $defaults ) {
		$new_columns['cb'] = '<input type="checkbox" />';
		// $new_columns['id'] = __( 'ID' );
		$new_columns['title'] = _x( 'Popup Title', 'column name', 'ujipopup' );
		$new_columns['impression'] = _x( 'Impressions', 'column name', 'ujipopup' );
		
		if ( isset( $defaults['date'] ) ) {
			$new_columns['date'] = $defaults['date'];
		}

		return $new_columns;
	} // End add_column_headings()
	
	/**
	 * Add data for our newly-added custom columns.
	 * @access public
	 * @since  1.0.0
	 */
	public function add_column_data ( $column_name, $id ) {
		global $wpdb, $post;
		
		switch ( $column_name ) {
			case 'id':
				echo $id;
			break;

			case 'impression':
				$num = get_post_meta( $id, 'pop_impressions', true );
				echo ( !empty($num) ) ? $num : 0;
			break;

			default:
			break;
		}
	} // End add_column_data()
	
	/**
	 * Labels for post type
	 * @since  1.0.0
	 * @return void
	 */
	private function create_post_type_labels ( $token, $singular, $plural, $menu ) {
		$labels = array(
		    'name' => sprintf( _x( '%s', 'post type general name', 'ujipopup' ), $plural ),
		    'singular_name' => sprintf( _x( '%s', 'post type singular name', 'ujipopup' ), $singular ),
		    'add_new' => sprintf( _x( 'Add New %s', $token, 'ujipopup' ), $singular ),
		    'add_new_item' => sprintf( __( 'Add New %s', 'ujipopup' ), $singular ),
		    'edit_item' => sprintf( __( 'Edit %s', 'ujipopup' ), $singular ),
		    'new_item' => sprintf( __( 'New %s', 'ujipopup' ), $singular ),
		    'all_items' => sprintf( __( 'All %s', 'ujipopup' ), $plural ),
		    'view_item' => sprintf( __( 'View %s', 'ujipopup' ), $singular ),
		    'search_items' => sprintf( __( 'Search %s', 'ujipopup' ), $plural ),
		    'not_found' =>  sprintf( __( 'No %s found', 'ujipopup' ), strtolower( $plural ) ),
		    'not_found_in_trash' => sprintf( __( 'No %s found in Trash', 'ujipopup' ), strtolower( $plural ) ), 
		    'parent_item_colon' => '',
		    'menu_name' => $menu
		  );

		return $labels;
	} // End create_post_type_labels()
	
	/**
	 * Load the global admin styles for the menu icon and the relevant page icon.
	 * @access public
	 * @since 1.0.0
	 * @return void
	 */
	public function admin_styles_popups () {
        $screen = get_current_screen();

        if ( in_array( $screen->id, array( 'popups', 'popups_page_ujipopup-api' ) ) || in_array( $screen->id, array( 'edit-popups', 'popups_page_ujipopup-api' ) ) ) :
            wp_dequeue_style( array('adminstyle', 'adminstyle-css') );
        
            wp_register_style( 'admin-popups', self::$plugin_url . 'css/admin.css', '', '1.0', 'screen' );
            wp_register_style( 'bootstrap', self::$plugin_url . 'assets/bootsrap/css/bootstrap.css', '', '2.0', 'screen' );
            if ( floatval( get_bloginfo( 'version' ) ) < 3.5 ) {
                wp_register_style( 'colorpicker', self::$plugin_url . 'assets/colorpicker/css/colorpicker.css', '', '1.0', 'screen' );
            }
            wp_register_style( 'datapicker', self::$plugin_url . 'assets/datepicker/css/datepicker.css', '', '1.0', 'screen' );
            wp_enqueue_style( 'admin-popups' );
            wp_enqueue_style( 'bootstrap' );
            if ( floatval( get_bloginfo( 'version' ) ) >= 3.5 ) {
                wp_dequeue_style( 'colorpicker' );
                wp_dequeue_style( 'color-picker' );
                wp_dequeue_style( 'wp-color-picker' );
                wp_enqueue_style( 'wp-color-picker' );
            } else {
                wp_enqueue_style( 'colorpicker' );
            }
            wp_enqueue_style( 'datapicker' );

        endif;
    }

// End admin_styles_global()
	
		/**
	 * enqueue_scripts function.
	 *
	 * @description Load in JavaScripts where necessary.
	 */
	public function admin_enqueue_scripts () {
		
		$screen = get_current_screen();
		
		
		if (in_array( $screen->id, array( 'popups', 'popups_page_ujipopup-api' ))) :
		
		wp_enqueue_script( 'bootstrap', self::$plugin_url . 'assets/bootsrap/js/bootstrap.min.js', array( 'jquery' ), '2.0' );
      wp_enqueue_script( 'bootstrap-color', self::$plugin_url . 'assets/colorpicker/js/bootstrap-colorpicker.js', array( 'jquery' ), '1.0' );
		wp_enqueue_script( 'bootstrap-date', self::$plugin_url . 'assets/datepicker/js/bootstrap-datepicker.js', array( 'jquery' ), '1.0' );
      
      if ( floatval(get_bloginfo('version')) >= 3.5){
          wp_enqueue_script( 'wp-color-picker');
      }
		wp_enqueue_script( 'popups', self::$plugin_url . 'js/admin-popups.js', array( 'jquery' ), '1.0' );
		
		endif;
		
	} // End enqueue_scripts()
	
	/**
	 * Change Title
	 * @since  1.0
	 */
	public function change_default_title( $title ){
		 $screen = get_current_screen();
		 
		 if  ( 'popups' == $screen->post_type ) {
			  $title = 'Enter Ad Title Here';
		 }
	 
		 return $title;
	}
	
	/**
	 * Remove it if already exist
	 * @since  1.0
	 */
	public function remove_pop( ){
		 $screen = get_current_screen();
		$published_posts = wp_count_posts( 'popups' );
		 if  ( 'popups' == $screen->post_type ) {
			if( (int) $published_posts->publish > 0 && 'popups' == $screen->post_type ){
				remove_submenu_page( 'edit.php?post_type=popups', 'post-new.php?post_type=popups' );
				add_action('admin_footer', array( &$this, 'add_popup_css' ) );
			}
			
		 }
		 if ( $published_posts->publish >= 1 && 'popups' == $screen->post_type ) {
			 	add_action('admin_footer', array( &$this, 'add_popup_css_one' ) );
		 }
		 
		 if ( $published_posts->publish >= 1 && 'popups' != $screen->post_type ) {
			 	add_action('admin_footer', array( &$this, 'add_popup_css_two' ) );
		 }
	}
	
	/**
	 * Add footer CSS
	 * @since  1.0
	 */
	public function add_popup_css( ){
		echo '<style type="text/css">
					#favorite-actions {display:none;}
					.add-new-h2{display:none;}
					.tablenav{display:none;}
			  </style>';
	}
	
	/**
	 * Add footer CSS One
	 * @since  1.0
	 */
	public function add_popup_css_one( ){
		echo '<style type="text/css">
					#wp-admin-bar-new-popups {display:none;}
			  </style>';
	}
	
	/**
	 * Add footer CSS Two
	 * @since  1.0
	 */
	public function add_popup_css_two( ){
		echo '<style type="text/css">
					#wp-admin-bar-new-popups {display:none;}
					#menu-posts-popups ul li:nth-child(3) {display:none;}
			  </style>';
	}
	
	
	
	/**
	 * Add menu
	 * @since  1.0
	 */
	public function ujipopup_menu() {
		$hook = add_submenu_page( 'edit.php?post_type=popups', __( 'Uji Popup', 'ujipopup' ), __('Popup Options', 'ujipopup'), 'manage_options', $this->page_slug, array( &$this, 'settings_screen' ) );
		if ( isset( $_GET['page'] ) && ( $_GET['page'] == $this->page_slug ) ) {
			add_action( 'admin_notices', array( &$this, 'settings_errors' ) );
			/*add_action( 'admin_print_scripts', array( &$this, 'enqueue_scripts' ) );
			add_action( 'admin_print_styles', array( &$this, 'enqueue_styles' ) );*/
		}
  
	}
	
	/**
	 * Add metaboxes
	 * @since  1.0
	 */
	public function popups_meta_boxes() {
	global $post;
	
      // Excerpt
		if ( function_exists('wp_editor') ) {
         //WP 4 space
         $idp = (   floatval(get_bloginfo('version')) >= 4 ) ? 'postexcerpt_wp4' : 'postexcerpt';
			remove_meta_box( $idp, 'product', 'normal' );
			add_meta_box( $idp, __('Popup Content', 'ujipopup'), array( &$this, 'popups_html' ), 'popups', 'normal' );
		}
		
		add_meta_box( 'postwhere', __('Where to show', 'ujipopup'), array( &$this, 'popups_where' ), 'popups', 'normal' );
		add_meta_box( 'styles', __('Popup Style', 'ujipopup'), array( &$this, 'popups_style' ), 'popups', 'side' );	
		add_meta_box( 'getpro', __('Uji Popup Premium', 'ujinter'), array( &$this, 'popups_prover' ), 'popups', 'side' );	
		
	}
	
	/**
	 * Add HTML metaboxes
	 * @since  1.0
	 */
	public function popups_html( $post ) {
		?>
		<ul class="nav nav-tabs" id="cont_tab">
				<li><a href="#int-tab-1" data-toggle="tab"><?php _e("Text/Html", 'ujipopup') ?></a></li>
			  </ul>
	   	 <div class="tab-content">
		 <?php
		//TAB1: add editor
		?>
		<div class="tab-pane" id="int-tab-1">
				<div class="options_group tab-space">
				<p class="form-field">
					<label for="include_html"><?php _e("Includ this Popup", 'ujipopup') ?></label>  
					<input id="include_html" class="checkbox" type="checkbox" value="yes" name="include_html" <?php checked( $this->get_opt( $post->ID, 'include_html' ), 'yes' ) ?>> 
                                        <span class="description"><?php _e("Enabld/Disable this popup", 'ujipopup') ?>
				</p>
		</div>
		<?php
		$settings = array(
				'quicktags' 	=> array( 'buttons' => 'em,strong,link' ),
				'textarea_name'	=> 'excerpt',
				'quicktags' 	=> true,
				'tinymce' 		=> true,
				'editor_css'	=> '<style>#wp-excerpt-editor-container .wp-editor-area{height:275px; width:100%;}</style>'
				);
	
		wp_editor( htmlspecialchars_decode( $post->post_excerpt ), 'excerpt', $settings );
		
		echo '</div>
		</div>';

	}

	/**
	 * Where to show
	 * @since  1.0
	 */
	public function popups_where( $post ) {
	?>	
	   <div class="tab-content">
	   
	   <!-- checkbox Home Page -->
			  <div class="options_group">
				<p class="form-field">
					<label for="_see_show_home"><?php _e("Enable on Home Page", 'ujipopup') ?></label>  
					<input id="_see_show_home" class="radio" type="radio" value="show_home" name="where_show" <?php checked( $this->get_opt( $post->ID, 'where_show' ), 'show_home' ) ?>> 
					<span class="description"><?php _e("Show Popup on Home Page", 'ujipopup') ?></span>
				</p>
			   </div>
			   
	   <!-- checkbox All Pages -->
	   		 <div class="options_group">
				<p class="form-field">
					<label for="_see_show_all"><?php _e("Enable on All Pages", 'ujipopup') ?></label>  
					<input id="_see_show_all" class="radio" type="radio" value="show_all" name="where_show" <?php checked( $this->get_opt( $post->ID, 'where_show' ), 'show_all' ) ?>> 
					<span class="description"><?php _e("Show Popup on entire site", 'ujipopup') ?></span>
				</p>
			   </div>
			   
		<!-- checkbox Custom Pages -->
	  		 <div class="options_group">
				<p class="form-field">
					<label for="_see_show_cust"><?php _e("Enable on Custom Pages", 'ujipopup') ?></label>  
					<input id="_see_show_cust" class="radio radio_sub" type="radio" value="show_cust" name="where_show" <?php checked( $this->get_opt( $post->ID, 'where_show' ), 'show_cust' ) ?>> 
					<span class="description"><?php _e("Show Popup on selected Pages/Posts", 'ujipopup') ?></span>
				</p>
			   </div>	   
			   
		<!-- Select Posts/Pages -->
	    	<div id="_see_show_cust_sub" class="options_group options_sub" <?php echo ( $this->get_opt( $post->ID, 'show_cust' ) != 'show_cust' ) ? ' style="display:none"' : '' ?>>
				<p class="form-field">
					<label for="pop_link"><?php _e("Select Posts/Pages", 'ujipopup') ?></label>  
					<input type="text" name="pop_posts" class="short" id="pop_posts" value="<?php echo $this->get_opt( $post->ID, 'pop_posts' ); ?>" />  
					<span class="description"><?php _e("Add any pages or posts id separated by commas. ex: 312, 16, 27", 'ujipopup') ?></span>
				</p>
			   </div>  
      
      <!-- checkbox Shortcode link -->
	  		 <div class="options_group">
				<p class="form-field">
					<label for="_see_show_short"><?php _e("Link or Button", 'ujipopup') ?></label>  
					<input id="_see_show_short" class="radio radio_sub" type="radio" value="show_short" name="where_show" <?php checked( $this->get_opt( $post->ID, 'where_show' ), 'show_short' ) ?>> 
					<span class="description"><?php _e("Show on clicking button or link", 'ujipopup') ?></span>
				</p>
			   </div>	   
			   
		<!-- Select Shortcode link class -->
	    	<div id="_see_show_short_sub" class="options_group options_sub" <?php echo ( $this->get_opt( $post->ID, 'show_short' ) != 'show_short' ) ? ' style="display:none"' : '' ?>>
				<p class="form-field">
					<label for="pop_link"><?php _e("Add class name", 'ujipopup') ?></label>  
					<input type="text" name="pop_class" class="short" id="pop_class" value="<?php echo $this->get_opt( $post->ID, 'pop_class' ); ?>" />  
					<span class="description"><?php _e("Class name for your link or button", 'ujipopup') ?></span>
               <div style="background-color: #f6f6f6; color: #0074a2; padding: 14px 16px; display: inline-block; margin-left: 178px; border: 1px solid #969696;">
                  <span style="font-weight: bold;">[uji_popup class="<span class="uji_class"></span>" id="<?php echo $post->ID; ?>"]</span> replace with: text, banner or image here <span style="font-weight: bold;">[/uji_popup]</span>
               </div>
               <span style="display: block; margin: 4px 0 0 178px;" class="description">Copy one of the two shortcode options and paste it where you want to have the click link</span>
				</p>
               
			   </div> 
			  
			</div>
	<?php 
	}
	
	/**
	 * Popup sizes
	 * @since  1.0
	 */
	public function popups_style( $post ) {
		?>
                <div class="tab-content side-content">
                    <div class="control-group chkbox2">
                        <label class="size-label" for="add_close"><?php _e("Enable Autosize:", 'ujipopup') ?></label>  
                        <input id="auto_size" class="checkbox" type="checkbox" value="yes" name="auto_size" <?php checked($this->get_opt($post->ID, 'auto_size'), 'yes') ?>>
                        <div class="howto"><?php _e("Leave the Height and Width empty", 'ujipopup') ?></div>
                    </div>     
                    <div class="control-group input-append" id="wi1">
                        <p>
                            <label class="size-label" for="width1"><?php _e("Popup Width", 'ujipopup') ?>:</label>
                            <input class="small-text" size="16" type="text" name="width1" value="<?php echo $this->get_opt($post->ID, '_width1') ?>"> <span> px</span>
                        </p>
                        <p>
                            <label class="size-label" for="width1"><?php _e("Popup Height", 'ujipopup') ?>:</label>
                            <input class="small-text" size="16" type="text" name="height1" value="<?php echo $this->get_opt($post->ID, '_height1') ?>"> <span> px</span>
                        <div class="howto"><?php _e("Height size is auto determined if it is empty", 'ujipopup') ?></div>
                        </p>
                    </div>

                    <div class="control-group select2">         
                        <h4 style="float:none; display:block; clear: both;">Position:</h4>
                        <p>
                            <label for="popup_pop_location"><?php _e("Location", 'ujipopup') ?>:</label>
                            <?php echo $this->ujip_select('pop_location', self::location_types(), $this->get_opt($post->ID, 'pop_location'), 'center'); ?>
                        </p>
                        <div class="howto"><?php _e("Choose where the popup will be positioned.", 'ujipopup') ?></div>
                    </div>

                    <h4 style="float:none; display:block; clear: both;">Content Spaces:</h4>
                    <div class="control-group chkbox2">
                        <p><label class="size-label" for="pop_close"><?php _e("Top", 'ujipopup') ?></label>
                            <input class="small-text" id="pop_top" size="16" type="text" name="pop_top" value="<?php echo $this->get_opt($post->ID, 'pop_top') ?>"> px</p>
                        <p><label class="size-label" for="pop_close"><?php _e("Right", 'ujipopup') ?></label>
                            <input class="small-text" id="pop_right" size="16" type="text" name="pop_right" value="<?php echo $this->get_opt($post->ID, 'pop_right') ?>"> px</p>
                        <p><label class="size-label" for="add_close"><?php _e("Bottom", 'ujipopup') ?></label>
                            <input class="small-text" id="pop_bottom" size="16" type="text" name="pop_bottom" value="<?php echo $this->get_opt($post->ID, 'pop_bottom') ?>"> px</p>
                        <p><label class="size-label" for="pop_close"><?php _e("Left", 'ujipopup') ?></label>
                            <input class="small-text" id="pop_left" size="16" type="text" name="pop_left" value="<?php echo $this->get_opt($post->ID, 'pop_left') ?>"> px</p>
                    </div>
                    <div class="control-group chkbox2">
                        <label class="size-label" for="add_close"><?php _e("Show Close Button:", 'ujipopup') ?></label>  
                        <input id="add_close" class="checkbox" type="checkbox" value="yes" name="add_close" <?php checked($this->get_opt($post->ID, 'add_close'), 'yes') ?>>
                    </div>

                    <?php
                    $is = $this->get_sett('show_timer');
                    if ( $is == "yes" ):
                        ?>
                        <div class="control-group chkbox2">
                            <label class="size-label" for="add_close"><?php _e("Show Countdown:", 'ujipopup') ?></label>  
                            <input id="show_counter" class="checkbox" type="checkbox" value="yes" name="show_count" <?php checked($this->get_opt($post->ID, 'show_count'), 'yes') ?>>
                        </div>

                        <div class="control-group chkbox2">
                            <label class="size-label" for="add_close"><?php _e("Enable Wait Time:", 'ujipopup') ?></label>  
                            <input id="wait_time" class="checkbox" type="checkbox" value="yes" name="wait_time" <?php checked($this->get_opt($post->ID, 'wait_time'), 'yes') ?>>
                        </div>
                    <?php endif; ?>


                </div>
         
	<?php
		 echo  '<input type="hidden" name="popups_edit_nonce" value="'. wp_create_nonce( 'popups_edit_nonce' ) .'" />';	
	}
	
	/**
	 * Add HTML metaboxes
	 * @since  1.0
	 */
	public function popups_prover( $post ) {
		echo '<a href="http://www.wpmanage.com/uji-popup" target="_blank"><img src="'.plugins_url() . '/uji-popup/images/popup-premium.png" style="padding-left:2px" /></a>';
	}
	
	/**
	 * Save post
	 * @since  1.0
	 */
	public function popups_save( $post_id ) {
	
	
	
		if ( !$_POST ) return $post_id;
		if ( is_int( wp_is_post_revision( $post_id ) ) ) return;
		if ( is_int( wp_is_post_autosave( $post_id ) ) ) return;
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) return $post_id;
		//if ( !isset($_POST['popups_edit_nonce']) || (isset($_POST['popups_edit_nonce']) && !wp_verify_nonce( $_POST['popups_edit_nonce'], 'popups_edit_nonce' ))) return $post_id;
		if ( !current_user_can( 'edit_post', $post_id )) return $post_id;
		if ( 'popups' == $_POST['post_type'] ){
		// Save fields
			if( isset($_POST['include_html'] ) ) update_post_meta($post_id, 'include_html', esc_html(stripslashes($_POST['include_html']))); else update_post_meta($post_id, 'include_html', '');
			if( isset($_POST['where_show'] ) ) update_post_meta($post_id, 'where_show', esc_html(stripslashes($_POST['where_show'])));  else update_post_meta($post_id, 'where_show', '');
			for($x=1; $x<=5; $x++){	
				if(isset($_POST['pop_link'.$x])) update_post_meta($post_id, 'pop_link'.$x, esc_html(stripslashes($_POST['pop_link'.$x])));  else update_post_meta($post_id, 'pop_link'.$x, '');
			}
			if( isset($_POST['pop_posts'] ) ) update_post_meta($post_id, 'pop_posts', esc_html(stripslashes($_POST['pop_posts']))); else update_post_meta($post_id, 'pop_posts', '');
                        if( isset($_POST['pop_class'] ) ) update_post_meta($post_id, 'pop_class', esc_html(stripslashes($_POST['pop_class']))); else update_post_meta($post_id, 'pop_class', '');
			
                        if( isset($_POST['pop_location'] ) ) update_post_meta($post_id, 'pop_location', esc_html(stripslashes($_POST['pop_location']))); else update_post_meta($post_id, 'pop_location', '');
                        if( isset($_POST['auto_size'] ) ) update_post_meta($post_id, 'auto_size', esc_html(stripslashes($_POST['auto_size']))); else update_post_meta($post_id, 'auto_size', '');
			if( isset($_POST['width1'] ) ) update_post_meta($post_id, '_width1', esc_html(stripslashes($_POST['width1']))); else update_post_meta($post_id, '_width1', '');
                        if( isset($_POST['height1'] ) ) update_post_meta($post_id, '_height1', esc_html(stripslashes($_POST['height1']))); else update_post_meta($post_id, '_height1', '');
			if( isset($_POST['pop_top'] ) ) update_post_meta($post_id, 'pop_top', esc_html(stripslashes($_POST['pop_top']))); else update_post_meta($post_id, 'pop_top', '');
			if( isset($_POST['pop_right'] ) ) update_post_meta($post_id, 'pop_right', esc_html(stripslashes($_POST['pop_right']))); else update_post_meta($post_id, 'pop_right', '');
			if( isset($_POST['pop_bottom'] ) ) update_post_meta($post_id, 'pop_bottom', esc_html(stripslashes($_POST['pop_bottom']))); else update_post_meta($post_id, 'pop_bottom', '');
			if( isset($_POST['pop_left'] ) ) update_post_meta($post_id, 'pop_left', esc_html(stripslashes($_POST['pop_left']))); else update_post_meta($post_id, 'pop_left', '');
			if( isset($_POST['add_close'] ) ) update_post_meta($post_id, 'add_close', esc_html(stripslashes($_POST['add_close']))); else update_post_meta($post_id, 'add_close', '');
			if( isset($_POST['show_count'] ) ) update_post_meta($post_id, 'show_count', esc_html(stripslashes($_POST['show_count']))); else update_post_meta($post_id, 'show_count', '');
			if( isset($_POST['wait_time'] ) ) update_post_meta($post_id, 'wait_time', esc_html(stripslashes($_POST['wait_time']))); else update_post_meta($post_id, 'wait_time', '');
			
		}
						 
	}
	
	/**
	 * settings_errors function.
	 * @since 1.0.0
	 */
	public function settings_errors () {
		echo settings_errors( $this->token . '-errors' );
	} // End settings_errors()
	
	/**
	 * settings_screen function.
	 * @since 1.0.0
	 */
	public function settings_screen () {
	
	?>
    <div id="ujipopup" class="wrap">
        <?php screen_icon( 'popups' ); ?>
        <h2><?php echo esc_html( $this->opt_name ); ?></h2>
        
        <form action="options.php" method="post">
            <?php settings_fields( $this->page_slug ); ?>
            <?php do_settings_sections( $this->page_slug ); ?>
            <?php //$this->print_fields(); ?>
            <?php submit_button(); ?>
        </form>
    </div>
    
        <?php
        }

	
	
} // End Class
?>