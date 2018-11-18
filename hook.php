<?php
// Load composer
require __DIR__ . '/vendor/autoload.php';

$config = include('config.php');
if ($config === false) {
    die('Unable to find config file!');
}

try {
    // Create Telegram API object
    $telegram = new Longman\TelegramBot\Telegram($config['api_key'], $config['username']);

    $telegram->addCommandsPath($config['commands_path']);

    if ($config['log_errors'] === true) {
        Longman\TelegramBot\TelegramLog::initErrorLog($config['log_location'] . "/{$config['username']}_error.log");
    }
    if ($config['log_debug'] === true) {
        Longman\TelegramBot\TelegramLog::initDebugLog($config['log_location'] . "/{$config['username']}_debug.log");
        Longman\TelegramBot\TelegramLog::initUpdateLog($config['log_location'] . "/{$config['username']}_update.log");
    }
    
    $telegram->enableMySql($config['mysql']);

    //Note: the limiter can only be enabled if the database is enabled
    $telegram->enableLimiter();

    if ($config['enable_webhook'] === true) {
        $telegram->handle();
    } else {
        while (true) {
            $telegram->handleGetUpdates();
        }
    }
} catch (Longman\TelegramBot\Exception\TelegramException $e) {
    Longman\TelegramBot\TelegramLog::error($e);
} catch (Longman\TelegramBot\Exception\TelegramLogException $e) {
    //Logging went wrong, just swallow the exception in this case.
}
