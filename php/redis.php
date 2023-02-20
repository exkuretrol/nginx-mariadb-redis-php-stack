<?php
//Connecting to Redis server on localhost
$redis = new \Redis();
$redis->connect('redis', 6379);
$redis->auth('W626RCy@LhsWRqyDW3U*!Q!PSLNrSjt6');

echo $redis->get("test");
echo "Connection to server sucessfully";
//check whether server is running or not
echo $redis->ping();
