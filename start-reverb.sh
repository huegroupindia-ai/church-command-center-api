#!/bin/bash
cd "$(dirname "$0")"
nohup php artisan reverb:start --port=8080 > /tmp/reverb.log 2>&1 &
echo "Reverb started with PID: $!"
