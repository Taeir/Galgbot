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
    //The number of lives for each game, based on the number of unique letters in a word
    'lives' => [
        1  => 12,
        2  => 12,
        3  => 12,
        4  => 10,
        5  => 10,
        6  => 9,
        7  => 9,
        8  => 8,
        9  => 7,
        10 => 7,
        11 => 6,
        12 => 6,
        13 => 5,
        14 => 5,
        15 => 4,
        16 => 4,
        17 => 4,
        18 => 3,
        19 => 3,
        20 => 3,
        21 => 2,
        22 => 2,
        23 => 2,
        24 => 2,
        25 => 2,
        26 => 1,
    ],

    //The location where dictionaries are (language.txt)
    'dictionaries_path' => __DIR__ . '/Dictionaries',

    //The location where language files are (language.php)
    'languages_path'    => __DIR__ . '/Languages',

    //The language to use
    'language'          => 'dutch',
];
