#-*- encoding:utf-8 ; mode:ruby -*-
#
# The Rakefile contains mainly packging tasks for the installation archives
#

require 'rake/clean'
require 'rake/packagetask'
require 'fileutils'

APPNAME = 'BicBucStriim'
VERSION = '0.5.0'

Rake::PackageTask.new(APPNAME, VERSION) do |p|
	p.need_tar = true
	p.need_zip = true
  p.package_files.include("img/**/*.*")  
  p.package_files.include("js/**/*.min.js")
  p.package_files.include("js/script.js")
  p.package_files.include("style/style.css")
  p.package_files.include("lib/**/*.*")
  p.package_files.include("templates/**/*.*")
  p.package_files.include("index.php")
  p.package_files.include("config.php")
  p.package_files.include(".htaccess")
  p.package_files.include("NOTICE")
  p.package_files.include("README.md")
end

task :copy2nas => [:package] do |t|
	sh "rsync -rv pkg/#{APPNAME}-#{VERSION}/ /Volumes/web/bbs"
end

task :default => [:clobber, :package]

