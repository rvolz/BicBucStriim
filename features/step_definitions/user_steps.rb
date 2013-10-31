When(/^I enter the user credentials "(.*?)", "(.*?)"$/) do |arg1, arg2|  
  fill_in('newuser_name', :with => arg1)
  fill_in('newuser_password', :with => arg2)
  click_button 'Save'    
end

Then(/^the list contains user "(.*?)"$/) do |arg1|
	within(:xpath, '//ul[@id="users"]') do
		expect(page).to have_content(arg1)
	end
end

