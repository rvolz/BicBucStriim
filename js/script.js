/* Author: Rainer Volz
*/

jQuery(function(){

	/* Click handler for the password dialog.
	When submit is clicked the password is checked synchronously.
	If successful the server set a cookie, so we can simly close 
	the dialog and refresh the parent page. Otherwise we stay in
	the dialog an display an error message.	*/
	$('.checkdl').live('click', function(){
		$.ajaxSetup({async:false});
		var pw = $('#password').val();
		var jh = $.post("/bbs/titles/272/checkaccess/",{password:pw})
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

