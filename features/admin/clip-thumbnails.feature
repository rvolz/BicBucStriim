Feature: Clip thumbnails

	An admin user can change the clipping of thumbnails.

	Background: Navigating to the configuration page
		When I click on menu item "Home"
		And the app switches to the "Home" view
		And I click on menu item "Admin"
		And the app switches to the "Admin" view
		And I click on menu item "Configuration"
		And the app switches to the "Configuration" view

	Scenario: Initially the thumbnails will be clipped to provide thumbnails without borders.
		Then the "thumb-gen-clipped" slider is set to "Yes"
