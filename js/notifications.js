(function( $ ){

  var methods = {
    init : function( ) { 
      // THIS 
    },
    error : function( content ) {
    	if($(this).parent().hasClass("ui-state-error")){
       		$(".ui-icon").next().fadeOut(0);
    		$(".ui-icon").after("<span>"+content+"</span>");
    	}
    	else
    	{ 
    		$(this).empty().attr("class","ui-state-error ui-corner-all").append("<span class='ui-icon ui-icon-alert' style='float: left; margin-right: .3em;'></span>"+content+"<span class='close_button'><img src='images/delete.png' width='20px' height='20px'></span>").show();
    		$(".close_button",this).click(function() {
  				$(this).parent().removeClass("ui-state-error").hide();
			});
		}
    },
    result : function( content ) {
    	if($(this).parent().hasClass("ui-state-highlight")){
    		$(".ui-icon").next().fadeOut(0);
    		$(".ui-icon").after("<span>"+content+"</span>");
    	}
    	else
    	{ 
 			$(this).empty().attr("class","ui-state-highlight ui-corner-all").append("<span class='ui-icon ui-icon-info' style='float: left; margin-right: .3em;'></span><span>"+content+"</span><span class='close_button'><img src='images/delete.png' width='20px' height='20px'></span>").show();
  	    	$(".close_button",this).click(function() {
  				$(this).parent().removeClass("ui-state-highlight").empty().hide();
			});
		}
    }
  };

  $.fn.showNotification = function( method ) {
    
    // Method calling logic
    if ( methods[method] ) {
      return methods[ method ].apply( this, Array.prototype.slice.call( arguments, 1 ));
    } else if ( typeof method === 'object' || ! method ) {
      return methods.init.apply( this, arguments );
    } else {
      $.error( 'Method ' +  method + ' does not exist on jQuery.showNotification' );
    }    
  
  };

})( jQuery );