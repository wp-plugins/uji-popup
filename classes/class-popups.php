<?php

if ( !defined( 'ABSPATH' ) )
    exit; // Exit if accessed directly

class Uji_Popups extends Uji_Popups_Functions {

    var $version;
    private $file;
    public $keep;
    public $ad_ajax_html;

    /**
     * __construct function.
     * 
     */
    public function __construct( $file ) {
        $this->token = 'ujipopup';
        $this->file = $file;
        $this->plugin_url = trailingslashit( plugins_url( '', $plugin = $file ) );

        $this->load_plugin_textdomain();
        add_action( 'init', array( &$this, 'load_localisation' ), 0 );

        // Setup post types.
        require_once( 'class-popups-admin-settings.php' );
        require_once( 'class-popups-admin.php' );
        $this->admin_inter = new Uji_Popups_Admin( $file );

        //Add style
        add_action( 'wp_enqueue_scripts', array( &$this, 'enqueue_styles' ) );

        //Add scripts
        add_action( 'wp_footer', array( &$this, 'enqueue_scripts' ) );

        //Add Ads Ajax
        if ( $this->is_cached() ) {
            add_action( 'wp_ajax_inter_pop_action', array( &$this, 'inter_ajax_ads' ) );
            add_action( 'wp_ajax_nopriv_inter_pop_action', array( &$this, 'inter_ajax_ads' ) );
        }

        //Ajax imprssion link
        add_action( 'wp_ajax_inter_pop_impress', array( &$this, 'inter_ajax_impress' ) );

        //Add Ads PHP
        add_action( 'wp_footer', array( &$this, 'popup_ads' ) );

        //Add shortcode
        add_shortcode( 'uji_popup', array( $this, 'uji_popup_code' ) );
    }

// End__construct()

