
// Admin ID templates management 
$(document).on('pageinit', '#padmin_idtemplates', function() {

	// Initiate the template handling via click
	$('.template').on('click', function(){
		var name = $(this).find('h2 span.template-name').text();
		var label = $(this).find('h2 span.template-label').text();
		var url = $(this).find('p.template-uri').text();
		$('#edit').popup();	
		$('#edit #name').val(name);
		$('#edit #label').val(label);
		$('#edit #template').val(url);
		$('#edit').popup('open');		
		return false;
	});


	// Initiate the editing of a template via click
	$('#templateform').on('submit', function(event) {
		event.preventDefault();
		var template = {
			name: $('#name').val(),
			label: $('#label').val(),
			url: $('#template').val()
		};
		var root = $(this).data('proot');
		var jh = $.ajax({
			url: root+'/admin/idtemplates/'+template.name,
			type: 'PUT',
			async: false,
			data: template,
			success: function(data) {
				$('#edit').popup('close');
				var tmpl = $('ul#idtemplates li a.template[data-template|='+data.template.name+']');
				$(tmpl).find('p.template-uri').text(data.template.val);
				$(tmpl).find('span.template-label').text(data.template.label);
				$('ul#idtemplates').listview('refresh');
				$('div#flash').empty().append('<p class="success">'+data.msg+'</p>');
				$('#padmin_idtemplates').trigger('change');
			},
			error: function(jqXHR, responseText, errorThrown) {
				$('#edit').popup('close');
				$('div#flash').empty().append('<p class="error">'+responseText+'</p>');
				$('#padmin_idtemplates').trigger('change');		
			}
		});		
		return false;
	});

	// Initiate the delete user handling via click
	$('.idtemplate_clear').on('click', function(){
		var template = $(this).data('template');
		var root = $(this).data('proot');
		var jh = $.ajax({
			url: root+'/admin/idtemplates/'+template,
			type: 'DELETE',
			async: false,
			success: function(data) {
				var tmpl = $('ul#idtemplates li a.template[data-template|='+template+']');
				$(tmpl).find('p.template-uri').text('');
				$(tmpl).find('span.template-label').text('');
				$('ul#idtemplates').listview('refresh');
				$('div#flash').empty().append('<p class="success">'+data.msg+'</p>');
				$('#padmin_idtemplates').trigger('change');
			},
			error: function(jqXHR, responseText, errorThrown) {
				$('div#flash').empty().append('<p class="error">'+jqXHR.responseText+'</p>');
				$('#padmin_idtemplates').trigger('change');		
			}
		});
		return false;
	});

});


// Admin user management - user list
$(document).on('pageinit', '#padmin_users', function() {

	// Initiate the additon of a user handling via click
	$('#newuserform').on('submit', function(event) {
		event.preventDefault();
		var user = {
			username: $('#username').val(),
			password: $('#password').val()
		};
		var root = $(this).data('proot');
		$.ajaxSetup({async:false});
		$.post(root+'/admin/users/', user, function(data, textStatus, jqXHR) {
			var user = data.user;
			var userLi = document.createElement('li'),
				userA1 = document.createElement('a'),
				userA1H2 = document.createElement('h2'),
				userA2 = document.createElement('a');
			userLi.dataset.user = user.id;
			$(userA1).attr('href', root+'/admin/users/'+user.id);
			$(userA1H2).text(user.username);
			$(userA2).addClass('user_delete');
			$(userA2).on('click', function(){
				startUserDeletion(userA2);
			});
			$(userA2).attr('href', '#');
			userA2.dataset.user = user.id;
			$(userA2).attr('title', $('#users li:first a.user_delete').attr('title'));
			$(userLi).append(userA1);
			$(userLi).append(userA2);
			$(userA1).append(userA1H2);
			$(userLi).prependTo('#users');
			$('#users').listview('refresh');
			$('div#flash').empty().append('<p class="success">'+data.msg+'</p>');
			$('#padmin_user').trigger('change');
		})
		.fail(function(data) {
			$('div#flash').empty().append('<p class="error">'+data.msg+'</p>');
			$('#padmin_user').trigger('change');		
		});
		$.ajaxSetup({async:true});
		return false;
	});

	function startUserDeletion(that) {
		var user = $(that).data('user');
		deleteUser(user);
	}

	// Initiate the delete user handling via click
	$('.user_delete').on('click', function(){
		startUserDeletion(this);
		return false;
	});

	// Initiate the delete user handling via swiping
	$(document).on( "swipeleft swiperight", "#users li", function( event ) {
       	startUserDeletion(this);        
    });
    
	// Common delete handling, add the user id to the popoup and open it
	function deleteUser(id) {
		$('#delete_user').data('user',id);	
		$('#delete').popup();	
		$('#delete').popup('open');	
	}
	
	// Finish the delete and close the popup
	$('#delete_user').on('click', function(){
		var user = $(this).data('user');
		var root = $(this).data('proot');
		var jh = $.ajax({
			url: root+'/admin/users/'+user,
			type: 'DELETE',
			async: false,
			success: function(data) {
				$('#users li[data-user|='+user+']').remove();						
				$('div#flash').empty().append('<p class="success">'+data.msg+'</p>');
				$('#padmin_user').trigger('change');
			},
			error: function(jqXHR, responseText, errorThrown) {
				$('div#flash').empty().append('<p class="error">'+jqXHR.responseText+'</p>');
				$('#padmin_user').trigger('change');		
			}
		});
		$('#delete').popup('close');
		$('#users').listview('refresh');
		return false;
	});
});

