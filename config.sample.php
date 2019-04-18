<?php
$config = array();
$config['dbserver'] = '';
$config['dbuser'] = '';
$config['dbpassword'] = '';
$config['dbport'] = 3306;
$config['dbname'] = '';
$config['mail'] = '';
$config['token'] = '';
$config['url'] = 'https://api.telegram.org/bot' . $config['token'] . '/';

$commands = array();
$commands['/start']['de'] = 'Hello! I\'m the Summerbo.at Bot.
To get a command overview, send /help.';