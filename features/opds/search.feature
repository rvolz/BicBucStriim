@opds
Feature: User searches via OPDS
	In order to search for books
	As an OPDS Reader
	I can request a search.

	Scenario: Find the OpenSearch description document
		When I request the root catalog
		Then it contains a link to the OpenSearch descriptor document

	Scenario: OpenSearch descriptor contains search terms
		When I request the OpenSearch descriptor
		Then it contains a template for the search

	Scenario: Request a search with the search template
		When I request a search with a filled out search template
		Then I receive an OPDS catalog with the results
		
