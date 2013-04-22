jQuery( function($){

	//Enable Tabs
	$('#cont_tab a:first').tab('show');
	    $('#cont_tab a').click(function (e) {
			e.preventDefault();
			$(this).tab('show');
			})
	
	//Show Custom 		
	var this_div = jQuery('#show_custom');		
	var ck = jQuery('#_see_show_cust');
	var all_ck = jQuery('#_see_show_cust, #_see_show_all, #_see_show_home');
	all_ck.click(function(){
		
		if(ck.is(':checked')){
			jQuery(this_div).fadeIn('fast');
		}else{
			jQuery(this_div).hide();
		}
	});	
	
	//data picker
	$('.datapick').datepicker();
	
	//Data clear
	$('.dclear').click(function(e){
		e.preventDefault();	
		$(this).parent('div').find('input').val('');	
	
	});
	
	
	//Color picker
	$('.colorpicker').colorpicker();
	
	$('.colorpicker').each(function(index) {
		var initc = $(this).find("input").val();
		$(this).find("i").css("background", initc);
	});
	
	//Color picker
	$('#show_timer').change(function() {
		
	 	init_settings();
	});
	
	init_settings();
	
});
	
//Select options:
	function init_settings(){
		var is_show = jQuery('#show_timer').val();
		var opt1 = jQuery('#countdown_time');
		var opt2 = jQuery('#wait_time');
	
		if(is_show == "yes"){
			opt1.parents('tr').show();
			opt2.parents('tr').show();
			
		}
		else if(is_show == "no"){
			opt1.parents('tr').hide();
			opt2.parents('tr').hide();
			
		}
	}

	