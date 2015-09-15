require 'fileutils'
include FileUtils

# Reset the DB and clear generated images
# Set English as default language for testing
Before do 
	#browser = Capybara.current_session.driver.browser
	#browser.clear_cookies
	cp_r 'tests/work/src/data/data.backup', 'tests/work/src/data/data.db' 	
	rm_rf 'tests/work/src/data/titles/*.png'
	rm_rf 'tests/work/src/data/authors/*.png'
	page.driver.header 'Accept-Language', 'en'
end

# Normal features work with the "admin" login
# and a page size of "2"
Before("~@initial") do
  visit "/login/"
  fill_in('username', :with => "admin")
	fill_in('password', :with => "admin")
	click_button 'Login', :wait => 1500
  fill_in('calibre_dir', :with => '/tmp/calibre')
  fill_in('page_size', :with => 2)
  click_button 'Save'
end

