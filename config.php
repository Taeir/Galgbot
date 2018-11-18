<?php
return [
    //=============================================================================================
    //Telegram
    //=============================================================================================
    //Telegram Bot API Key
    'api_key'        => '',

    //Telegram Bot Username
    'username'       => '',

    //Enable the webhook
    'enable_webhook' => false,

    //URL for the hook.php
    'hook_url'       => '',

    //=============================================================================================
    //MySQL
    //=============================================================================================
    'mysql'        => [
        'host'     => 'localhost',
        'user'     => '',
        'password' => '',
        'database' => 'galgbot',
    ],

    //=============================================================================================
    //Folders
    //=============================================================================================
    //Path of the Commands folder
    'commands_path' => __DIR__ . '/Commands',

    //=============================================================================================
    //Logging
    //=============================================================================================
    //Enable error logging
    'log_errors'    => true,

    //Enable debug logging
    'log_debug'     => true,

    //Location of the log files
    'log_location'  => __DIR__ . '/logs',

    //=============================================================================================
    //Game
    //=============================================================================================
    //The number of lives for each game
    'lives'             => 10,

    //The location where dictionaries are (language.txt)
    'dictionaries_path' => __DIR__ . '/Dictionaries',

    //The location where language files are (language.php)
    'languages_path'    => __DIR__ . '/Languages',

    //The language to use
    'language'          => 'dutch',
];
