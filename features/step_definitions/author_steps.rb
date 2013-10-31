# encoding: utf-8

Then(/^just name "(.*?)" is mentioned$/) do |arg1|
  expect(page.find('div#author-metadata div h3', :visible => true)).to have_content(arg1)
end

Then(/^there is no author image$/) do
  expect(page).not_to have_css('div#author-metadata div img#author-thumbnail-pic')
end

Then(/^there are no author links$/) do
  expect(page).not_to have_css('div#author-metadata div#author-links ul#author-links-list li')
end

Then(/^there are two books$/) do
  expect(page).to have_css('section#author-books ul li', :count => 2)
end

Then(/^the entry for author "(.*?)" shows the default thumbnail$/) do |arg1|
  within(:xpath, '//ul/li/a[@href="/bbs/authors/6/0/"]') do
    expect(page).to have_content(arg1)
    expect(page).to have_xpath('img[@src="/bbs/img/writer.png"]')
  end
end

When(/^I click on the metadata menu$/) do
  page.find('#author_edit').click  
end

When(/^I click on the image menu button$/) do
  expect(page.find('div#popupMenu-popup div#popupMenu ul', :visible => true)).to have_content('Meta data')
  page.find(:link, 'Image').click  
end

Then(/^the author image panel appears$/) do
  expect(page.find('section#author-mdthumb-panel', :visible => true)).to have_content('Image')
end

When(/^I attach the author image file "(.*?)"$/) do |arg1|
  within(:xpath, '//form[@id="author-pic"]/div[@class="ui-input-text ui-body-inherit ui-corner-all ui-shadow-inset"]') do
    attach_file('file', arg1)
  end
end

When(/^press the "(.*?)" button$/) do |arg1|
  page.find(:button, arg1).click  
end

When(/^press the "(.*?)" link$/) do |arg1|
  page.find(:link, arg1).click  
end

Then(/^there is an author image$/) do
  expect(page.find('div#author-metadata div.ui-block-a img', :visible => true)).to have_xpath('//img[@src="/bbs/data/authors/author_1_thm.png"]')
end

Then(/^the entry for author "(.*?)" shows the custom thumbnail "(.*?)"$/) do |arg1, arg2|
  within(:xpath, '//ul/li/a[@href="/bbs/authors/6/0/"]') do
    expect(page).to have_content(arg1)
    expect(page).to have_xpath("img[@src=\"#{arg2}\"]")
  end
end

