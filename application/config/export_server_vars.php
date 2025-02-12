<?php
// Export $_SERVER variables to a file
$file = 'server_vars.json';
$serverVars = $_SERVER;
file_put_contents($file, json_encode($serverVars, JSON_PRETTY_PRINT));
echo "Server variables exported to $file\n";