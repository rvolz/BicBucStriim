Feature: List defined users
	
	The user administration page lists all defined users and
	provides a form to add new ones.

	Defined users can be edited or deleted by clicking on the 
	respective links.

	Scenario: Initially there is just the *admin* user
		When I click on menu item "Home"
		And the app switches to the "Home" view
		And I click on menu item "Admin"
		And the app switches to the "Admin" view
		And I click on menu item "Users"
		And the app switches to the "Users" view
		Then the list contains 1 item
		