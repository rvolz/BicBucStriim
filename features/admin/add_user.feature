Feature: Add user
	
	An admin user can add new users in the admin/users page.
	A new user is defined by a unique name and a non-empty password.

	Background: Navigating to the users page
		When I click on menu item "Home"
		And the app switches to the "Home" view
		And I click on menu item "Admin"
		And the app switches to the "Admin" view
		And I click on menu item "Users"
		And the app switches to the "Users" view

	Scenario: Add valid user
		When I enter the user credentials "testuser", "testuser_pw"
		Then I get the success message "Changes applied"
		And the list contains 2 items
		And the list contains user "testuser"

	Scenario: Add valid user with unicode in user name
		When I enter the user credentials "testüser", "testuser_pw"
		Then I get the success message "Changes applied"
		And the list contains 2 items
		And the list contains user "testüser"

	Scenario: Add user with existing user name
		When I enter the user credentials "testuser2", "testuser2_pw"
		And I get the success message "Changes applied"
		And I enter the user credentials "testuser2", "testuser3_pw"
		Then I get the success message "Error while applying changes"

	Scenario: Add user with empty user name
		When I enter the user credentials "", "testuser2_pw"
		Then I get the error message "Error while applying changes"

	Scenario: Add user with empty password
		When I enter the user credentials "testuser3", ""
		Then I get the error message "Error while applying changes"

	Scenario: Add user with empty user credentials
		When I enter the user credentials "", ""
		Then I get the error message "Error while applying changes"
	