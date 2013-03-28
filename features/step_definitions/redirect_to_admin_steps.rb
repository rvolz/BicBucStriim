When /^I navigate to the root$/ do
  visit "/"
end

When /^I navigate to the titleslist page$/ do
  visit "/titleslist/0/"
end

Then /^I get redirected to the admin page$/ do
	# Not supported by Selenium
  # page.status_code.should == 200
  page.should have_content 'BicBucStriim :: Configuration'
end
