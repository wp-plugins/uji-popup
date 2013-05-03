jQuery( function($){
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

			$.ajax({
				 type : "post",
				 dataType : "html",
				 cache: false,
				 url : ujiPopups.ajaxurl,
				 data : {action: 'inter_pop_action', id_post : ujiPopups.id_post },
				 success: function(response) {
					if(response.type != "" && response != "none_popups") {
					   $('body').append(response);
					   if(!is_wait){
						    open_lightbox();
					   		popups_count();
					   }
					}
					
				 }
			  });
		}
	}
});

function popups_count(){
	var min_txt = (typeof(ujiPopups) !== 'undefined' && ujiPopups != null && ujiPopups.minutes) ? ujiPopups.minutes : 'min';
	var sec_txt = (typeof(ujiPopups) !== 'undefined' && ujiPopups != null && ujiPopups.seconds) ? ujiPopups.seconds : 'sec';

	if(typeof(ujiPopups.is_count) !== 'undefined' && ujiPopups.is_count != null && ujiPopups.is_count > 0){
		jQuery(".popups-kkcount-down").kkcountdown({
						minutesText	:  ' '+min_txt + ' : ',
						secondsText	:  ' '+sec_txt,
						displayZeroDays : false,
						callback	: popups_close
					});					
	}
}

//Open lightbox
function open_lightbox(){
		//var closebut = (typeof(ujiPopups) !== 'undefined' && ujiPopups != null && ujiPopups.showclose && ujiPopups.showclose == "true") ? true : false;
			jQuery("#popup").modal({onOpen: function (dialog) {
					dialog.overlay.fadeIn('fast');
					dialog.data.hide();
					dialog.container.show('fast', function () {
							dialog.data.fadeIn('slow');	 
						});	
					},
					autoResize: false,
					autoPosition: true,
					escClose: false,
					zIndex: 999999,
					overlayClose: false
				});
				

				
				
				
			
}

//Close Ad
function popups_close(){
  	jQuery.modal.close();
	jQuery("#popup").remove();
}



