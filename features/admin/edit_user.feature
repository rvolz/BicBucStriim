Feature: Edit user
	
	An admin user can edit existing users in the admin/users detail page.
	Everything except the user name can be changed.

	Background: Navigating to the users page
		When I click on menu item "Home"
		And the app switches to the "Home" view
		And I click on menu item "Admin"
		And the app switches to the "Admin" view
		And I click on menu item "Users"
		And the app switches to the "Users" view

	Scenario: Change password
		When I enter the user credentials "testuser9", "testuser_pw"
		And the list contains user "testuser9"
		And I edit user "testuser9"
		And I change the password to "testuser_other_pw"
		Then I get the success message "Changes applied"

	Scenario: Empty passwords cannot be used
		When I enter the user credentials "testuser9", "testuser_pw"
		And the list contains user "testuser9"
		And I edit user "testuser9"
		And I change the password to ""
		Then I get the error message "Error while applying changes"

	Scenario: Change the language filter
		When I enter the user credentials "testuser10", "testuser_pw"
		And the list contains user "testuser10"
		And I edit user "testuser10"
		And I change the language filter to "deu"
		Then I get the success message "Changes applied"

	Scenario: Change the tags filter
		When I enter the user credentials "testuser11", "testuser_pw"
		And the list contains user "testuser11"
		And I edit user "testuser11"
		And I change the tag filter to "Italy"
		Then I get the success message "Changes applied"
