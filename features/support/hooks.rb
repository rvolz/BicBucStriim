Before("~@initial") do  
	visit "/dev/reset"
  visit "/"
  fill_in('calibre_dir', :with => '/tmp/calibre')
  fill_in('page_size', :with => 2)
  click_button 'Save'
end

