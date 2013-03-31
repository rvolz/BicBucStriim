# encoding: utf-8
# 

Given(/^I choose selective download protection with tag "(.*?)"$/) do |arg1|
	visit "/admin/"
	if (page.has_selector?('form#adminpwform', :visible => true))
    fill_in 'admin_pwin', :with => 'admin'
    click_button 'Submit Password'
  end
  fill_in 'tag_protect_field', :with => arg1
  page.driver.execute_script("$('#tag_protect_choice option[value=\"1\"]').attr('selected',true);");
  click_button 'Save'
  page.has_no_selector?('p.error')
end
