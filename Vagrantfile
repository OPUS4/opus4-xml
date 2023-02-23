# -*- mode: ruby -*-
# vi: set ft=ruby :

$software = <<SCRIPT
# PHP 8.1
apt-add-repository -y ppa:ondrej/php
apt-get -yq update
apt-get -yq install php8.1

# Install MYSQL
debconf-set-selections <<< "mysql-server mysql-server/root_password password root"
debconf-set-selections <<< "mysql-server mysql-server/root_password_again password root"
apt-get -yq install mysql-server

# Install required PHP packages
apt-get -yq install php8.1-curl
apt-get -yq install php8.1-zip
apt-get -yq install php8.1-dom
apt-get -yq install php8.1-mysql

# Install required tools
apt-get -yq install ant
apt-get -yq install unzip
SCRIPT

$composer = <<SCRIPT
cd /vagrant
bin/install-composer.sh
bin/composer update
SCRIPT

$workspace = <<SCRIPT
cd /vagrant
ant prepare-workspace
SCRIPT

$database = <<SCRIPT
/vagrant/vendor/bin/opus4db --adminpwd root --userpwd root --sqlpwd root
SCRIPT

$environment = <<SCRIPT
if ! grep "cd /vagrant" /home/vagrant/.profile > /dev/null; then
  echo "cd /vagrant" >> /home/vagrant/.profile
fi
if ! grep "PATH=/vagrant/bin" /home/vagrant/.bashrc > /dev/null; then
  echo "export PATH=/vagrant/bin:$PATH" >> /home/vagrant/.bashrc
fi
SCRIPT

$help = <<SCRIPT
echo "Use 'vagrant ssh' to log into VM and 'logout' to leave it."
echo "In VM use:"
echo "'composer test' for running tests"
echo "'composer update' to update dependencies"
echo "'composer cs-check' to check coding style"
echo "'composer cs-fix' to automatically fix basic style problems"
SCRIPT

Vagrant.configure("2") do |config|
  config.vm.box = "bento/ubuntu-22.04"

  config.vm.provision "Install required software...", type: "shell", inline: $software
  config.vm.provision "Install Composer dependencies...", type: "shell", privileged: false, inline: $composer
  config.vm.provision "Create workspace...", type: "shell", privileged: false, inline: $workspace
  config.vm.provision "Create database...", type: "shell", inline: $database
  config.vm.provision "Setup environment...", type: "shell", inline: $environment
  config.vm.provision "Information", type: "shell", privileged: false, run: "always", inline: $help
end
