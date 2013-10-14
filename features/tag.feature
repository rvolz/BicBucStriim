Feature: Tag details pages list the books for a single tag

	Scenario: A tag details page lists books for a single tag
		When I click on menu item "Tags"
		And the app switches to the "Tags" view
		And I click on list item "Architecture"
		Then the "Tags" details page 21 for "Architecture" appears
		And the list contains 1 item