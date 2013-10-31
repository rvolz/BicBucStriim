Feature: Delete user
	
	An admin user can delete existing users in the admin/users page.	
	The admin user is an exception, this account can't be deleted.

	Background: Navigating to the users page
		When I click on menu item "Home"
		And the app switches to the "Home" view
		And I click on menu item "Admin"
		And the app switches to the "Admin" view
		And I click on menu item "Users"
		And the app switches to the "Users" view

	Scenario: Delete normal user
		When I enter the user credentials "testuser7", "testuser_pw"
		And I get the success message "Changes applied"
		And the list contains user "testuser7"
		And I delete user "testuser7"
		And I confirm the deletion
		Then I get the success message "Changes applied"
		And the list doesn't contain user "testuser7"

	Scenario: Deleting the admin user is not posible
		When I enter the user credentials "testuser8", "testuser_pw"
		And I get the success message "Changes applied"
		And the list contains user "testuser8"
		And I delete user "admin"
		And I confirm the deletion
		Then I get the error message "Error while applying changes"
		And the list contains user "admin"
