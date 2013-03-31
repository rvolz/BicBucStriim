Feature: Protect book downloads with a global password
	In order to protect my books 
	As an admin
	I activate the global download protection

	Scenario: no download protection
		Given I choose no download protection
		When a user navigates to book page "4"
		Then the page shows the download options
	
	Scenario: download protected with admin password
		Given I choose admin download protection, using password "admin"
		When a user navigates to book page "4"
		Then the download is protected

	Scenario: download protected with separate password
		Given I choose separate download protection, using password "separate"
		When a user navigates to book page "4"
		Then the download is protected

	Scenario: access with admin password
		Given I choose admin download protection, using password "admin"
		When a user navigates to book page "4"
		And enters the password "admin"
		And a user navigates to book page "4"
		Then the page shows the download options
		And the cookie "glob_dl_access" is set


	Scenario: access with separate password
		Given I choose separate download protection, using password "separate"
		When a user navigates to book page "4"
		And enters the password "separate"
		And a user navigates to book page "4"
		Then the page shows the download options
		And the cookie "glob_dl_access" is set

	@javascript
	Scenario: no access without password
		Given I choose admin download protection, using password "admin"
		When a user navigates to book page "4"
		And enters the password "noadmin"
		Then the page shows an error 

	@remove
	Scenario: no direct download without password
		Given I choose admin download protection, using password "admin"
		When a user downloads a boook directly
		Then the app sends reponse code "401"
	
	@remove
	Scenario: reset download protection -- for simpletest
		Given I choose no download protection
		When a user navigates to book page "4"
		Then the page shows the download options
	