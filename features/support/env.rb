gem 'capybara', '=1.1.3'
require 'capybara'
require 'capybara/cucumber'
require 'capybara/webkit'
#require 'capybara/poltergeist'
Capybara.run_server = false
Capybara.app_host = 'http://localhost:8080/bbs/'
Capybara.default_driver = :webkit
#Capybara.javascript_driver = :poltergeist

World do
	Capybara::Session.new(:webkit)
	Capybara.current_session.driver.header 'Accept-Language', 'en'
end