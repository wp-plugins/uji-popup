jQuery( function($){

    if($('#cont_tab').length){
        //Enable Tabs
        $('#cont_tab a:first').tab('show');
            $('#cont_tab a').click(function (e) {
                e.preventDefault();
                $(this).tab('show');
                })

        //Click sub 		
       $(".radio_sub").each(function(){
          var id = $(this).attr("id");
       
            if($(this).is(':checked')){
                $("#"+id+"_sub").fadeIn('fast');
            }else{
                $("#"+id+"_sub").hide();
            }
       });
       
       $( ".radio" ).on( "click", function() {
          var id = $(this).attr("id");
       
            if($(this).is(':checked')){
                $('.options_sub').hide();
                $("#"+id+"_sub").fadeIn('fast');
            }
       });
       
       //shortcode class
       $( ".uji_class").html( $( "#pop_class" ).val());
        $( "#pop_class" ).on( "input", function() {
           var the_class = $(this).val();
           $( ".uji_class").html(the_class);
       });
       

        //data picker
        $('.datapick').datepicker();

        //Data clear
        $('.dclear').click(function(e){
            e.preventDefault();	
            $(this).parent('div').find('input').val('');	

        });
        
    }    
	
	
	//Color picker
    if (typeof colorpicker == 'function') { 
    
        $('.colorpicker').colorpicker();

        $('.colorpicker').each(function(index) {
            var initc = $(this).find("input").val();
            $(this).find("i").css("background", initc);
        });
        
    }else{
                
        $('.wpcolorpicker').wpColorPicker();
    }
    
    
	
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

	