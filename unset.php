<?php
/**
 * README
 * This file is intended to unset the webhook.
 * Uncommented parameters must be filled
 */
// Load composer
require_once __DIR__ . '/vendor/autoload.php';

$configs = include('config.php');
if ($configs === false) {
    die('Unable to find config file!');
}

try {
    // Create Telegram API object
    $telegram = new Longman\TelegramBot\Telegram($configs['api_key'], $configs['username']);
    // Delete webhook
    $result = $telegram->deleteWebhook();
    if ($result->isOk()) {
        echo $result->getDescription();
    }
} catch (Longman\TelegramBot\Exception\TelegramException $e) {
    echo $e->getMessage();
}