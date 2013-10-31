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

When(/^I delete user "(.*?)"$/) do |arg1|
  user = page.find('ul#users li', :text => arg1)
  within(user) do
  	find('a.user_delete').click
  end
end

When(/^I confirm the deletion$/) do
  find('a#delete_user').click
end

Then(/^the list doesn't contain user "(.*?)"$/) do |arg1|
  expect(page).to have_no_content(arg1)
end