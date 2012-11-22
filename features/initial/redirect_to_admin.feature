@initial
Feature: User configures after installation
	In order to configure the application just after installation
	As a user
	I get the admin page immediately 

	Scenario: root redirects to admin page
		Given I just installed the application
		When I navigate to the root 
		Then I get redirected to the admin page
		
	Scenario: every other page also redirects to admin page
		Given I just installed the application
		When I navigate to the titleslist page 
		Then I get redirected to the admin page
