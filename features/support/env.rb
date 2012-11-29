gem 'capybara', '=1.1.3'
require 'capybara'
require 'capybara/cucumber'
require 'capybara/webkit'
require 'capybara-screenshot'
require 'capybara-screenshot/cucumber'
#require 'capybara/poltergeist'
Capybara.run_server = false
Capybara.app_host = 'http://localhost:8080/bbs/'
Capybara.default_driver = :webkit
#Capybara.javascript_driver = :poltergeist

World do
	Capybara::Session.new(:webkit)
	Capybara.current_session.driver.header 'Accept-Language', 'en'
	Capybara::Screenshot.register_filename_prefix_formatter(:rspec) do |example|
    "screenshot-#{example.description.gsub(' ', '-')}"
  end
end