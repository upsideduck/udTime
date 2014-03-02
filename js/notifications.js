(function( $ ){

  var methods = {
    init : function( ) { 
      // THIS 
    },
    error : function( content ) {
    	/*if($(this).parent().hasClass("ui-state-error")){
       		$(".ui-icon").next().fadeOut(0);
    		$(".ui-icon").after("<span>"+content+"</span>");
    	}
    	else
    	{ */
    	    var messagePart = "<ul class='notificationList'>";
    		$.each(content, function(index, value) {
      	 		messagePart = messagePart + "<li>" + value + "</li>";
      	 		
  			});
  			messagePart += "</ul>";
    		$(this).append('<div class="alert alert-error"><button type="button" class="close" data-dismiss="alert">&times;</button>'+messagePart+'</div>');
		//}
    },
    result : function( content ) {
    	/*if($(this).parent().hasClass("ui-state-highlight")){
    		$(".ui-icon").next().fadeOut(0);
    		$(".ui-icon").after("<span>"+content+"</span>");
    	}
    	else
    	{ */
    	    var messagePart = "<ul class='notificationList'>";
    		$.each(content, function(index, value) {
      	 		messagePart = messagePart + "<li>" + value + "</li>";
      	 		
  			});
  			messagePart += "</ul>";
    		$(this).append('<div class="alert alert-success"><button type="button" class="close" data-dismiss="alert">&times;</button>'+messagePart+'</div>');
		//}
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