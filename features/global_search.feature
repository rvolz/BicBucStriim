Feature: User starts a global search
	Given the application is configured with a page size of 1
	In order to search for something
	As a user
	I go to the home page.

	Scenario: Search from the start screen with one result
		When I navigate to the home page
	  And search for "stones" in "home"
		Then I see 1 book as a result
		And I see 0 authors as a result
		And I see 0 tags as a result
		And I see 0 series as a result
		
	Scenario: Search from the start screen with multiple results
		When I navigate to the home page
	  And search for "s" in "home"
		Then I see 6 books as a result
		And I see 5 authors as a result
		And I see 1 tags as a result
		And I see 3 series as a result
		