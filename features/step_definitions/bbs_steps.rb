# encoding: utf-8

When /^I click on menu item "(.+)"$/ do |menu|
  click_on menu
end

Then /^the app switches to the "(.+)" view$/ do |view|
  case view 
  when 'Home'
    page.should have_content("Most recent")
  when 'Books'
    page.should have_title("Books")
  when 'Authors'
    page.should have_title("Authors")
  when 'Tags'
    page.should have_title("Tags")
  when 'Series'
    page.should have_title("Series")
  when 'Admin'
    page.should have_title("Configuration")
  when 'Configuration'
    page.find('div.ui-content h1', :visible => true).should have_content('Configuration')
  when 'Users'
    page.should have_title("Users")
  else
    raise RuntimeError.new("Invalid topic #{topic}")
  end
end

Then /^the menu item "(.+)" is active$/ do |item|
  uiblock = ''
  case item
  when 'Home'
    uiblock = 'li.ui-block-a'
  when 'Books'
    uiblock = 'li.ui-block-b'
  when 'Authors'
    uiblock = 'li.ui-block-c'
  when 'Tags'
    uiblock = 'li.ui-block-d'
  when 'Series'
    uiblock = 'li.ui-block-e'
  else
    raise RuntimeError.new("Invalid topic #{topic}")
  end
  page.should have_css("div.ui-navbar ul #{uiblock} a.ui-btn-active")
end

Then(/^the list contains (\d+) item/) do |arg1|
  expect(page).to have_css("ul.ui-listview li", :count => arg1)
end


Then(/^there are no "(.*?)" buttons$/) do |arg1|
  expect(page).to have_no_link(arg1)
end

Then(/^there are "(.*?)" buttons$/) do |arg1|
  expect(page).to have_link(arg1, :count => 1)
end

When(/^I click on the "(.*?)" button$/) do |arg1|
  all(:link, arg1)[0].click
end

Then(/^"(.*?)" page (\d+) with content "(.*?)" appears$/) do |item, pgno, content|
  # Wait for the AJAX content to become visible
  page.find('div.ui-content', :visible => true).should have_content(content)

  part = get_path_for_topic(item)
  expect(page.current_url).to end_with("/#{part}list/#{pgno}/")
end

When(/^I go to the "(.+)" page (\d+)$/) do |topic, page|
  part = get_path_for_topic(topic)
  visit "/#{part}list/#{page}/"
end

When(/^I enter "(.*?)" into the "(.+)" search field$/) do |arg1, view|
  # Add "\n" to simulate key press
  id = "show_#{view}_options"
  click_on id
  fill_in('search', :with => "#{arg1}\n")
end

Then(/^the search result page appears$/) do
  # Wait for the AJAX content to become visible
  page.find('div.ui-content', :visible => true).should have_content('Books:')
  expect(page.current_path).to eq("/bbs/search/")
end

Then(/^the page contains "(.*?)"$/) do |arg1|
  expect(page).to have_content(arg1)
end

When /^I click on list item "(.+)"$/ do |item|
  page.find('div.ui-content', :visible => true).should have_content(item)
  click_link "#{item}", :wait => 10
end

Then(/^the "(.+)" details page (\d+) for "(.+)" appears$/) do |item, pgno, content|
  # Wait for the AJAX content to become visible
  if (item == 'Authors')
    page.find('div.ui-content section header div#author-metadata div h3', :visible => true).should have_content(content)
  else
    page.find('div.ui-content section header h1', :visible => true).should have_content(content)
  end

  path = get_path_for_topic(item)
  expect(page.current_path).to include("/#{path}/#{pgno}")
end

Then(/^clicking on "Download" reveals the download options$/) do 
  click_on "Download"
  page.find('div.dl_download', :visible => true).should have_content('EPUB')
end

Then(/^clicking on download format "(.*)" starts the download for file "(.*)" and length (\d+)$/) do |format, file, length|
  click_on format, :wait => 15
  if format == 'EPUB'
    ft = 'application/epub+zip'
  else
    ft = 'application/octet-stream'
  end
  result = expect(page.response_headers['Content-Type']).to eq(ft)
  if result
    expect(page.response_headers['Content-Length']).to eq(length)
    result = expect(page.response_headers['Content-Disposition']).to  eq("attachment; filename=\"#{file}\"")
  end
  result
end

When(/^I click on author "(.*)"$/) do |name|
  page.find('div#authors div.ui-collapsible-content', :visible => true).should have_content(name)
  click_on name
end

When(/^I click on "Tags" to reveal the tags$/) do
  all(:link, 'Tags')[1].click
end

When(/^I click on tag "(.*?)"$/) do |tag|
  page.find('div#tags div.ui-collapsible-content', :visible => true).should have_content(tag)
  click_on tag
end

Then(/^there are no "Series" links$/) do
  expect(page).to have_no_css('div#series')  
end

Then(/^there are "Series" links$/) do
  expect(page).to have_css('div#series')  
end

Then(/^the series link contains the text "(.*?)"$/) do |txt|
  expect(page).to have_text(txt)  
end

Then(/^I click on "Series" to reveal the series$/) do
  all(:link, 'Series')[1].click
end

Then(/^I click on series "(.*?)"$/) do |arg1|
  page.find('div#series div.ui-collapsible-content', :visible => true).should have_content(arg1)
  click_on arg1
end

Then(/^there is no custom column info$/) do
  expect(page).to have_no_css('div#custom_columns')  
end

Then(/^there is custom column info$/) do
  expect(page).to have_css('div#custom_columns')  
end

Then(/^I click on "(.*)" to reveal the custom column info "(.*)"$/) do |arg1, arg2|
  click_on arg1
  page.find('div#custom_columns div.ui-collapsible-content', :visible => true).should have_content(arg2)
end

When(/^I close the author image panel$/) do
  keypress_script = "$('#author-mdthumb-panel').panel('close');"
  page.execute_script(keypress_script)
end


Then(/^I get the error message "(.*)"$/) do |arg1|
  expect(page).to have_content(arg1)
end

Then(/^I get the success message "(.*?)"$/) do |arg1|
  expect(page).to have_content(arg1)
end
