Feature: ID links are shown if templates are active

	Typical book IDs are ISBN numbers or IDs from distributors
	(like Amazon, Apple, Google ...) or URLs. Using ID templates 
	these IDs can be automatically transformed into HTTP links 
	to various other web sites, e.g. a ISBN search engine or book 
	catalogs.

	Background: Navigating to the id templates page
		When I click on menu item "Home"
		And the app switches to the "Home" view
		And I click on menu item "Admin"
		And the app switches to the "Admin" view
		And I click on menu item "ID Templates"
		And the app switches to the "ID Templates" view


	Scenario: Book pages with suitable IDs contain ID links
		When I enter the id template data "ISBN search", "http://isbnsearch.org/isbn/%id%" for id "test1"
		And I navigate to title details page "6"
		Then there are 1 id links 
		And the id links contain label "ISBN search" and url "http://isbnsearch.org/isbn/neuesleben1"

	Scenario: Book pages without suitable IDs contain no ID links
		When I enter the id template data "ISBN search", "http://isbnsearch.org/isbn/%id%" for id "test1"
		And I navigate to title details page "5"
		Then there are 0 id links
	