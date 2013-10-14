Feature: Series details pages list the books for a single series

	Scenario: A series details page lists books for a single series
		When I click on menu item "Series"
		And the app switches to the "Series" view
		And I click on list item "Serie Grimmelshausen"
		Then the "Series" details page 1 for "Serie Grimmelshausen" appears
		And the list contains 2 items