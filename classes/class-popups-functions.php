<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Uji_Popups_Functions {

	/**
	 * Check if trigger it
	 * @since  1.0
	 */
    protected function is_popups ( $id, $ajax = null ) {
		$args = array(
			 'post_type'     	 => 'popups',
			 'post_status' 		 => 'publish',
			 'order' 			 => 'DESC',
			 'orderby'			 => 'date',
			 'posts_per_page'	 => 1
			 
		);

		$queryin = new WP_Query( $args );
		$cicle = true;

		
		while ( $queryin->have_posts() && $cicle ):
				$queryin->the_post();
				$valid = true;

				//Selected
				$is_as_html =  get_post_meta( get_the_ID(), 'include_html', true );
				$is_as_url  =  get_post_meta( get_the_ID(), 'include_url', true );
				$is_as_post =  get_post_meta( get_the_ID(), 'add_posts', true );
				if( $valid && empty( $is_as_html ) && empty( $is_as_url ) &&  empty( $is_as_post ) ){
					$valid = false;
					
				}	
		
				//Where
                                $where = get_post_meta( get_the_ID(), 'where_show', true );

                                if ( $valid && $where == 'show_home' ) {
                                    if ( !is_front_page() && !isset($ajax['is_home']) ) {
                                        $valid = false;
                                    }elseif ( isset($ajax['is_home']) && !$ajax['is_home'] ) {
                                        $valid = false;
                                    }
                                }
				
				//CUSTOM PAGE			
				if( $valid && $where == 'show_cust' && !is_home() && !is_front_page() ){
					$pop_posts = get_post_meta( get_the_ID(), 'pop_posts', true );
					if(!empty($pop_posts)){
						$ids = explode( ",", $pop_posts );
						if( !in_array( $id, $ids ) ){
							$valid = false;
						}
					}
				}
				
				//NOT IN CUSTOM PAGE			
				if ( $valid && !is_front_page() ) {
					$pop_posts_not = get_post_meta( get_the_ID(), 'pop_posts_not', true );
					if(!empty($pop_posts_not)){
						$ids = explode( ",", $pop_posts_not );
						if( in_array( $id, $ids ) ){
							$valid = false;
						}
					}
				}
            
                                //SHORTCODE
                                if ( $valid && $where == 'show_short' ) {
                                    global $uji_short;
                                    if ( shortcode_exists( 'uji_popup' ) && !$uji_short && !$this->is_cached()) {
                                        $valid = false;
                                    }
                                }
			
				//CUSTOM PAGE NOT HOME
				if( $valid && $where == 'show_cust' && ( is_home() || is_front_page() ) ){
					$valid = false;
				}	
					
				//END RETURN
				if( $valid ){
					$cicle = false;
					return get_the_ID();
				}
			
		endwhile;
		wp_reset_query();
	}
	
	/**
	 * Add impression
	 * @since  1.0
	 */
	protected function impression ( $id ) {
		$num = get_post_meta( $id, 'pop_impressions', true );
		$num = (!empty($num)) ? (int) $num + 1 : 1;
		update_post_meta($id, 'pop_impressions', $num );
	}
	
	/**
	 * Get Option
	 * @since  1.0
	 */
	protected function int_option ( $name, $default = NULL ) {
		$val = get_option( $this->token );
		
		if( !empty( $val[$name] ) )
			return $val[$name];
		elseif( $default && !empty( $val[$name] ) )
			return $default;
		else
			return '';
	}
	
	/**
	 * Is Cache Plugin
	 * @since  1.0
	 */
	public function is_cached ( ) {
		$is = $this->int_option ( 'cache_in', 'no' );
		$chached = ($is == 'yes') ? true : false;
		return $chached;
	}
	
	/**
	 * Ad content with Cache Plugin
	 * @since  1.0
	 */
	public function inter_ajax_ads ( ) {
            $id = $_POST['id_post'];
            $ajax = ( isset($_POST['is_front']) && $_POST['is_front'] == 1 ) ? array( 'is_home' => true ) : NULL;

            $ad_id = $this->is_popups( $id, $ajax );
            $mess = $this->popup_ads( $id, $ajax );
            
            if( !empty( $mess ) && $ad_id ){
                    echo $mess;
            } else if( empty( $mess ) || !$ad_id ){
                    echo 'none_popups';
            }

            die();
	}
   
        /**
         * Add impression +
         * @since  1.0
         */
         public function inter_ajax_impress ( ) {
           $id = $_POST['id_ad'];
           $this->impression ( $id );
        }  
	
	
	/**
	 * Get Ad Contents
	 * @since  1.0
	 */
	protected function get_interad ( $id, $return = 'content' ) {
		
		switch ($return)
		{
		case 'title':
			  $show_it =  get_post_meta( $id, 'show_title', true );
			  if( $show_it == 'yes' ){
					$get_ad = get_post( $id );	
					return $get_ad->post_title;
			  }
		  break;
		case 'style':
                          $auto  = get_post_meta( $id, 'auto_size', true );
                          
                          if($auto != "yes"){
                            $width  = get_post_meta( $id, '_width1', true );
                            $width  = ( !empty( $width )  && is_numeric( $width )) ? $width : '450';
                            $style  = 'width: '.$width.'px;';
                            $height  = get_post_meta( $id, '_height1', true );
                            $height  = ( !empty( $width )  && is_numeric( $height )) ? $height : false;
                             if( $height ) $style .= 'height: '.$height.'px;';
                            return $style;
                          }else{
                             return 'overflow: visible;';
                          }
		  break;
		case 'style_cnt':
			  $top 		= get_post_meta( $id, 'pop_top', true );
			  $top		= ( !empty( $top ) && is_numeric( $top ) ) ? " ".$top."px" : " 0";
			  $right 	= get_post_meta( $id, 'pop_right', true );
			  $right	= ( !empty( $right ) && is_numeric( $right ) ) ? " ".$right."px" : " 0";
			  $bottom 	= get_post_meta( $id, 'pop_bottom', true );
			  $bottom	= ( !empty( $bottom ) && is_numeric( $bottom ) ) ? " ".$bottom."px" : " 0";
			  $left 	= get_post_meta( $id, 'pop_left', true );
			  $left		= ( !empty( $left ) && is_numeric( $left ) ) ? " ".$left."px" : " 0";

			  $style = 'padding: '.$top.$right.$bottom.$left.';';
                          
                          $auto  = get_post_meta( $id, 'auto_size', true );
                          if($auto == "yes")
                          $style .= 'overflow: hidden;';
           
			  return $style;
		  break;  
		case 'close':
			 $close  = get_post_meta( $id, 'add_close', true );
		  	 return ($close == "yes") ? true  : false;
		  break;
                case 'class':
                        $class = get_post_meta( $id, 'pop_location', true );
                        return (!empty($class)) ? $class : false;
                  break;
		case 'timer':
			 $timer  = get_post_meta( $id, 'show_count', true );
		  	 return ($timer == "yes") ? true  : false;
		  break;  
		case 'wait':
			 //$wtimer  = get_post_meta( $id, 'show_count', true );
                         $swait   = get_post_meta( $id, 'wait_time', true );
		  	 return ( $swait == 'yes' ) ? true  : false;
		  break;
                case 'shortcode':
			 $short  = get_post_meta( $id, 'where_show', true );
		  	 return ($short == "show_short") ? true  : false;
		  break;
		default:
		  	  return $this->get_content( $id );
		} 
		
	}
	
	/**
	 * Get Ad Contents
	 * @since  1.0
	 */
	private function get_content ( $id ) {
		
		$cnt_html =  get_post_meta( $id, 'include_html', true );
			//echo "x".$id;
			//is HTML
			if( $cnt_html ){
				$get_ad = get_post( $id );
				return  apply_filters('the_excerpt', do_shortcode( $get_ad->post_excerpt )); //do_shortcode( $get_ad->post_excerpt );
			}
			
		
	}
	
	
} // End Class
?>