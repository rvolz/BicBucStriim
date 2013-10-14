Feature: Author details pages list books by an author

	The details pages for authors list all books by an author in the
	library. If there are no images or links, the page shows just the 
	author's name and a default thumbnail in the list view.

	Scenario: Page lists all books
		When I click on menu item "Authors"
		And the app switches to the "Authors" view
		And I click on list item "Grimmelshausen"
		Then the "Authors" details page 6 for "Grimmelshausen" appears
		And just name "Hans Jakob Christoffel von Grimmelshausen" is mentioned
		And there is no author image
		And there are no author links
		And there are two books

	Scenario: Author has default thumbnail
		When I click on menu item "Authors"
		And the app switches to the "Authors" view
		Then the entry for author "Grimmelshausen" shows the default thumbnail
		
