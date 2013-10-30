Feature: Adding author images

	Users can add author images to author detail pages
	that are displayed on top of the page. These images
	are also reused to serve as thumbnails in the author
	list view.

	The image will be resized to 160*160 pixels on upload,
	max. size is 3MB. Acceptable types are JPEG and PNG.

	Background:
		When I click on menu item "Authors"
		And the app switches to the "Authors" view
		And I click on list item "Grimmelshausen"
		And the "Authors" details page 6 for "Grimmelshausen" appears
		And I click on the metadata menu
		And I click on the image menu button
		Then the author image panel appears

	Scenario: Add an author image
		When I attach the author image file "tests/fixtures/author1.jpg"
		And press the "Upload" button
		Then there is an author image

	Scenario: Use an author image larger than 3 MB
		When I attach the author image file "tests/fixtures/mb3.png"
		And press the "Upload" button
		Then I get the error message "Wrong file type or too big"
		And there is no author image

	Scenario: Use an author image with an unsupported file type
		When I attach the author image file "tests/fixtures/mb3b.gif"
		And press the "Upload" button
		Then I get the error message "Wrong file type or too big"
		And there is no author image

	Scenario: Authors with an author image get a also thumbnail version
		When I attach the author image file "tests/fixtures/author1.jpg"
		And press the "Upload" button
		And there is an author image
		And I click on menu item "Authors"
		And the app switches to the "Authors" view
		Then the entry for author "Grimmelshausen" shows the custom thumbnail "/bbs/data/authors/author_1_thm.png"

		
