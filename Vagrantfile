
server_ip = "192.168.22.10"
server_memory = "2048" # MB
server_timezone = "UTC"

public_folder = "/vagrant"

Vagrant.configure(2) do |config|
  # Set server to Debian 10 / Buster 64bit
  config.vm.box = "debian/buster64"

  # Disable automatic box update checking. If you disable this, then
  # boxes will only be checked for updates when the user runs
  # `vagrant box outdated`. This is not recommended.
  config.vm.box_check_update = true

  # Create a hostname, don't forget to put it to the `hosts` file
  # This will point to the server's default virtual host
  # TO DO: Make this work with virtualhost along-side xip.io URL
  config.vm.hostname = "friendica.local"

  # Create a static IP
  config.vm.network :private_network, ip: server_ip

  # Share a folder between host and guest
  # config.vm.synced_folder ".", "/vagrant", id: "vagrant-root", owner: "www-data", group: "vagrant"
  config.vm.synced_folder ".", "/vagrant", id: "vagrant-root", owner: "www-data", group: "www-data"

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
  config.vm.provision "shell", path: "./bin/dev/vagrant_provision.sh"
    # run: "always"
    # run: "once"
end