// Admin user management - single user
$(document).on('pageinit', '#padmin_user', function() {
	// Initiate the modification of a user handling via click
	$('#userform').on('submit', function(event) {
		event.preventDefault();
		var user = {
			username: $('#username').val(),
			password: $('#password').val(),
			languages: $('#languages').val(),
			tags: $('#tags').val()
		};
		var root = $(this).data('proot'),
			id = $(this).data('user');
		var jh = $.ajax({
			url: root+'/admin/users/'+id,
			type: 'PUT',
			async: false,
			data: user,
			success: function(data) {
				var user = data.user;
				$('#username').val(user.username).trigger('change');
				$('#password').val(user.password).trigger('change');
				$('#languages').val(user.languages).trigger('change');
				$('#tags').val(user.tags).trigger('change');
				$('[type=\'submit\']').removeClass('ui-btn-active').trigger('change');
				$('div#flash').empty().append('<p class="success">'+data.msg+'</p>');
				$('#padmin_user').trigger('change');
			},
			error: function() {
				$('div#flash').empty().append('<p class="error">'+data.msg+'</p>');
				$('#padmin_user').trigger('change');
			}
		});
		return false;	
	});
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

	$('#admin_check_version').on('click', function(){
		$.ajaxSetup({async:false});
		//var root = $('.checkdl').data('proot');		
		var jh = $.getJSON("version/")
		.success(function(data) {
			$('#flash').append(data.msg);
		});		
		$.ajaxSetup({async:true});
		return false;
	});

});

/* Send-to-Kindle Ajax function 
Target-Url: /titles/:id/kindle/:file via POST
request-data: email
*/

$(document).on('pageinit', function() {

	var buttontext = "";
	$("#ajax-form").submit(function(e) {
	//$('#ajax-form').on('submit',function(e){
	  //e.preventDefault();	 
	  buttontext = $("#ajax-form-button").attr("value");
	  var kindleEmail = $.trim($("#kindleEmail").val());
	  $("#ajax-form-button").button('disable');
	  $.mobile.showPageLoadingMsg();
	  $.ajax({
	    type: "POST",
	    url: $("#ajax-form").attr("action"),
	    data: ({email: kindleEmail}),
	    cache: false,
	    dataType: "text",
	    success: onSuccess,
	  });
	  return false;
	});

	$("#ajax-message").ajaxError(function(event, request, settings, exception) {
	$.mobile.hidePageLoadingMsg();
	//$("#ajax-message").text("Error! HTTP Code: " + request.status);
	$("#ajax-message").find('span#msg').fadeIn();
	setTimeout(function() { 
	  $("#ajax-form-button").button('enable');
	  $("#ajax-message").find('span#msg').fadeOut(); 
	}, 2000);
	});

	function onSuccess(data)
	{
	  data = $.trim(data);
	  $.mobile.hidePageLoadingMsg();
	  $( "#kindlePopup" ).popup( "close" )
	  $("#kindleButton").attr('value',data);
	  $("#kindleButton").buttonMarkup({ icon: "check" });
	  $("#kindleButton").button('refresh');
	  $("#kindleButton").button('disable');
	  $(".downButtons").controlgroup('refresh', true);
	}

	/* If cookie 'kindleEmail' is found, pre-populate form input*/
	if ($.cookie('kindle_email')) {
		$("#kindleEmail").attr('value',$.cookie('kindle_email'));
	}

});

$(document).on('vclick', '#kindleButton', function(){
  $('#kindlePopup').popup('open', { positionTo: 'window', transition: 'pop' } );
});

