
server_ip             = "192.168.22.10"
server_memory         = "384" # MB
server_timezone       = "UTC"

public_folder         = "/vagrant" 

Vagrant.configure("2") do |config|

  # Set server to Ubuntu 12.04
  config.vm.box = "precise64"

  config.vm.box_url = "http://files.vagrantup.com/precise64.box"

  # Create a hostname, don't forget to put it to the `hosts` file
  # This will point to the server's default virtual host
  # TO DO: Make this work with virtualhost along-side xip.io URL
  config.vm.hostname = "friendica.dev"

  # Create a static IP
  config.vm.network :private_network, ip: server_ip

  # If using VirtualBox
  config.vm.provider :virtualbox do |vb|

    # Set server memory
    vb.customize ["modifyvm", :id, "--memory", server_memory]

    # Set the timesync threshold to 10 seconds, instead of the default 20 minutes.
    # If the clock gets more than 15 minutes out of sync (due to your laptop going
    # to sleep for instance, then some 3rd party services will reject requests.
    vb.customize ["guestproperty", "set", :id, "/VirtualBox/GuestAdd/VBoxService/--timesync-set-threshold", 10000]

    # Prevent VMs running on Ubuntu to lose internet connection
    vb.customize ["modifyvm", :id, "--natdnshostresolver1", "on"]
    vb.customize ["modifyvm", :id, "--natdnsproxy1", "on"]

  end

  # If using VMWare Fusion
  config.vm.provider "vmware_fusion" do |vb, override|
    override.vm.box_url = "http://files.vagrantup.com/precise64_vmware.box"
    
    # Set server memory
    vb.vmx["memsize"] = server_memory

  end

  
  ####
  # Local Scripts
  # Any local scripts you may want to run post-provisioning.
  # Add these to the same directory as the Vagrantfile.
  ##########

  config.vm.synced_folder "./", "/vagrant/", :owner=> 'www-data', :group=>'vagrant', :mount_options => ['dmode=775', 'fmode=775']
  config.vm.provision "shell", path: "./util/vagrant_provision.sh" 

end
