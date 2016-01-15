Feature: Delete id template
	
	An admin user can delete defined id templates in the admin/idtemplates‚ page.
	An id template is defined by a unique label and a URL containing the parameter "%id%".

	Background: Navigating to the id templates page
		When I click on menu item "Home"
		And the app switches to the "Home" view
		And I click on menu item "Admin"
		And the app switches to the "Admin" view
		And I click on menu item "ID Templates"
		And the app switches to the "ID Templates" view

	Scenario: Delete id template
		When I enter the id template data "ISBN search", "http://isbnsearch.org/isbn/%id%" for id "test1"
		And I get the success message "Changes applied"
		And the id template "test1" contains label "ISBN search" and URL "http://isbnsearch.org/isbn/%id%"
		And I delete id template "test1"
		Then I get the success message "Changes applied"
	
