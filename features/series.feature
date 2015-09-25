Feature: The series list displays all book series in the library.

	The series list is a browseable list of all series, sorted byname.
	The list is paginated, the page size is defined in the configuration
	settings. Users can browse the list with *next* and *back* buttons.

	Scenario: The menu button *Series* shows the first page
		When I click on menu item "Series"
		Then the app switches to the "Series" view
		And the list contains 2 items
		And the menu item "Series" is active
		And there are no "previous" buttons
		And there are "next" buttons

	Scenario: Clicking on a list entry leads to a series details page
		When I click on menu item "Series"
		And the app switches to the "Series" view
		And I click on list item "Serie Grimmelshausen"
		Then the "Series" details page 1 for "Serie Grimmelshausen" appears

	Scenario: The *next* button shows the next series with only *back* buttons
		When I click on menu item "Series"
		And the app switches to the "Series" view
		And there are "next" buttons
		And I click on the "next" button
		Then "Series" page 1 with content "Serie Rilke" appears
		And the list contains 1 item1
		And there are "previous" buttons
		And there are no "next" buttons

	Scenario: The *back* button shows the previous series
		When I click on menu item "Series"
		And the app switches to the "Series" view
		And I go to the "Series" page 1
		And I click on the "previous" button
		Then "Series" page 0 with content "Serie Lessing" appears
		And the list contains 2 items

	Scenario: Searching leads to global search
		When I click on menu item "Series"
		And the app switches to the "Series" view
	  And I enter "sto" into the "series" search field
		Then the search result page appears
		And the page contains "Books: 1"
		And the page contains "Authors: 1"
		And the page contains "Tags: 0"
		And the page contains "Series: 0"
