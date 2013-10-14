Feature: The home page lists the most recent books

	The home page lists the most recent books in the library. The number of
	books listed is defined by the configuration settings.

	Scenario: list all new books
		Given the application is configured with a page size of 2
		When I navigate to the home page
		Then I see my 2 newest books
		And the menu item "Home" is active

	