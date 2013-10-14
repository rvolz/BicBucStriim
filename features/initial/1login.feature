@initial
Feature: Access only with valid login data
	In order to access the application
	As a user 
	I need valid login credentials

	Scenario: Login page
		Given I just installed the application
		Then I get the login page
	
	Scenario: Login page everywhere
		When  I navigate to page "/titleslist/0/"
		Then I get the login page
		
	Scenario: Invalid login
		When I login as user "noadmin"
		Then I get the login page

	Scenario: Valid login
		When I login as user "admin"
		Then I get the installation page
