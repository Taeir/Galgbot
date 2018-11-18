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

    // Set webhook
    if ($config['enable_webhook'] === true) {
        $result = $telegram->setWebhook($config['hook_url']);
        if ($result->isOk()) {
            echo $result->getDescription();
        }
    }
} catch (Longman\TelegramBot\Exception\TelegramException $e) {
    // Log telegram errors
    echo $e->getMessage();
}