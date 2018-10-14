# Eve-KKIK-docker
Enabling docker in the community updated version of Eve.

Add docker to community version of eve community version based upon 2.0.3-90 and known as version 1.71. uninstall docker-enginer, docker.io if installed. Install Docker-ce as per https://docs.docker.com/install/linux/docker-ce/ubuntu/#set-up-the-repository.

Create /etc/systemd/system/docker.service.d/override.conf with the contents below: [Service] ExecStart= ExecStart=/usr/bin/dockerd -H unix://var/run/docker.sock -H tcp://127.0.0.1:4243

This should enable docker within eve and enable console access to load images to use.

Applications in containers can be downloaded from the Hub and then be used in eve itself.

These are the changed files to make docker function. IP Addresses of the interfaces are set by using nsenter on the container. If you do not assign an IP address then when you start a container the container will never enter run mode. The icon on the container will stay as a clock face. The only way to recover from this is to wipe the node, configure the IP address and then start.

The container IP address must be part of the startup configuration. Check the eve-ng pro cookbook on the correct format. If you have not got a play triangle against the image in the eve lab it means that you have not set the IP address correctly as it uses nsenter to set the ip address and the success of this removes the lock file enabling the play symbol. If you start a device without the correct settings you need to wipe the device before trying again.

The docker options box on the template should typically be used to set environmental variables rather than the ip address. I found a number of containerise network functions that required environmental variables to be presented. so use '-e ENVIRONMENTAL_VARIABLE' and it gets passed into the create command.

Copy files to the appropriate directory in /opt/unetlab should be releatively self explanatory.

And lastly disable iptables on the bridge on eve-ng by:
cat 0 > /proc/sys/net/bridge/bridge-nf-call-iptables

Of course if you set up a docker container and link them and it does not work then you can always check on eve-ng host. Each link is a ethernet bridge and each interface should be attached to that bridge. So a single link between 2 devices you should see a new bridge (VNET) and 2 virtual ethernet interfaces (VUNL) connected to the bridge. Use:
$>brctl show
to see the bridging.

I've just tried ovs openflow switch made with the following dockerfile that sets 16 ethernet interfaces. The eve-ng template you need to set 16 ethernet interfaces. 

For boot.sh see below from GNSS3 respository. Don't forget to chmod 755 this and then build with $>docker build -t openvswitch29 .

Once built you can select in the eve-ng template and save. Then create new start up configs that set the ip address.

ip addr add 172.22.7.101/24 dev eth0
ip route add default via 172.22.7.1

Best of luck. If you really want you can create a linux image of OpenDayLight and control the openflow switches.

