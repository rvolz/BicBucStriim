When /^I navigate to the home page$/ do
	click_link 'Home'
end

Then /^I see my (\d+) newest books$/ do |arg1|  
  page.should have_content 'Most recent 30'
  page.all(:xpath, '//div[@class="ui-content"]/ul/li').size.should == arg1.to_i
  page.should have_content 'The Stones of Venice, Volume II (2012)'
  page.should have_content 'Neues Leben (2012)'
end