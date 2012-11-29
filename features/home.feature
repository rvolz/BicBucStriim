Feature: The home page lists the most recent books
	Given the application is configured with a page size of 2
	In order to see my newest books
	As a user
	I go to the home page

	Scenario: list all new books
		Given the application is configured with a page size of 2
		When I navigate to the home page
		Then I see my 2 newest books
