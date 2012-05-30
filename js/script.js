/* Author: Rainer Volz
*/

jQuery(function(){
	$('.checkdl').live('click', function(){
		var pw = $('#password').val();
		$('.ui-dialog').dialog("close");
		$.post('/bbs/titles/160/checkaccess/', {pw: pw});
		$mobile.changePage('/titles/160/')
		return false;
	});

	if ('.title_error') {
		var fl = $.cookie('flash');
		$('title_error').text(fl);
	}
	if ($('.dl_access')) {
		if ($.cookie('glob_dl_access')) {
			$('.dl_access').hide();
			$('.dl_download').show();
		} else {
			$('.dl_access').show();
			$('.dl_download').hide();
		}
	}
});

