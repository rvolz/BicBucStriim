#-*- encoding:utf-8 ; mode:ruby -*-
#
# The Rakefile contains mainly packging tasks for the installation archives
#

require 'rake/clean'
require 'rake/packagetask'
#require 'cucumber'
#require 'cucumber/rake/task'
require 'fileutils'
require 'sqlite3'

APPNAME = 'BicBucStriim'
VERSION = '0.9.4'

begin
  require 'vagrant'
  # Needs also 'vagrant-sync' !
  # tests/env contains the integration testing environment with the Vagrantfile
  env = Vagrant::Environment.new(:cwd => "tests/env")
rescue LoadError
  env = nil
  puts STDERR, "*** Vagrant not installed. Integration testing tasks disabled. ***"
end

desc "Make a release package"
task :package2 do |t|
  mkdir "data"
  chmod 0777, "data"
  touch "data/data.db"
  chmod 0777, "data/data.db"
  db = SQLite3::Database.new "data/data.db"
  rows = db.execute <<-SQL
    create table configs (
      name varchar(30),
      val varchar(256)
    );    
  SQL
  {
    "admin_pw" => "",
    "calibre_dir" => "",
    "db_version" => "1",
    "glob_dl_choice" => "0",
    "glob_dl_password" => "7094e7dc2feb759758884333c2f4a6bdc9a16bb2",
    "thumb_gen_clipped" => "1",
    "page_size" => "30",
    "display_app_name" => "BicBucStriim",
  }.each do |pair|
    db.execute "insert into configs values ( ?, ? )", pair
  end
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
    p.package_files.include("vendor/composer/*.*")
    p.package_files.include("vendor/slim/slim/*.*")
    p.package_files.include("vendor/slim/extras/**/*.*")
    p.package_files.include("vendor/slim/slim/Slim/**/*.*")
    p.package_files.include("vendor/twig/twig/*.*")
    p.package_files.include("vendor/twig/twig/ext/**/*.*")
    p.package_files.include("vendor/twig/twig/lib/**/*.*")
    p.package_files.include("templates/**/*.*")
    p.package_files.include("data/**/*.*")
    p.package_files.include("index.php")
    p.package_files.include("installcheck.php")
    p.package_files.include("ChangeLog")
    p.package_files.include(".htaccess")
    p.package_files.include("NOTICE")
    p.package_files.include("LICENSE")
    p.package_files.include("README.md")

  end
  Rake::Task['package'].invoke
  rm_rf "data"
end

# Integration testing tasks only make sense when vagrant is installed.
if env
  # Starts the VM. It will be created and provisioned if necessary.
  desc "Start the VM for integration testing"
  task :itest_up do |t|  
    puts "About to run vagrant-up..." 
    env.cli("up")
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
    cp code_target+"/data/data.db", code_target+"/data/data.backup"
    chmod_R 0777,"#{code_target}/data"
    puts "Deploying test fixtures"    
    rm_rf lib_target+"/."
    cp_r "tests/fixtures/lib2/.", lib_target
    cp "tests/fixtures/config.php", code_target
  end

  desc "Deploy the current code for testing, via rsync"
  task :itest_deploy => [:itest_deploy2] do |t|    

    code_target = "./tests/work/src"
    lib_target = "./tests/work/calibre"

    env.cli("sync","-f#{code_target}/", "-t/var/www/bbs")    
    env.cli("sync","-f#{lib_target}/","-t/tmp/calibre")    
    # a backup db for reset during testing
    #env.cli("ssh", '-c "sudo cp /var/www/bbs/data/data.db /var/www/bbs/data/data.backup"')
  end

  desc "Starts a ssh shell in the VM"
  task :itest_shell do |t|
    env.cli("ssh")
  end
end


desc "Unit testing"
task :test do |t|
  sh "php tests/test_all.php"
end

desc "Integration testing (via integration test environment)"
task :itest => [:itest_deploy] do |t|  
  #sh "php tests/test_integration.php"
  sh "cucumber features --format=pretty"
end


# Cucumber::Rake::Task.new(:features) do |t|
#   t.cucumber_opts = "features --format pretty"
# end
# desc "Real Integration testing (via integration test environment)"
# task :features => [:itest_deploy]

desc "Copy the current version to the NAS for testing"
task :copy2nas => [:package2] do |t|
	sh "rsync -rv pkg/#{APPNAME}-#{VERSION}/ /Volumes/web/bbs"
end

task :default => [:clobber, :package2]

