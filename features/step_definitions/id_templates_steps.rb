When(/^I enter the id template data "(.*?)", "(.*?)" for id "(.*?)"$/) do |arg1, arg2, arg3|
  find(:xpath, '//li/a[@class="template ui-btn" and @data-template="'+arg3+'"]').click
  fill_in('label', :with => arg1)
  fill_in('template', :with => arg2)
  click_button 'Save'    
end

Then(/^the id template "([^"]*)" contains label "([^"]*)" and URL "([^"]*)"$/) do |arg1, arg2, arg3|
  within(:xpath, '//li/a[@class="template ui-btn" and @data-template="'+arg1+'"]') do
		expect(page).to have_content("#{arg1} #{arg2}")
		expect(page).to have_content(arg3)
	end
end

When(/^I delete id template "([^"]*)"$/) do |arg1|
  find(:xpath, '//li/a[@class="idtemplate_clear ui-btn ui-btn-icon-notext ui-icon-delete" and @data-template="'+arg1+'"]').click
end

Then(/^the id links contain label "([^"]*)" and url "([^"]*)"$/) do |arg1, arg2|
  within(:xpath, '//div[@id="idlinks"]') do
  	expect(page).to have_content(arg1)
  	#page.find(:xpath, './/a[@href="'+arg2+'""]')
  	find(:xpath, './/a[@href="'+arg2+'"]')
	end
end

Then(/^there are (\d+) id links$/) do |arg1|
	if (arg1.to_i == 0)
		page.assert_selector(:xpath, '//div[@id="idlinks"]', :count => 0)
	else 
	  within(:xpath, '//div[@id="idlinks"]') do
			find_all(:xpath, '..//div[@class="ui-collapsible-content ui-body-inherit"]').length == arg1.to_i
		end
	end
end