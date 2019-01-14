echo "Ultrasonic"
( echo -n "get:ultrasonic_distance" | nc -U /tmp/sensor_server; echo "" )

echo "Infrared"
( echo -n "get:infrared_distance" | nc -U /tmp/sensor_server; echo "" )

