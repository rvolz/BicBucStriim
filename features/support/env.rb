require 'capybara'
require 'capybara/cucumber'
require 'capybara-screenshot'
require 'capybara-screenshot/cucumber'
require 'capybara-webkit'

Capybara.run_server = false
Capybara.app_host = 'http://localhost:8080/bbs'

# Capybara.register_driver :selenium do |app|
#     require 'selenium/webdriver'
#     profile = Selenium::WebDriver::Firefox::Profile.new
#   profile["intl.accept_languages"] = "en-us,en"
#   profile["browser.cache.disk.enable"] = false
#   profile["browser.cache.memory.enable"] = false
#   profile["browser.cache.offline.enable"] = false
#   profile["network.http.use-cache"] = false
#     Capybara::Selenium::Driver.new(app, :profile => profile)
# end

# Suppress odd Qt warnings that destroy the output formatting
# see https://github.com/thoughtbot/capybara-webkit/issues/485
class WarningSuppressor
  class << self
    def write(message)
      puts(message) unless message =~ /QFont::setPixelSize: Pixel size <= 0.*/
      0
    end
  end
end
Capybara.register_driver :webkit do |app|
  Capybara::Webkit::Driver.new(app, :stderr => WarningSuppressor)
end

#Capybara.default_driver = :selenium
Capybara.default_driver = :webkit
Capybara.javascript_driver = :webkit
# Default is 2 seconds, but the VM is sometimes slow.
Capybara.default_max_wait_time = 8


class WebWorld
  def initialize
    #Capybara::Session.new(:selenium) 
    Capybara::Screenshot.register_filename_prefix_formatter(:rspec) do |example|
      "screenshot-#{example.description.gsub(' ', '-')}"
    end
  end
  
  # Convenience method for URL paths based on topics
  def get_path_for_topic(topic)
    case topic
    when 'Books'
      path = 'titles'
    when 'Authors'
      path = 'authors'
    when 'Tags'
      path = 'tags'
    when 'Series'
      path = 'series'
    else
      raise RuntimeError.new("Invalid topic #{topic}")
    end
    path
  end
end

World do  
  WebWorld.new
end