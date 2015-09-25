Feature: The titles list displays all books in the library.

	The titles list show all books ordered alphabetically by title.
	The list is paginated, the page size is defined in the configuration
	settings. Users can browse the list with *next* and *back* buttons.

	Scenario: The menu button *Books* shows the first page
		When I click on menu item "Books"
		Then the app switches to the "Books" view
		And the list contains 2 items
		And the menu item "Books" is active
		And there are no "previous" buttons
		And there are "next" buttons

	Scenario: Clicking on a list entry leads to a title details page
		When I click on menu item "Books"
		And the app switches to the "Books" view
		And I click on list item "Lob der Faulheit"
		Then the "Books" details page 1 for "Lob der Faulheit" appears

	Scenario: The *next* button shows the next 2 books
		When I click on menu item "Books"
		And the app switches to the "Books" view
		And there are "next" buttons
		And I click on the "next" button
		Then "Books" page 1 with content "Neues Leben" appears
		And the list contains 2 items
		And there are "previous" buttons
		And there are "next" buttons

	Scenario: The *back* button shows the previous 2 books
		When I click on menu item "Books"
		And the app switches to the "Books" view
		And I go to the "Books" page 1
		And I click on the "previous" button
		Then "Books" page 0 with content "Lob der Faulheit" appears
		And the list contains 2 items

	Scenario: The last list page has only a *back* button
		When I click on menu item "Books"
		And the app switches to the "Books" view
		And I go to the "Books" page 3
		Then "Books" page 3 with content "Zwei Wege sinds" appears
		And the list contains 1 item
		And there are "previous" buttons
		And there are no "next" buttons

	Scenario: Searching leads to global search
		When I click on menu item "Books"
		And the app switches to the "Books" view
	  And I enter "sto" into the "titles" search field
		Then the search result page appears
		And the page contains "Books: 1"
		And the page contains "Authors: 1"
		And the page contains "Tags: 0"
		And the page contains "Series: 0"
