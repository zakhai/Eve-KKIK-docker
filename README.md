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
to see the brdging.

I've just tried ovs openflow switch made with the following dockerfile that sets 16 ethernet interfaces. The eve-ng template you need to set 16 ethernet interfaces. 

Dockerfile
FROM alpine:edge
RUN apk update && apk add openvswitch=2.9.2-r0 && rm -rf /var/cache/apk/*
VOLUME /etc/openvswitch/
ADD boot.sh /bin/boot.sh
CMD /bin/sh /bin/boot.sh

For boot.sh see below from GNSS3 respository. Don't forget to chmod 755 this and then build with $>docker build -t openvswitch29 .

Once built you can select in the eve-ng template and save. Then create new start up configs that set the ip address.

ip addr add 172.22.7.101/24 dev eth0
ip route add default via 172.22.7.1

Best of luck. If you really want you can create a linux image of OpenDayLight and control the openflow switches.

#!/bin/sh
#
# Copyright (C) 2015 GNS3 Technologies Inc.
#
# This program is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 3 of the License, or
# (at your option) any later version.
#
# This program is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with this program.  If not, see <http://www.gnu.org/licenses/>.

export PATH=$PATH:/usr/share/openvswitch/scripts/

if [ ! -f "/etc/openvswitch/conf.db" ]
then
  ovs-ctl start 
  x=0
  until [ $x = "4" ]; do
    ovs-vsctl add-br br$x
    ovs-vsctl set bridge br$x datapath_type=netdev
    x=$((x+1))
  done
  if [ $MANAGEMENT_INTERFACE == 1 ]
  then
    x=1
  else
    x=0
  fi
  until [ $x = "16" ]; do
    ovs-vsctl add-port br0 eth$x
    x=$((x+1))
  done
else
  ovs-ctl start
fi
x=0
until [ $x = "4" ]; do
  ip link set dev br$x up
  x=$((x+1))
done
/bin/sh
