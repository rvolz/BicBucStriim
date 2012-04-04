/* Author: Rainer Volz
*/

jQuery(function(){

	// Change the active link in the navbar
	var changeActive = function(target) {
		$("ul.nav li").removeClass("active")
		target.addClass("active");
	}
	if (document.URL.match(/titles/)) {
		changeActive($("#nav_titles"));
	} else if (document.URL.match(/authors/)) {
		changeActive($("#nav_authors"));
	}

});

