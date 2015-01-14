Vagrant for Friendica Developers
===================

* [Home](help)

[Vagrant](https://www.vagrantup.com/) is a virtualization solution for developers. No need to setup up a webserver, database etc. before actually starting. Vagrant creates a virtual machine (an Ubuntu 12.04) for you that you can just run inside VirtualBox and start to work directly on Friendica. What you need to do:

1. Install VirtualBox and vagrant.
2. Git clone your Friendica repository. Inside, you'll find a "Vagrantfile" and some scripts in the utils folder.
3. Run "vagrant up" from inside the friendica clone. Be patient: When it runs for the first time, it downloads an Ubuntu Server image.
4. Run "vagrant ssh" to log into the virtual machine to log in to the VM.
5. Open 192.168.22.10 in a browser to finish the Friendica installation. The mysql database is called "friendica", the mysql user and password both are "root".
6. Work on Friendica's code in your git clone on your machine (not in the VM).
7. Check the changes in your browser in the VM. Debug via the "vagrant ssh" login.
8. Commit and push your changes directly back to Github.
