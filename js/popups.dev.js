jQuery( function($){
   if(typeof(ujiPopups) !== 'undefined' && ujiPopups != null){
       var is_link = ujiPopups.is_short;
       var exit_intent = ujiPopups.exit_intent;
       var exit_once = true;
         if(!is_link){
          //Exit only   
          if( exit_intent && exit_once){   
            document.addEventListener("mousemove", function(e) {
                        // Get current scroll position
			var scroll = window.pageYOffset || document.documentElement.scrollTop;
			if((e.pageY - scroll) < 7 && exit_once){
				show_uji_popup();
                                exit_once = false;
                            }
		}); 
            //Any time    
            }else{
                show_uji_popup();
            }     
            
         } 
   }
   
   $( ".uji-pop-link" ).on( "click", function( event ) {
          event.preventDefault();
           if(is_link){   
               show_uji_popup();
               jQuery.ajax({
                     type : "post",
                     dataType : "html",
                     cache: false,
                     url : ujiPopups.ajaxurl,
                     data : {action: 'inter_pop_impress', id_ad : is_link },
                     
                   });
            }
 
          return false;
      });
});

function show_uji_popup(){
	if(typeof(ujiPopups) !== 'undefined' && ujiPopups != null){
		//WAIT TIME
		var is_wait = ujiPopups.is_wait;
		var is_cached = ujiPopups.is_cached;
		if(is_wait){
			setTimeout(
			  function() 
			  {
				open_lightbox();
				popups_count();
			  }, (is_wait*1000));
		}
		if(!is_wait && !is_cached){
					open_lightbox();
					popups_count();
		}
		
		//CACHED AD
		var is_cached = ujiPopups.is_cached;
		if(is_cached){
		
			jQuery.ajax({
				 type : "post",
				 dataType : "html",
				 cache: false,
				 url : ujiPopups.ajaxurl,
				 data : {action: 'inter_pop_action', id_post : ujiPopups.id_post, is_front : ujiPopups.is_front },
				 success: function(response) {
					if(response.type != "" && response != "none_popups") {
					   jQuery('body').append(response);
					   if(!is_wait){
 						    open_lightbox();
					   		popups_count();
					   }
					}
					
				 }
			  });
		}
	}
}

function popups_count(){
	var min_txt = (typeof(ujiPopups) !== 'undefined' && ujiPopups != null && ujiPopups.minutes) ? ujiPopups.minutes : 'min';
	var sec_txt = (typeof(ujiPopups) !== 'undefined' && ujiPopups != null && ujiPopups.seconds) ? ujiPopups.seconds : 'sec';
  
	if(typeof(ujiPopups.is_count) !== 'undefined' && ujiPopups.is_count != null && ujiPopups.is_count > 0){
 
      count = jQuery('.popups-kkcount-down').data('seconds');
      
      jQuery('.popups-kkcount-down').countdownpopup({
          date: +(new Date) + 1000*count,
          render: function(data) {
            if( data.min > 0)
               jQuery(this.el).html("<div>" + this.leadingZeros(data.min, 2) + " <span> "+ min_txt +" : </span></div><div>" + this.leadingZeros(data.sec, 2) + " <span>"+ sec_txt +"</span></div>");
            else
               jQuery(this.el).html("<div>" + this.leadingZeros(data.sec, 2) + " <span>"+ sec_txt +"</span></div>");
               
          },
          onEnd: function() {
            popups_close();
          }
        });
    
   }
}

//Open lightbox
function open_lightbox(){
        var isClose = ( ujiPopups.showclose ) ? true : false;
        var outClose = ( ujiPopups.closeout ) ? true : false;
        
        var fadeSpeed = ( ujiPopups.exit_intent ) ? 0 : 500;
        var fadeWait = ( ujiPopups.exit_intent ) ? 0 : 0.50;
    
	jQuery("#popup").modaluji({
            closeText: '×',
            fadeDuration: fadeSpeed,
            fadeDelay: fadeWait,
            zIndex: 99999,
            showClose: isClose,
            escapeClose: false,
            clickClose: outClose
        });
        //Location
        if(ujiPopups.location)
            jQuery("#popup").addClass(ujiPopups.location);
}

//Close Ad
function popups_close( ){
   jQuery.modaluji.close();
   var typ     = (typeof(ujiPopups) !== 'undefined' && ujiPopups != null && ujiPopups.is_short)? false : true;
   if( typ == true ){
        jQuery("#popup").remove();
   }
}