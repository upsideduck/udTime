$(document).ready(function() {
	var numItems = $('.weeklist_item').length -1;

	var hiddenElements = $('.weeklist_item:gt(5):lt('+(numItems-10)+')').hide();
	
	if (hiddenElements.size() > 0) {
      var showCaption = '...' + hiddenElements.size() + ' More weeks';
      $('.weeklist_item:eq(5)').append(
          $('<div id="toggler">' + showCaption + '</div>')
              .toggle(
                  function() { 
                      hiddenElements.show();
                      $(this).text('...Fewer Choices');
                  }, 
                  function() { 
                      hiddenElements.hide();
                      $(this).text(showCaption);
                  }
              )
      );
  }
	
});	


