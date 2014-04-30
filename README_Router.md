Router
======

Hardware: TP-Link TL-WDR4900
Software: OpenWRT (http://downloads.openwrt.org/snapshots/trunk/mpc85xx/)
http://wiki.openwrt.org/toh/tp-link/tl-wdr4900


Initial setup and how to reset
------------------------------

1. Press "reset" for a while
2. Connect to router on LAN port
3. telnet 192.168.1.1
4. passwd -> set a root password, log out.
5. Connect router to internet on WAN port
6. Login via ssh.
7. opkg update, then opkg install luci-ssl (wiki.openwrt.org/doc/howto/luci.essentials)
8. /etc/init.d/uhttpd start, then /etc/init.d/uhttpd enable (for starting luci after reboot)
9. https://192.168.1.1 (or ssh -L 8080:localhost:80 root@192.168.1.1)
1O. Eventually upload backup archive.

Config for Datentankstelle
--------------------------

* ssh login with key only
* WAN port: static IP, connected to DTS
* on LAN ports / wifi devices get address via dhcp
* dual band wifi
