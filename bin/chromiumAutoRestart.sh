#!/bin/bash
while true;
	do chromium-browser %u --kiosk --start-fullscreen --disable-pinch
	sleep 5s;
done

