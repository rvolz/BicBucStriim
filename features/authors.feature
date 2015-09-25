Feature: The authors list displays all book authors in the library.

	The authors list is a browseable list of all authors, sorted by lastname.
	The list is paginated, the page size is defined in the configuration
	settings. Users can browse the list with *next* and *back* buttons.

	Scenario: The menu button *Authors* shows the first page
		When I click on menu item "Authors"
		Then the app switches to the "Authors" view
		And the list contains 2 items
		And the menu item "Authors" is active
		And there are no "previous" buttons
		And there are "next" buttons

	Scenario: Clicking on a list entry leads to an author details page
		When I click on menu item "Authors"
		And the app switches to the "Authors" view
		And I click on list item "Eichendorff, Joseph von"
		Then the "Authors" details page 5 for "Joseph von Eichendorff" appears

	Scenario: The *next* button shows the next 2 Authors
		When I click on menu item "Authors"
		And the app switches to the "Authors" view
		And there are "next" buttons
		And I click on the "next" button
		Then "Authors" page 1 with content "Heyse, Paul" appears
		And the list contains 2 items
		And there are "previous" buttons
		And there are "next" buttons

	Scenario: The *back* button shows the previous 2 Authors
		When I click on menu item "Authors"
		And the app switches to the "Authors" view
		And I go to the "Authors" page 1
		And I click on the "previous" button
		Then "Authors" page 0 with content "Eichendorff, Joseph von" appears
		And the list contains 2 items

	Scenario: The last list page has only a *back* button
		When I click on menu item "Authors"
		And the app switches to the "Authors" view
		And I go to the "Authors" page 2
		Then "Authors" page 2 with content "Ruskin, John" appears
		And the list contains 2 items
		And there are "previous" buttons
		And there are no "next" buttons

	Scenario: Searching leads to global search
		When I click on menu item "Authors"
		And the app switches to the "Authors" view
	  And I enter "sto" into the "authors" search field
		Then the search result page appears
		And the page contains "Books: 1"
		And the page contains "Authors: 1"
		And the page contains "Tags: 0"
		And the page contains "Series: 0"
