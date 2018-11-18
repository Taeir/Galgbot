<?php

namespace Taeir\Vliegbot;


use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Exception\TelegramException;
use Spatie\Emoji\Emoji;

class Util {
    private static $config;
    private static $valid;

    /**
     * @return array
     *      the configuration
     */
    public static function getConfig(): array
    {
        if (static::$config === null) {
            static::$config = include(__DIR__ . '/../config.php');
            static::$valid = (static::$config !== false);
        }
        return static::$config;
    }

    /**
     * @return bool
     *      true if the config is valid, false otherwise
     */
    public static function isConfigValid(): bool
    {
        static::getConfig();
        return static::$valid;
    }


    /**
     * Formats a response with the correct emoji.
     *
     * @param string $word
     *      the word to guess
     * @param array $guessed
     *      an array of letters (strings) that have already been guessed
     * @param int $lives
     *      the number of lives remaining
     *
     * @return string
     *      the formatted message "Word: ...\nLives: ..."
     */
    public static function formatResponse(string $word, array $guessed, int $lives): string
    {
        $word_len = strlen($word);
        $word_part = '';
        for ($i = 0; $i < $word_len; $i++) {
            $letter = $word[$i];
            if (in_array($letter, $guessed)) {
                $word_part .= $letter;
            } else {
                $word_part .= Emoji::heavyMinusSign();
            }
        }

        //For the hearts
        $hearts = [Emoji::redHeart(), Emoji::yellowHeart(), Emoji::greenHeart(), Emoji::blueHeart(), Emoji::purpleHeart()];

        $lives_part = array_map(function ($i) use ($hearts): string {
            return $hearts[$i % count($hearts)];
        }, range(0, $lives - 1));

        return 'Woord: ' . $word_part . "\nLevens: " . implode($lives_part);
    }

    /**
     * Creates a keyboard with letters A-Z, with all letters that have already been guessed removed from the keyboard.
     *
     * @param array $guessed
     *      the letters that have already been guessed
     *
     * @return Keyboard
     *      the keyboard
     */
    public static function getKeyboard(array $guessed): Keyboard
    {
        //TODO Add placeholder keys in such a way that keys don't shift accross the keyboard. e.g. Emoji::whiteSmallSquare();

        $letters = array_diff(range('A', 'Z'), $guessed);
        $lines = array_chunk($letters, ceil(count($letters) / 3));

        try {
            return new Keyboard(['keyboard' => $lines, 'force_reply' => true, 'selective' => false]);
        } catch (TelegramException $e) {
            static::errMsg('Unable to create keyboard! ' . $e->getMessage());
        }
    }

    /**
     * Logs the given message to the debug log, if the settings permit it.
     *
     * @param string $msg
     *      the message to log
     */
    public static function logMsg(string $msg)
    {
        if (static::getConfig()['log_debug'] === true) {
            file_put_contents(static::getConfig()['log_location'] . '/debug.log', $msg . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
    }

    /**
     * Logs the given message to the error log, if the settings permit it.
     *
     * @param string $msg
     *      the message to log
     */
    public static function errMsg(string $msg)
    {
        if (static::getConfig()['log_errors'] === true) {
            file_put_contents(static::getConfig()['log_location'] . '/error.log', $msg . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
    }
}