Feature: Delete author images

	Users can add author images to author detail pages
	that are displayed on top of the page. These images
	are also reused to serve as thumbnails in the author
	list view.

	If an author image is deleted, the thumbnail will 
	also be replaced by the default image.

	Background:
		When I click on menu item "Authors"
		And the app switches to the "Authors" view
		And I click on list item "Grimmelshausen"
		And the "Authors" details page 6 for "Grimmelshausen" appears
		And I click on the metadata menu
		And I click on the image menu button
		And the author image panel appears
		And I attach the author image file "tests/fixtures/author1.jpg"
		And press the "Upload" button
		And there is an author image

	Scenario: Delete an author image
		When I click on the metadata menu
		And I click on the image menu button
		And the author image panel appears
		And press the "Delete" link
		Then I get the success message "Changes applied"

	Scenario: Deleting a non-existent author image does nothing
		When I click on the metadata menu
		And I click on the image menu button
		And the author image panel appears
		And press the "Delete" link
		And I get the success message "Changes applied"
		And press the "Delete" link
		Then I get the success message "Changes applied"

	Scenario: Deleting an author image also replaces the thumbnail
		When I click on the metadata menu
		And I click on the image menu button
		And the author image panel appears
		And press the "Delete" link
		And I get the success message "Changes applied"
		And I close the author image panel
		And I click on menu item "Authors"
		And the app switches to the "Authors" view
		Then the entry for author "Grimmelshausen" shows the default thumbnail

		
