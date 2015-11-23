#-*- encoding:utf-8 ; mode:ruby -*-
#
# The Rakefile contains mainly packging tasks for the installation archives
# and helpers for a Vagrant-based integration test environment. Works
# with Vagrant 1.2+ and Cucumber.
#

require 'rake/clean'
require 'rake/packagetask'
require 'cucumber/rake/task'
require 'less'
require 'fileutils'
require 'json'
require 'yaml'
require 'logger'

APPNAME = 'BicBucStriim'
VERSION = '1.3.6'

SOURCE = "."
LESS = File.join( SOURCE, "style")
CONFIG = {
  'less' => File.join( LESS, "" ),
  'css' => File.join( LESS, "" ),
  'input' => "style.less",
  'output' => "style.css"
}
 
desc "Compile Less"
task :lessc do
  less = CONFIG['less']
 
  input = File.join( less, CONFIG['input'] )
  output = File.join( CONFIG['css'], CONFIG['output'] )
 
  source = File.open( input, "r" ).read

  parser = Less::Parser.new :paths => [less]
  tree = parser.parse( source )
 
  File.open( output, "w+" ) do |f|
    f.puts tree.to_css( :compress => true )
  end
end # task :lessc


desc "Generate the message file langs.php"
task :genl10n do |t|
  msgs = YAML.load_file("messages.yml")
  php = File.new('lib/BicBucStriim/langs.php', 'w')
  php << "<?php\n"
  php << "# Generated file. Please don\'t edit here,\n"
  php << "# edit messages.yml instead. \n"
  php << "#\n"
  ['de', 'en', 'fr', 'it', 'nl'].each do |lang|
    php << "$lang#{lang} = array(\n"
    msgs.each do |msg, locs|      
      php << "'#{msg}' => '#{locs[lang]}',\n" unless locs[lang].nil?
    end
    php << ");\n"
    php << "\n"
  end
  php << "?>\n"
  php.close
end

desc "Make a release package"
task :pack => [:lessc, :genl10n] do |t|
  Rake::PackageTask.new(APPNAME, VERSION) do |p|
    p.need_tar = true
    p.need_zip = true
    p.package_files.include("img/**/*.*")  
    p.package_files.include("js/**/*.min.js")
    p.package_files.include("js/libs/jquery.cookie.js")
    p.package_files.include("js/script.js")
    p.package_files.include("style/jquery/**/*.*")
    p.package_files.include("style/style.css")
    p.package_files.include("lib/BicBucStriim/*.*")
    p.package_files.include("vendor/*.*")
    p.package_files.include("vendor/aura/**/*.*")
    p.package_files.include("vendor/composer/*.*")
    p.package_files.include("vendor/slim/slim/*.*")
    p.package_files.include("vendor/slim/views/**/*.*")
    p.package_files.include("vendor/slim/slim/Slim/**/*.*")
    p.package_files.include("vendor/twig/twig/*.*")
    p.package_files.include("vendor/twig/twig/ext/**/*.*")
    p.package_files.include("vendor/twig/twig/lib/**/*.*")
    p.package_files.include("vendor/swiftmailer/swiftmailer/lib/**/*.*")
    p.package_files.include("vendor/swiftmailer/swiftmailer/*.*")
    p.package_files.include("vendor/ircmaxell/password-compat/lib/**/*.*")
    p.package_files.include("vendor/dflydev/markdown/src/**/*.*")
    p.package_files.include("vendor/dflydev/markdown/*.*")
    p.package_files.include("templates/**/*.*")
    p.package_files.include("data/**/*.*")
    p.package_files.include("index.php")
    p.package_files.include("installcheck.php")
    p.package_files.include("favicon.ico")
    p.package_files.include("bbs-icon.png")
    p.package_files.include("ChangeLog")
    p.package_files.include(".htaccess")
    p.package_files.include("NOTICE")
    p.package_files.include("LICENSE")
    p.package_files.include("README.md")
  end
  Rake::Task['repackage'].invoke
end

# Integration testing tasks only make sense when vagrant is installed.
# Starts the VM. It will be created and provisioned if necessary.
desc "Start the VM for integration testing"
task :itest_up do |t|  
  puts "About to run vagrant-up..." 
  system "bash -c 'pushd tests/env;vagrant up;popd'"
  puts "Finished running vagrant-up"
end

# Just halts the VM so that it can be resumed later.
desc "Halt the VM for integration testing"
task :itest_down do |t|
  puts "About to run vagrant-halt..."  
  system "bash -c 'pushd tests/env;vagrant halt;popd'"
  puts "Finished running vagrant-halt"
end

desc "Deploy the current code for testing to the VM dirs"
task :itest_deploy => [:pack] do |t|    
  code_target = "./tests/work/src"
  lib_target = "./tests/work/calibre"

  puts "Deploying code"
  rm_rf code_target+"/."
  cp_r "./pkg/#{APPNAME}-#{VERSION}/.", code_target
  cp code_target+"/data/data.db", code_target+"/data/data.backup"
  mkdir "#{code_target}/data/titles"
  mkdir "#{code_target}/data/authors"
  chmod_R 0777,"#{code_target}/data"
  puts "Deploying test fixtures"    
  rm_rf lib_target+"/."
  cp_r "tests/fixtures/lib2/.", lib_target
end

desc "Starts a ssh shell in the VM"
task :itest_shell do |t|
  system "bash -c 'pushd tests/env;vagrant ssh;popd'"
end

Cucumber::Rake::Task.new do |t|
  t.cucumber_opts = %w{--format progress}
end

desc "Integration testing (via integration test environment)"
task :itest => [:itest_deploy, :cucumber] 

desc "Unit testing"
task :test do |t|
  sh "php tests/test_all.php"
end

desc "Generate and copy version information file to server"
task :install_version_info do |t|
  version_info = {
    :version => VERSION,
    :url => 'http://projekte.textmulch.de/bicbucstriim/downloads/BicBucStriim-1.2.0.zip'
  }
  File.open('version.json','w') do |f|
    f.puts version_info.to_json
  end
  sh "scp version.json projekte.textmulch.de:~/tm_projekte/bicbucstriim/version.json"
  rm 'version.json'
end

task :default => [:clobber, :pack]

