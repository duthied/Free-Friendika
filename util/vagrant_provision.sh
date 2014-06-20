#!/bin/bash
#Script to setup the vagrant instance for running friendica
#
#DO NOT RUN on your physical machine as this won't be of any use 
#and f.e. deletes your /var/www/ folder!

#make the vagrant directory the docroot
rm -rf /var/www/
ln -fs /vagrant /var/www

#create the friendica database
echo "create database friendica" | mysql -u root -proot

#create cronjob
echo "*/10 * * * * cd /vagrant; /usr/bin/php include/poller.php" >> friendicacron
crontab friendicacron
rm friendicacron

#Optional: checkout addon repository
#git clone https://github.com/friendica/friendica-addons.git /vagrant/addon