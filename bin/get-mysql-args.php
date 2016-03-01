<?php

ini_set('display_errors', true);
error_reporting(-1);

function error($no, $str) {
    fwrite(STDERR, "$str\n");
    die(1);
}
    
//set_error_handler("error"); 

$config = $argv[1];
include "$config"; 
echo "-u $config_db_user -h $config_db_host -p$config_db_password $config_db_name";

