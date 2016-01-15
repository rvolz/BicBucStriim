Feature: Add id template
	
	An admin user can add id templates in the admin/idtemplates page.
	An id template is defined by a unique label and a URL containing the parameter "%id%".

	Background: Navigating to the id templates page
		When I click on menu item "Home"
		And the app switches to the "Home" view
		And I click on menu item "Admin"
		And the app switches to the "Admin" view
		And I click on menu item "ID Templates"
		And the app switches to the "ID Templates" view

	Scenario: Add valid id template
		When I enter the id template data "ISBN search", "http://isbnsearch.org/isbn/%id%" for id "test1"
		Then I get the success message "Changes applied"
		And the id template "test1" contains label "ISBN search" and URL "http://isbnsearch.org/isbn/%id%"
	
	@wip	
	Scenario: Add invalid id template

