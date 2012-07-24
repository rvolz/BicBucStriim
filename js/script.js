/* Author: Rainer Volz
*/

/** Install some change handlers on the admin page for the ui logic. */
$('#padmin').on('pageinit', function(e) {
		$('#glob_dl_choice_1, #glob_dl_choice_2').on('change', function(e) {
		if ($(this).attr('checked') == "checked") {
			$('#glob_dl_password').textinput('disable');
		} else {
			$('#glob_dl_password').textinput('enable');
		}
	});
	$('#glob_dl_choice_3').on('change', function(e) {
		if ($(this).attr('checked') == "checked") {
			$('#glob_dl_password').textinput('enable');
		} else {
			$('#glob_dl_password').textinput('disable');
		}
	});
	/* Submit handling for the admin page  */
	$('#adminform').submit(function() {
		var url= $(this).attr('action');
		$.post(url, $(this).serializeArray(), function(data,status,jqXHR) {
			$('div#flash').empty().append(data);
		});
		return false;
	});
		/* Submit handling for admin password check */
	$('#adminpwform').submit(function() {
		var url= $(this).attr('action');
		$.post(url, $(this).serializeArray(), function(data, status, jqXHR) {
			if (data.access == true) {
				$('#adminform').show();
				$('#adminpwform').hide();			
			} else {
				$('div#flash').empty().append('<p class="error">'+data.message+'</p>');	
			}
		});
		return false;
	});

});

$('#padmin').on('pageshow', function(e) {
	$.get($('#adminform').attr('action')+'access/', function(data) {
		if (data === '0' || $.cookie('admin_access')) {
			$('#adminpwform').hide();
			$('#adminform').show();
		} else {
			$('#adminpwform').show();
			$('#adminform').hide();			
		}
	});
});

$('#padmin').on('pagehide', function(e) {
	/* Delete the flash messages when the page is done */
	$('div#flash').empty();
});

$(document).on('pageinit', function() {

	/* Click handler for the password dialog.
	When submit is clicked the password is checked synchronously.
	If successful the server set a cookie, so we can simly close 
	the dialog and refresh the parent page. Otherwise we stay in
	the dialog an display an error message.	*/
	$('.checkdl').on('click', function(){
		$.ajaxSetup({async:false});
		var root = $('.checkdl').data('proot');
		var book = $('.checkdl').data('bookid');
		var pw = $('#password').val();
		var jh = $.post(root+"/titles/"+book+"/checkaccess/",{password:pw})
		.success(function() {
			$('.ui-dialog').dialog("close");
			$('.dl_access').hide();
			$('.dl_download').show();
		})
		.error(function() {
			$('#pw_dlg_status').show();
		});
		$.ajaxSetup({async:true});
		return false;
	});

	/* The status field in the password dialog is alread hidden, but 
	Kindle ignores that. So we hide it again.*/
	if ($('#passwordform')) {		
		$('#pw_dlg_status').hide();
	}

	/* Show the correct state of the download area in the title
	 details page. Only if we have a download cookie the download 
	 options should be visible */
	if ($('.dl_access').size() > 0) {
		if ($.cookie('glob_dl_access')) {
			$('.dl_access').hide();
			$('.dl_download').show();
		} else {
			$('.dl_access').show();
			$('.dl_download').hide();
		}
	}

});
