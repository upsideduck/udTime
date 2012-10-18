$(document).ready(function() {
	var numItems = $('.monthlist_item').length;

	var hiddenElements = $('.monthlist_item:gt(4):lt('+(numItems-10)+')').hide();
	
	if (hiddenElements.size() > 0) {
      var showCaption = '...Show ' + hiddenElements.size() + ' more months';
      $('.monthlist_item:eq(4)').append(
          $('<div id="toggler">' + showCaption + '</div>')
              .toggle(
                  function() { 
                      hiddenElements.show();
                      $(this).text('...Show fewer months');
                  }, 
                  function() { 
                      hiddenElements.hide();
                      $(this).text(showCaption);
                  }
              )
      );
  }
	
});	


