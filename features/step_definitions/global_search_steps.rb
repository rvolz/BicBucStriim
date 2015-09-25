When(/^search for "(.*?) in "(.+)"$/) do |arg1, view|
  id = "show_#{view}_options"
  click_on id
	fill_in('search', :with => "#{arg1}\n")
end

Then(/^I see (\d+) book as a result$/) do |arg1|
	page.has_text? 'Books: '+arg1
end

Then(/^I see (\d+) books as a result$/) do |arg1|
	page.has_text? 'Books: '+arg1
end

Then(/^I see (\d+) authors as a result$/) do |arg1|
	page.has_text? 'Authors: '+arg1
end

Then(/^I see (\d+) tags as a result$/) do |arg1|
	page.has_text? 'Tags: '+arg1
end

Then(/^I see (\d+) series as a result$/) do |arg1|
	page.has_text? 'Series: '+arg1
end
