When /^I click on menu item (.+)$/ do |menu|
  click_on menu
end

Then /^the app switches to the home view$/ do
  page.should have_content("Most recent")
end

Then /^the menu item (.+) is active$/ do |arg1|
  pending # express the regexp above with the code you wish you had
end

Then /^the app shows page (\d+) of the titleslist$/ do |arg1|
  pending # express the regexp above with the code you wish you had
end