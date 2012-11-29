Given /^the application is configured with a page size of (\d+)$/ do |arg1|
  visit "/dev/reset"
  visit "/"
  fill_in('calibre_dir', :with => '/tmp/calibre')
  fill_in('page_size', :with => arg1)
  click_button 'Save'
  page.has_xpath?('.//div[@id="flash"]/p[@class="success"]')
	page.should have_content 'Changes saved'
end

When /^I navigate to the home page$/ do
	click_link 'Home'
end

Then /^I see my (\d+) newest books$/ do |arg1|  
  page.should have_content 'Most recent'
  page.all(:xpath, '//div[@class="ui-content"]/ul/li').size.should == arg1.to_i
  page.should have_content 'Stones of Venice, Volume II'
  page.should have_content 'Neues Leben (2012)'
end
