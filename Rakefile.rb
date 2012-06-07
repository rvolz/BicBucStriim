#-*- encoding:utf-8 ; mode:ruby -*-
#
# The Rakefile contains mainly packging tasks for the installation archives
#

require 'rake/clean'
require 'rake/packagetask'
require 'fileutils'

APPNAME = 'BicBucStriim'
VERSION = '0.8.0'

begin
  require 'vagrant'
  # Needs also 'vagrant-sync' !
  # tests/env contains the integration testing environment with the Vagrantfile
  env = Vagrant::Environment.new(:cwd => "tests/env")
rescue
  env = nil
  puts STDERR, "*** Vagrant not installed. Integration testing tasks disabled. ***"
end

desc "Make a release package"
task :package2 do |t|
  mkdir "logs"
  chmod 0777, "logs"
  touch "logs/dummy.txt"
  sh 'echo "A dummy file" >> logs/dummy.txt'
  Rake::PackageTask.new(APPNAME, VERSION) do |p|
    p.need_tar = true
    p.need_zip = true
    p.package_files.include("img/**/*.*")  
    p.package_files.include("js/**/*.min.js")
    p.package_files.include("js/libs/jquery.cookie.js")
    p.package_files.include("js/script.js")
    p.package_files.include("style/jquery/**/*.*")
    p.package_files.include("style/style.css")
    p.package_files.include("lib/**/*.*")
    p.package_files.include("templates/**/*.*")
    p.package_files.include("logs/**/*.*")
    p.package_files.include("bicbucstriim.php")
    p.package_files.include("index.php")
    p.package_files.include("config.php.template")
    p.package_files.include("ChangeLog")
    p.package_files.include(".htaccess")
    p.package_files.include("NOTICE")
    p.package_files.include("README.md")
  end
  Rake::Task['package'].invoke
  rm_rf "logs"
end

# Integration testing tasks only make sense when vagrant is installed.
if env
  # Starts the VM. It will be created and provisioned if necessary.
  desc "Start the VM for integration testing"
  task :itest_up do |t|  
    puts "About to run vagrant-up..." 
    if File.exists?("tests/env/.vagrant") 
      env.cli("up --no-provision")
    else
      env.cli("up")
    end
    puts "Finished running vagrant-up"
  end

  # Just halts the VM so that it can be resumed later.
  desc "Halt the VM for integration testing"
  task :itest_down do |t|
    puts "About to run vagrant-halt..."  
    raise "Must run `vagrant up`" if !env.primary_vm.created?
    raise "Must be running!" if env.primary_vm.state != :running
    #env.primary_vm.channel.sudo("halt")
    env.cli("halt")
    puts "Finished running vagrant-halt"
  end

  desc "Deploy the current code for testing to the VM dirs"
  task :itest_deploy2 => [:package2] do |t|    
    code_target = "./tests/work/src"
    lib_target = "./tests/work/calibre"

    puts "Deploying code"
    rm_rf code_target+"/."
    cp_r "./pkg/#{APPNAME}-#{VERSION}/.", code_target
    puts "Deploying test fixtures"    
    rm_rf lib_target+"/."
    cp "tests/fixtures/metadata_empty.db", "#{lib_target}/metadata.db"
    cp "tests/fixtures/config.php", code_target
  end

  desc "Deploy the current code for testing, via rsync"
  task :itest_deploy => [:itest_deploy2] do |t|    

    code_target = "./tests/work/src"
    lib_target = "./tests/work/calibre"

    env.cli("sync","-f#{code_target}/", "-t/var/www/bbs")    
    env.cli("sync","-f#{lib_target}/","-t/tmp/calibre")    
  end

  desc "Starts a ssh shell in the VM"
  task :itest_shell do |t|
    env.cli("ssh")
  end
end

desc "Copy the current version to the NAS for testing"
task :copy2nas => [:package2] do |t|
	sh "rsync -rv pkg/#{APPNAME}-#{VERSION}/ /Volumes/web/bbs"
end

task :default => [:clobber, :package2]

