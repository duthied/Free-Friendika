
server_ip_trusty = "192.168.22.10"
server_ip_xenial = "192.168.22.11"
server_memory = "1024" # MB
server_timezone = "UTC"

public_folder = "/vagrant"

Vagrant.configure(2) do |config|
  # Set server to Ubuntu 14.04
  config.vm.define "trusty" do |trusty|
    trusty.vm.box = "ubuntu/trusty64"

    # Disable automatic box update checking. If you disable this, then
    # boxes will only be checked for updates when the user runs
    # `vagrant box outdated`. This is not recommended.
    # config.vm.box_check_update = false

    # Create a hostname, don't forget to put it to the `hosts` file
    # This will point to the server's default virtual host
    # TO DO: Make this work with virtualhost along-side xip.io URL
    trusty.vm.hostname = "friendica-trusty.dev"

    # Create a static IP
    trusty.vm.network :private_network, ip: server_ip_trusty

    # Share a folder between host and guest
    trusty.vm.synced_folder "./", "/vagrant/", owner: "www-data", group: "vagrant"
  end

  # Set server to Ubuntu 16.04
  config.vm.define "xenial" do |xenial|
    xenial.vm.box = "ubuntu/xenial64"

    # Disable automatic box update checking. If you disable this, then
    # boxes will only be checked for updates when the user runs
    # `vagrant box outdated`. This is not recommended.
    # config.vm.box_check_update = false

    # Create a hostname, don't forget to put it to the `hosts` file
    # This will point to the server's default virtual host
    # TO DO: Make this work with virtualhost along-side xip.io URL
    xenial.vm.hostname = "friendica-xenial.dev"

    # Create a static IP
    xenial.vm.network :private_network, ip: server_ip_xenial

    # Share a folder between host and guest
    xenial.vm.synced_folder "./", "/vagrant/", owner: "www-data", group: "vagrant"
  end


  # Provider-specific configuration so you can fine-tune various
  # backing providers for Vagrant. These expose provider-specific options.
  config.vm.provider "virtualbox" do |vb|
  #   # Display the VirtualBox GUI when booting the machine
  #   vb.gui = true
  #
  #   # Customize the amount of memory on the VM:
      vb.memory = server_memory
  end

  # Enable provisioning with a shell script. 
  config.vm.provision "shell", path: "./util/vagrant_provision.sh"
    # run: "always"
    # run: "once"
end
