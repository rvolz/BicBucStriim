Feature: Protect book downloads with a tag
	In order to protect certain of my books 
	As an admin
	I activate the selective download protection

	Scenario: download protected with tag
		Given I choose admin download protection, using password "admin"
		And I choose selective download protection with tag "Venice"
		When a user navigates to book page "7"
		Then the download is protected

	Scenario: download not protected with tag
			Given I choose admin download protection, using password "admin"
			And I choose selective download protection with tag "Venice"
			When a user navigates to book page "4"
			Then the page shows the download options
