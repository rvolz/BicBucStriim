# encoding: utf-8
# 
Given /^I choose no download protection$/ do
  visit "/admin/"
  choose 'No, not necessary'
  click_button 'Save'
end

Given /^I choose (admin|separate) download protection, using password "(.+)"$/ do |arg1, arg2|
  visit "/admin/"
  if (page.has_selector?('form#adminpwform', :visible => true))
    fill_in 'admin_pwin', :with => 'admin'
    click_button 'Submit Password'
  end
  if arg1 == 'admin'
		fill_in 'admin_pw', :with => arg2
		choose 'Yes, use admin password'
	else
		fill_in 'glob_dl_password', :with => arg2
		choose 'Yes, use a separate password (enter below)'
	end
  page.driver.execute_script("$('#tag_protect_choice option[value=\"0\"]').attr('selected',true);");
  click_button 'Save'
  page.has_no_selector?('p.error')
end

When /^a user navigates to book page "(\d+)"$/ do |arg1|
  visit "/titles/#{arg1}"
  page.has_content?('Book Details')
  page.has_content?('Die Glücksritter')
end

Then /^the download is protected$/ do
  page.has_selector?('div.dl_access')
  click_on 'Download'
  page.should have_css('p', :text => 'This book is protected. Please enter your password to enable the book download.', :visible => true)
  #page.should have_no_css('p', :text => 'Press a button to download the book in the respective format.', :visible => true)
end

Then /^the page shows the download options$/ do
  page.has_selector?('div.dl_download')
  click_link 'Download'
  #screenshot_and_open_image
  #page.should have_no_css('p', :text => 'This book is protected. Please enter your password to enable the book download.', :visible => true)
  #page.should have_css('p', :text => 'Press a button to download the book in the respective format.', :visible => true)
  page.should have_content 'Press a button to download the book'
end

Then /^the cookie "(.+)" is set$/ do |arg1|
  pending # webkit doesn't recognoze our cookies?
end

Then /^enters the password "(.+)"$/ do |arg1|
  click_on 'Download'
  click_on 'Submit Password'
  fill_in 'password', :with => arg1
  # Selenium can't find the button otherwise
  page.driver.execute_script("$('#submit_pw').click()");
  #click_on 'Submit Password'
end

Then /^the page shows an error$/ do
  page.has_content?('Invalid Password')
end

When /^a user downloads a boook directly$/ do
  visit 'titles/3/file/Der+seltzame+Springinsfeld+-+Hans+Jakob+Christoffel+von+Grimmelshausen.epub' 
end

#Then /^the app sends reponse code "(\d+)"$/ do |arg1|
#  x = page.driver.status_code
#  x.should == arg1.to_i
#end
