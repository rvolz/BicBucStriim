@initial
Feature: User configures after installation
	In order to configure the application just after installation
	As an admin user
	I get the admin page immediately 

	Scenario: root redirects to admin page
		Given I just installed the application
		And I login as user "admin"
		Then I get redirected to the admin page
		
	Scenario: every other page also redirects to admin page
		Given I just installed the application
		And I login as user "admin"
		And I click the menu link "Books"
		Then I get redirected to the admin page
