Feature: The menu provides navigation between sections
	In order to navigate between different sections
	As a user
	I can use the menu

	Scenario: Home
		When I click on menu item Home
		Then the app switches to the home view
		And the menu item Home is active

	Scenario: Books
		When I click on menu item Books
		Then the app shows page 0 of the titleslist

	
