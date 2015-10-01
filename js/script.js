/**
 * BicBucStriim
 *
 * Copyright 2012-2015 Rainer Volz
 * Licensed under MIT License, see LICENSE
 *
 */

// Clear flash when showing page
$(document).on('pageload', '#pauthor_detail', function () {
    $('div#flash').empty();
});

// Author metadata on author details page
$(document).on('pageinit', '#pauthor_detail', function() {

	// Close the popup menu before the panel opens
	// It wants to stay open.
	$('#author-mdthumb-panel').on('panelbeforeopen', function( event, ui ) {
		$('#popupMenu').popup('close');
	});
	$('#author-mdlinks-panel').on('panelbeforeopen', function( event, ui ) {
		$('#popupMenu').popup('close');
	});

    $('div#flash').empty();

	// Delete author image
	$('#delete-image').on('vclick', function() {
		var root = $(this).data('proot');
		var author = $(this).data('author');
		var jh = $.ajax({
			url: root+'/metadata/authors/'+author+'/thumbnail/',
			type: 'DELETE',
			success: function(data) {
				$('img#author-thumbnail-pic').remove();
				$('div#flash').empty().append('<p class="success">'+data.msg+'</p>');
				$('#pauthor_detail').trigger('change');
			},
			error: function(jqXHR, responseText, errorThrown) {
				$('div#flash').empty().append('<p class="error">'+jqXHR.responseText+'</p>');
				$('#pauthor_detail').trigger('change');		
			}
		});
		return false;
	});

	// Add a new author link
	$('form#author-link-new').on('submit', function(event){
		event.preventDefault();		
		var url = $(this).attr('action');
		var link = {
			label: $(this).find('#link-description').val(),
			url: $(this).find('#link-url').val()
		};
		$.post(url, link, function(data, textStatus, jqXHR) {
			var litmp = {
				class1: 'author-link link-'+data.link.id,
				class2: 'link-'+data.link.id,
				id: data.link.id,
				url: data.link.url,
				label: data.link.label	
			}
			// Add links via templates
			$('#author-links-list').loadTemplate('#linkTemplate1', litmp, {prepend: true});
			$('#author-links-edit').loadTemplate('#linkTemplate2', litmp, {prepend: true});			
			// Bind the delete handler, it is not installed by the refresh?
			$('a.author-link-delete').on('vclick', function(event) {
				deleteLink(event, this);
			});
			$('ul#author-links-list').listview('refresh');
			$('ul#author-links-edit').listview('refresh');
			$('div#flash').empty().append('<p class="success">'+data.msg+'</p>');
			$('#link-description').val('').focus();
			$('#link-url').val('');
			$('#pauthor_detail').trigger('change');
			$('#author-mdlinks-panel').trigger('updatelayout');
		})
		.fail(function (jqXHR) {
			$('div#flash').empty().append('<p class="error">'+jqXHR.responseText+'</p>');
			$('#pauthor_detail').trigger('change');		
		});
		return false;
	});


	// Delete an author link
	$('.author-link-delete').on('vclick', function(event) {
		deleteLink(event, this);
		return false;	
	});

	// Delete an author link
	function deleteLink (event, that) {
		var root = $(that).data('proot');
		var author = $(that).data('author');
		var link = $(that).data('link');
		var jh = $.ajax({
			url: root+'/metadata/authors/'+author+'/links/'+link+'/',
			type: 'DELETE',
			success: function(data) {
				$('li.link-'+link).remove();
				$('ul#author-links-list').listview('refresh');
				$('ul#author-links-edit').listview('refresh');				
				$('div#flash').empty().append('<p class="success">'+data.msg+'</p>');
				$('#author-mdlinks-panel').trigger('updatelayout');
				$('#pauthor_detail').trigger('change');
			},
			error: function(jqXHR, responseText, errorThrown) {
				$('div#flash').empty().append('<p class="error">'+jqXHR.responseText+'</p>');
				$('#pauthor_detail').trigger('change');		
			}
		});
	}

});

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
				$('div#flash').empty().append('<p class="error">'+jqXHR.responseText+'</p>');
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

	// Clear the input fields - otherwise the login data might be re-used
	$('#newuser_name').val('');
	$('#newuser_password').val('');
	
	// Clear the flash area 
	$('div#flash').empty();

	// Initiate the additon of a user handling via click
	$('#newuserform').on('submit', function(event) {
		event.preventDefault();
		$('div#flash').empty();
		var user = {
			username: $('#newuser_name').val(),
			password: $('#newuser_password').val()
		};
		var root = $(this).data('proot');
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

			// Clear the input fields again for a new user
			$('#newuser_name').val('');
			$('#newuser_password').val('');
			
			$('#padmin_user').trigger('change');
		})
		.fail(function(jqXHR) {
			$('div#flash').empty().append('<p class="error">'+jqXHR.responseText+'</p>');
			$('#padmin_user').trigger('change');		
		});
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
	$('div#flash').empty();

	// Initiate the modification of a user handling via click
	$('#userform').on('submit', function(event) {
		event.preventDefault();
		$('div#flash').empty();
		var user = {
			username: $('#edituser_name').val(),
			password: $('#edituser_password').val(),
			languages: $('#edituser_languages').val(),
			tags: $('#edituser_tags').val(),
			role: $('#edituser_role').val()
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
				$('#edituser_name').val(user.username).trigger('change');
				$('#edituser_password').val(user.password).trigger('change');
				$('#edituserlanguages').val(user.languages).trigger('change');
				$('#editusertags').val(user.tags).trigger('change');
				$('[type=\'submit\']').removeClass('ui-btn-active').trigger('change');
				$('div#flash').empty().append('<p class="success">'+data.msg+'</p>');
				$('#padmin_user').trigger('change');
			},
			error: function(jqXHR) {
				$('div#flash').empty().append('<p class="error">'+jqXHR.responseText+'</p>');
				$('#padmin_user').trigger('change');
			}
		});
		return false;	
	});
});


// Search button for content pages
$(document).on('vclick', '#enableSearchButton', function () {
    $('#generalSearchForm').show();
});

$(document).on('pageinit', '#ptitles', function () {
    $('#generalSearchForm').hide();
});

/* Send-to-Kindle Ajax function 
Target-Url: /titles/:id/kindle/:file via POST
request-data: email
*/

$(document).on('pageinit', '#ptitle_detail' ,function() {

	var buttontext = "";
	//$("#ajax-form").submit(function(e) {
	$('#ajax-form').on('submit',function(e){
	  e.preventDefault();	 
	  buttontext = $("#ajax-form-button").attr("value");
	  var kindleEmail = $.trim($("#kindleEmail").val());
	  $("#ajax-form-button").button('disable');
	  $.mobile.loading('show');
	  $.ajax({
	    type: "POST",
	    url: $("#ajax-form").attr("action"),
	    data: ({email: kindleEmail}),
	    cache: false,
	    dataType: "text",
	    success: onSuccess,
	    error: onErr
	  });
	  return false;
	});

	function onErr(jqXHR, responseText, errorThrown) {
		$.mobile.loading('hide');
		//$("#ajax-message").text("Error! HTTP Code: " + jqXHR.status);
		$("#ajax-message").find('span#msg').fadeIn();
		setTimeout(function() { 
		  $("#ajax-form-button").button('enable');
		  $("#ajax-message").find('span#msg').fadeOut(); 
		}, 2000);
	};

	function onSuccess(data) {
	  data = $.trim(data);
	  $.mobile.loading('hide');
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

