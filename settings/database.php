<?php
$servername = "db";
$username = "admin_eleave";
$password = "admin231";
$dbname = "eleaveup"; 
$port = "3306";
$prefix = "app";
$dbdriver = "mysql";

return array (
  'mysql' => 
  array (
    'dbdriver' => $dbdriver,
    'username' => $username,
    'password' => $password,
    'dbname' => $dbname,
    'prefix' => $prefix,
    'hostname' => $servername,
    'port' => $port,
  ),
  'tables' => 
  array (
    'category' => 'category',
    'language' => 'language',
    'leave' => 'leave',
    'leave_quota' => 'leave_quota',
    'leave_items' => 'leave_items',
    'logs' => 'logs',
    'shift' => 'shift',
    'shift_holidays' => 'shift_holidays',
    'shift_workdays' => 'shift_workdays',
    'user' => 'user',
    'user_meta' => 'user_meta',
  ),
);