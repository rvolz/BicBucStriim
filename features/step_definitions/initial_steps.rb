# Step definitions for 'initial' features

Given /^I just installed the application$/ do
    #visit "/dev/reset"
    visit "/"
end

Given /^I login as user "(.+)"$/ do |user|
    if (user == "admin")
        password = "admin"
    else
        password = ""
    end
    visit "/login/"
    fill_in('username', :with => user)
    fill_in('password', :with => password)
    click_button 'Login'    
end

When /^I navigate to page "(.+)"$/ do |page|
    visit "#{page}"
end 

When /^I click the menu link "(.+)"$/ do |menu|
    click_link "#{menu}"
end 

Then /^I get the login page$/ do
    page.should have_content 'BicBucStriim :: Login'
end

Then /^I get the installation page$/ do
    page.should have_content 'BicBucStriim :: Configuration'
end

Then /^I get redirected to the admin page$/ do
    # Not supported by Selenium
    page.status_code.should == 200
    page.should have_content 'BicBucStriim :: Configuration'
end

When /^I enter an invalid directory name$/ do
    #visit "/"
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
