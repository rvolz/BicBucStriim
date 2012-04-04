guard :shell do
	watch /.*/ do |m|
		unless m =~ /NOTICE|README.md|.gitginore|Guardfile|Rakefile|bbs\..+|pkg\/.+/
			'rsync -av --exclude=pkg --exclude=.git --exclude=NOTICE --exclude=README.md --exclude=.gitginore --exclude=Guardfile --exclude=Rakefile.rb --exclude=bbs.* . admin@ds1201:/volume1/web/bbs'
		end
	end		
end