    /**
     * Load the plugin's localisation file.
     * @since 1.0
     */
    public function load_localisation() {
        load_plugin_textdomain( 'ujipopup', false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
    }

// End load_localisation()

    /**
     * Load the plugin textdomain from the main WordPress "languages" folder.
     * @since  1.0
     */
    public function load_plugin_textdomain() {
        $domain = 'ujipopup';
        // The "plugin_locale" filter is also used in load_plugin_textdomain()
        $locale = apply_filters( 'plugin_locale', get_locale(), $domain );

        load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
        load_plugin_textdomain( $domain, FALSE, dirname( plugin_basename( $this->file ) ) . '/lang/' );
    }

// End load_plugin_textdomain()

    /**
     * Register frontend CSS files.
     * @since  1.0
     * @return void
     */
    public function enqueue_styles() {
        wp_register_style( $this->token . '-modalcss', esc_url( $this->plugin_url . 'modal/css/jquery.modal.css' ), '', '0.5.5', 'all' );
        wp_register_style( $this->token . '-popups', esc_url( $this->plugin_url . 'css/popups.css' ), '', '1.0', 'all' );
    }

// End enqueue_styles()

    /**
     * Register frontend JS files.
     * @since  1.0
     * @return void
     */
    public function enqueue_scripts() {
        wp_register_script( $this->token . '-modal', esc_url( $this->plugin_url . 'modal/jquery.modal.min.js' ), array( 'jquery' ), '0.5.5', true );
        wp_register_script( $this->token . '-count', esc_url( $this->plugin_url . 'js/jquery.countdown.js' ), array( 'jquery' ), '1.4.0', true );
        wp_register_script( $this->token . '-popups', esc_url( $this->plugin_url . 'js/popups.js' ), array( 'jquery' ), '1.0', true );
    }

// End register_script()

    /**
     * Check trigger it
     * @since  1.0
     */
    public function popup_ads( $id = NULL, $ajax = NULL ) {
        if ( $id ) {
            $id_post = $id;
        } else {
            global $post;
            $id_post = $post->ID;
        }
        //AD id
        $ad_id = $this->is_popups( $id_post, $ajax );

        $settings = array(
            'bar_color' => 'bar_color',
            'title_color' => 'title_color',
            'back_color' => 'back_color',
            'close_name' => 'but_close',
            'show_timer' => 'show_timer',
            'countdown_time' => 'countdown_time',
            'wait_time' => 'wait_time',
            'tra_close' => 'tra_close',
            'tra_wait' => 'tra_wait',
            'tra_seconds' => 'tra_seconds',
            'tra_minutes' => 'tra_minutes',
            'tra_until' => 'tra_until'
        );

        foreach ( $settings as $set => $name ) {
            ${$name} = $this->int_option( $set );
        }
        //Countdown
        $timer = $this->get_interad( $ad_id, 'timer' );
        //Wait time
        $wait = $this->get_interad( $ad_id, 'wait' );

        // ADD val if cached
        if ( $this->is_cached() ) {

            $JSujiPopups = array( 'is_cached' => 'true', 'id_post' => $id_post, 'is_front' => is_front_page(), 'ajaxurl' => admin_url( 'admin-ajax.php' ) );

            //Shortcode
            global $uji_short;
            $short = $this->get_interad( $ad_id, 'shortcode' );
            if ( isset( $uji_short ) && $uji_short != '' && $short ) {
                $JSujiPopups = array_merge( $JSujiPopups, array( 'is_short' => $uji_short ) );
            } else {
                //because is ajax trigger just once
                if( $id )
                    $this->impression( $ad_id );
            }



            //Countdown
            if ( !empty( $wait_time ) && (int) $wait_time > 0 && $wait ) {
                $JSujiPopups = array_merge( $JSujiPopups, array( 'is_wait' => $wait_time ) );
            }
            //Timing	
            if ( $timer ) {

                if ( !empty( $countdown_time ) && (int) $countdown_time > 0 && $show_timer == 'yes' ) {
                    wp_enqueue_script( $this->token . '-count' );
                    $JSujiPopups = array_merge( $JSujiPopups, array( 'is_count' => $countdown_time ) );
                    $add_wait = (!empty( $wait_time ) && (int) $wait_time > 0 ) ? (int) $wait_time : 0;
                    //$countdown = time() + $add_wait + ( int ) $countdown_time;
                    $countdown = $countdown_time;
                }
                if ( !empty( $tra_seconds ) && $show_timer == 'yes' ) {
                    $JSujiPopups = array_merge( $JSujiPopups, array( 'seconds' => $tra_seconds ) );
                }
                if ( !empty( $tra_minutes ) && $show_timer == 'yes' ) {
                    $JSujiPopups = array_merge( $JSujiPopups, array( 'minutes' => $tra_minutes ) );
                }
            }
           
            //Close button
            $close = $this->get_interad( $ad_id, 'close' );
            if ( $close ) {
                $JSujiPopups = array_merge( $JSujiPopups, array( 'showclose' => 'true' ) );
            }
            
            //Class
            $class = $this->get_interad( $ad_id, 'class' );
            if ( $class != 'center' ) {
                $JSujiPopups = array_merge( $JSujiPopups, array( 'location' => $class ) );
            }

            wp_enqueue_style( $this->token . '-modalcss' );
            wp_enqueue_style( $this->token . '-popups' );
            wp_enqueue_script( $this->token . '-modal' );
            wp_enqueue_script( $this->token . '-popups' );
            wp_localize_script( $this->token . '-popups', 'ujiPopups', $JSujiPopups );
        }

        if ( $ad_id ) {

            $html_ad = $style_main = $style_cont = '';

            $back_color = !empty( $back_color ) ? 'background:' . $back_color . ';' : '';
            $bar_color = !empty( $bar_color ) ? 'background:' . $bar_color . ';' : '';
            $tit_color = !empty( $title_color ) ? 'color:' . $title_color . ';' : '';
            $is_wait = !empty( $wait_time ) && (int) $wait_time > 0 ? 'display: none;' : '';

            $col_style = !empty( $tit_color ) ? 'style="' . $tit_color . '"' : '';
            $bor_style = !empty( $title_color ) ? 'style="border-color:' . $title_color . ';"' : '';


            if ( !empty( $back_color ) || !empty( $is_wait ) ) {
                $style_main = $back_color . $is_wait;
            }

            $style_main = 'style="' . $this->get_interad( $ad_id, 'style' ) . $back_color . $is_wait . '"';
            $style_cont = 'style="' . $this->get_interad( $ad_id, 'style_cnt' ) . '"';

            if ( !empty( $cont_width ) ) {
                $style_cont = 'style="' . $cont_width . '"';
            }

            if ( !empty( $bar_color ) || !empty( $tit_color ) ) {
                $style_bar = 'style="' . $bar_color . $tit_color . '"';
            }
            //Not caching
            if ( !$this->is_cached() ) {

                $JSujiPopups = array();

                //Timing		
                if ( !empty( $wait_time ) && (int) $wait_time > 0 && $wait ) {
                    $JSujiPopups = array( 'is_wait' => $wait_time );
                }

                //Countdown	
                if ( $timer ) {

                    if ( !empty( $countdown_time ) && (int) $countdown_time > 0 && $show_timer == 'yes' ) {
                        wp_enqueue_script( $this->token . '-count' );
                        $JSujiPopups = array_merge( $JSujiPopups, array( 'is_count' => $countdown_time ) );
                        $add_wait = (!empty( $wait_time ) && (int) $wait_time > 0 ) ? (int) $wait_time : 0;
                        //$countdown = time() + $add_wait + ( int ) $countdown_time;
                        $countdown = $countdown_time;
                    }
                    if ( !empty( $tra_seconds ) && $show_timer == 'yes' ) {
                        $JSujiPopups = array_merge( $JSujiPopups, array( 'seconds' => $tra_seconds ) );
                    }
                    if ( !empty( $tra_minutes ) && $show_timer == 'yes' ) {
                        $JSujiPopups = array_merge( $JSujiPopups, array( 'minutes' => $tra_minutes ) );
                    }
                }
                if ( !$this->is_cached() ) {
                    $JSujiPopups = array_merge( $JSujiPopups, array( 'id_post' => $id_post, 'ajaxurl' => admin_url( 'admin-ajax.php' ) ) );
                }

                //Shortcode
                global $uji_short;
                $short = $this->get_interad( $ad_id, 'shortcode' );
                if ( isset( $uji_short ) && $uji_short != '' && $short ) {
                    $ad_id = $uji_short;
                    $JSujiPopups = array_merge( $JSujiPopups, array( 'is_short' => $uji_short ) );
                } else {
                    //add impression
                    $this->impression( $ad_id );
                }
                
                //Close button
                $close = $this->get_interad( $ad_id, 'close' );
                if ( $close ) {
                    $JSujiPopups = array_merge( $JSujiPopups, array( 'showclose' => 'true' ) );
                }
                
                //Class
                $class = $this->get_interad( $ad_id, 'class' );
                if ( $class != 'center' ) {
                    $JSujiPopups = array_merge( $JSujiPopups, array( 'location' => $class ) );
                }
                
                //add JS vars
                if ( !empty( $JSujiPopups ) ) {
                    wp_localize_script( $this->token . '-popups', 'ujiPopups', $JSujiPopups );
                }

                wp_enqueue_style( $this->token . '-modalcss' );
                wp_enqueue_style( $this->token . '-popups' );
                wp_enqueue_script( $this->token . '-modal' );
                wp_enqueue_script( $this->token . '-popups' );
            }

            $html_ad .= '<!-- Uji Popup Plugin -->
					
						<div id="popup" ' . $style_main . '>';


            if ( $timer && !empty( $show_timer ) && $show_timer == 'yes' )
                $html_ad .= '<div id="popups-bar" ' . $style_bar . '>
										<div id="popups-tit" class="popups">' . $this->get_interad( $ad_id, 'title' ) . '</div>
										<div id="popups-close">
					  						  <div id="inter-mess" ' . $bor_style . '>
												<span> ' . $tra_wait . ' </span>
												<span data-seconds="' . $countdown . '" class="popups-kkcount-down"></span>
												<span> ' . $tra_until . ' </span>
											  </div>	
										</div>
									</div>';


            $html_ad .= '<div id="popups-cnt" ' . $style_cont . '>
									' . $this->get_interad( $ad_id ) . '
								 </div>
							
						  </div><!-- Uji Popup Plugin by @wpmanage.com-->' . "\n";

            //Post AD content
            if ( !empty( $html_ad ) && !$this->is_cached() ) {
                echo $html_ad;
            } else if ( !empty( $html_ad ) && $this->is_cached() ) {
                return $html_ad;
            }
        }
    }

    /**
     * The shortcode
     *
     * @since    1.0
     */
    public function uji_popup_code( $atts, $content = null ) {
        global $uji_short;
        extract( shortcode_atts( array(
            'class' => '',
            'id' => ''
                        ), $atts ) );

        $uji_short = $id;
        $cls = ( $class && $class != "") ? ' ' . $class : '';
        return '<a href="#" class="uji-pop-link' . $cls . '">' . $content . '</a>';
    }

}

// End Class
?>
