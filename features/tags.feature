Feature: The tags list displays all book tags in the library.

	The tags list is a browseable list of all book tags, sorted by tag name.
	The list is paginated, the page size is defined in the configuration
	settings. Users can browse the list with *next* and *back* buttons.

	Scenario: The menu button *Tags* shows the first page
		When I click on menu item "Tags"
		Then the app switches to the "Tags" view
		And the list contains 2 items
		And the menu item "Tags" is active
		And there are no "previous" buttons
		And there are "next" buttons

	Scenario: Clicking on a list entry leads to a tag details page
		When I click on menu item "Tags"
		And the app switches to the "Tags" view
		And I click on list item "Architecture"
		Then the "Tags" details page 21 for "Architecture" appears

	Scenario: The *next* button shows the next 2 Tags
		When I click on menu item "Tags"
		And the app switches to the "Tags" view
		And there are "next" buttons
		And I click on the "next" button
		Then "Tags" page 1 with content "Biografien & Memoiren" appears
		And the list contains 2 items
		And there are "previous" buttons
		And there are "next" buttons

	Scenario: The *back* button shows the previous 2 Tags
		When I click on menu item "Tags"
		And the app switches to the "Tags" view
		And I go to the "Tags" page 1
		And I click on the "previous" button
		Then "Tags" page 0 with content "Architecture" appears
		And the list contains 2 items

	Scenario: The last list page has only a *back* button
		When I click on menu item "Tags"
		And the app switches to the "Tags" view
		And I go to the "Tags" page 2
		Then "Tags" page 2 with content "Venice" appears
		And the list contains 2 items
		And there are "previous" buttons
		And there are no "next" buttons

	Scenario: Searching leads to global search
		When I click on menu item "Tags"
		And the app switches to the "Tags" view
	  And I enter "sto" into the "tags" search field
		Then the search result page appears
		And the page contains "Books: 1"
		And the page contains "Authors: 1"
		And the page contains "Tags: 0"
		And the page contains "Series: 0"
