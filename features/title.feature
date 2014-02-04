Feature: The title details page provides a data summary and downloads.

	A title details page shows the most important data items for a 
	book from the Calibre library. It also provides download links for
	all defined formats. 

	Scenario: A simple title page with cover image, author, tag and download.
		When I click on menu item "Books"
		And the app switches to the "Books" view
		And I click on list item "Glücksritter"
		Then the "Books" details page 4 for "Die Glücksritter" appears

	Scenario: Books can be downloaded
		When I click on menu item "Books"
		And the app switches to the "Books" view
		And I click on list item "Glücksritter"
		And the "Books" details page 4 for "Die Glücksritter" appears
		Then clicking on "Download" reveals the download options
		And clicking on download format "EPUB" starts the download for file "Die Glucksritter - Joseph von Eichendorff.epub" and length 10175

	Scenario: Author links lead to author pages
		When I click on menu item "Books"
		And the app switches to the "Books" view
		And I click on list item "Glücksritter"
		And the "Books" details page 4 for "Die Glücksritter" appears
		And I click on author "Joseph von Eichendorff" 
		Then the "Authors" details page 5 for "Joseph von Eichendorff" appears

	Scenario: Tag links lead to tag pages
		When I click on menu item "Books"
		And the app switches to the "Books" view
		And I click on list item "Glücksritter"
		And the "Books" details page 4 for "Die Glücksritter" appears
		And I click on "Tags" to reveal the tags
		And I click on tag "Fachbücher" 
		Then the "Tags" details page 3 for "Fachbücher" appears

	Scenario: If a book is not part of a series, no series links are provided
		When I click on menu item "Books"
		And the app switches to the "Books" view
		And I click on list item "Glücksritter"
		And the "Books" details page 4 for "Die Glücksritter" appears
		Then there are no "Series" links

	Scenario: If a book is part of a series, series links are provided
		When I click on menu item "Books"
		And the app switches to the "Books" view
		And I click on list item "Lob der Faulheit"
		And the "Books" details page 1 for "Lob der Faulheit" appears
		Then there are "Series" links
		And I click on "Series" to reveal the series
		And the series link contains the text "Serie Lessing (1.0)"
		And I click on series "Serie Lessing" 
		Then the "Series" details page 4 for "Serie Lessing" appears

	Scenario: If a book has no custom column info, no info is displayed
		When I click on menu item "Books"
		And the app switches to the "Books" view
		And I click on list item "Glücksritter"
		And the "Books" details page 4 for "Die Glücksritter" appears
		Then there is no custom column info

	Scenario: If a book has custom column info, that is displayed
		When I click on menu item "Books"
		And the app switches to the "Books" view
		And I click on list item "Lob der Faulheit"
		And the "Books" details page 1 for "Lob der Faulheit" appears
		Then there is custom column info
		And I click on "Custom Calibre Data" to reveal the custom column info "Col6"

