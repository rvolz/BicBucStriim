@initial
Feature: Admin configures after installation
	In order to configure the application just after installation
	As an admin user
	I get the admin page immediately and can enter the Calibre library directory

	Scenario: Invalid directory
		Given I just installed the application
		And I login as user "admin"
		And I get the installation page
		When I enter an invalid directory name
		Then I get an error message

	Scenario: Valid directory		
		Given I just installed the application
		And I login as user "admin"
		And I get the installation page
		When I enter a valid directory name 
		Then the application saves the configuration
		And I can navigate to the home page


