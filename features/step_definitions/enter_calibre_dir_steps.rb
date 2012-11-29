Given /^I just installed the application$/ do
  visit "/dev/reset"
end

When /^I enter an invalid directory name$/ do
	visit "/"
  fill_in('calibre_dir', :with => '/tmp/no_calibre')
  click_button 'Save'
end

Then /^I get an error message$/ do
	page.has_xpath?('.//div[@id="flash"]/p[@class="error"]')
	page.should have_content 'The configured Calibre directory cannot be used.'
end

When /^I enter a valid directory name$/ do
  visit "/"
  fill_in('calibre_dir', :with => '/tmp/calibre')
  click_button 'Save'
end

Then /^the application saves the configuration$/ do
  page.has_xpath?('.//div[@id="flash"]/p[@class="success"]')
	page.should have_content 'Changes saved'
end

And /^I can navigate to the home page$/ do
  click_link 'Home'
  page.should have_content 'Most recent'
end
