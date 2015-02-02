# -*- coding: utf-8 -*-

dir = File.expand_path('../', __FILE__)
bin = File.join(dir, '/vendor/bin')

composer = "#{bin}/composer.phar"
phpunit  = "#{bin}/phpunit"

report = "#{dir}/build/report"
report_coverage = "#{report}/coverage"

log_coverage = "build/logs/clover.xml"

task :default => %(all)

desc 'Run `doc`, `test` task'
task :all => %w(setup doc test)

desc 'Setup application'
task :setup => %w(vendor:setup composer:setup composer:install)

desc 'Generate HTML document'
task :doc do
  sh "#{bin}/phpdox"
end

desc 'Run unit test'
task :test => %(setup)
task :test do
  if IO.popen('php -i', ?r).readlines.grep(/xdebug/).any?
    t = 'test:coverage'
  else
    t = 'test:default'
  end

  Rake::Task[t].invoke
end

namespace :test do
  task :default do
    sh phpunit
  end

  desc 'Run unit test and generate code coverage'
  task :coverage do
    FileUtils.mkdir_p(report_coverage) unless FileTest.directory?(report_coverage)
    sh "#{phpunit} --coverage-clover=#{log_coverage} --coverage-html=#{report_coverage}"
  end
end

task :phploc do
  sh "#{bin}/phploc ./src/ --log-xml ./build/logs/phploc.xml"
end

desc 'Run composer'
task :composer => %w(composer:setup composer:update)

namespace :vendor do
  task :setup do
    FileUtils.mkdir_p(bin) unless FileTest.directory?(bin)
  end
end

namespace :composer do
  desc 'Setup composer'
  task :setup do
    unless FileTest.file?(composer)
      sh "curl -sS https://getcomposer.org/installer | " +
         "php -- --install-dir=#{bin}"
    end
  end

  desc 'Run composer install'
  task :install do
    unless FileTest.file?(phpunit)
      sh "#{composer} install"
    end
  end

  desc 'Update composer'
  task :update => %(composer:install)
  task :update do
    sh "#{composer} self-update"
  end
end
