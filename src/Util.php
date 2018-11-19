<?php

namespace Taeir\Vliegbot;


use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Exception\TelegramException;
use Spatie\Emoji\Emoji;

class Util {
    private static $config;
    private static $lang;

    /**
     * @return array
     *      the configuration
     */
    public static function getConfig(): array
    {
        if (static::$config === null) {
            static::$config = include(__DIR__ . '/../config.php');
        }
        return static::$config;
    }

    /**
     * Retrieves the given key from the config, and returns the result.
     *
     * @param string $key
     * @return mixed
     */
    public static function config(string $key)
    {
        if (static::$config === null) {
            static::$config = include(__DIR__ . '/../config.php');
        }
        return static::$config[$key];
    }

    /**
     * @return bool
     *      true if the config is valid, false otherwise
     */
    public static function isConfigValid(): bool
    {
        return (static::getConfig() !== false);
    }

    /**
     * @param string $key
     *      the key to request
     *
     * @return string
     *      the translation for the given key
     */
    public static function getLang(string $key): string
    {
        if (static::$lang === null) {
            static::$lang = include(static::config('languages_path') . '/' . static::config('language') . '.php');
        }
        return static::$lang[$key];
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
        $word = static::normalizeAccents($word);
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

        return static::getLang('Word')  . ': ' . $word_part . "\n"
             . static::getLang('Lives') . ': ' . implode($lives_part);
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
            return null;
        }
    }

    /**
     * Gets the correct number of lives for the given word.
     * The number of lives may depend on the difficulty of the word in some way.
     *
     * @param string $word
     *      the word
     *
     * @return int
     *      the number of lives
     */
    public static function getLives(string $word): int
    {
        $unique = count(array_unique(str_split(self::normalizeAccents($word))));
        return static::config('lives')[$unique];
    }

    /**
     * Turns all accented characters in the given string into their unaccented variants.
     *
     * @param string $string
     *      the string to normalize
     *
     * @return string
     *      the normalized string
     */
    public static function normalizeAccents(string $string)
    {
        if ( !preg_match('/[\x80-\xff]/', $string) )
            return $string;

        $chars = array(
            // Decompositions for Latin-1 Supplement
            chr(195).chr(128) => 'A', chr(195).chr(129) => 'A',
            chr(195).chr(130) => 'A', chr(195).chr(131) => 'A',
            chr(195).chr(132) => 'A', chr(195).chr(133) => 'A',
            chr(195).chr(135) => 'C', chr(195).chr(136) => 'E',
            chr(195).chr(137) => 'E', chr(195).chr(138) => 'E',
            chr(195).chr(139) => 'E', chr(195).chr(140) => 'I',
            chr(195).chr(141) => 'I', chr(195).chr(142) => 'I',
            chr(195).chr(143) => 'I', chr(195).chr(145) => 'N',
            chr(195).chr(146) => 'O', chr(195).chr(147) => 'O',
            chr(195).chr(148) => 'O', chr(195).chr(149) => 'O',
            chr(195).chr(150) => 'O', chr(195).chr(153) => 'U',
            chr(195).chr(154) => 'U', chr(195).chr(155) => 'U',
            chr(195).chr(156) => 'U', chr(195).chr(157) => 'Y',
            chr(195).chr(159) => 's', chr(195).chr(160) => 'a',
            chr(195).chr(161) => 'a', chr(195).chr(162) => 'a',
            chr(195).chr(163) => 'a', chr(195).chr(164) => 'a',
            chr(195).chr(165) => 'a', chr(195).chr(167) => 'c',
            chr(195).chr(168) => 'e', chr(195).chr(169) => 'e',
            chr(195).chr(170) => 'e', chr(195).chr(171) => 'e',
            chr(195).chr(172) => 'i', chr(195).chr(173) => 'i',
            chr(195).chr(174) => 'i', chr(195).chr(175) => 'i',
            chr(195).chr(177) => 'n', chr(195).chr(178) => 'o',
            chr(195).chr(179) => 'o', chr(195).chr(180) => 'o',
            chr(195).chr(181) => 'o', chr(195).chr(182) => 'o',
            chr(195).chr(182) => 'o', chr(195).chr(185) => 'u',
            chr(195).chr(186) => 'u', chr(195).chr(187) => 'u',
            chr(195).chr(188) => 'u', chr(195).chr(189) => 'y',
            chr(195).chr(191) => 'y',
            // Decompositions for Latin Extended-A
            chr(196).chr(128) => 'A', chr(196).chr(129) => 'a',
            chr(196).chr(130) => 'A', chr(196).chr(131) => 'a',
            chr(196).chr(132) => 'A', chr(196).chr(133) => 'a',
            chr(196).chr(134) => 'C', chr(196).chr(135) => 'c',
            chr(196).chr(136) => 'C', chr(196).chr(137) => 'c',
            chr(196).chr(138) => 'C', chr(196).chr(139) => 'c',
            chr(196).chr(140) => 'C', chr(196).chr(141) => 'c',
            chr(196).chr(142) => 'D', chr(196).chr(143) => 'd',
            chr(196).chr(144) => 'D', chr(196).chr(145) => 'd',
            chr(196).chr(146) => 'E', chr(196).chr(147) => 'e',
            chr(196).chr(148) => 'E', chr(196).chr(149) => 'e',
            chr(196).chr(150) => 'E', chr(196).chr(151) => 'e',
            chr(196).chr(152) => 'E', chr(196).chr(153) => 'e',
            chr(196).chr(154) => 'E', chr(196).chr(155) => 'e',
            chr(196).chr(156) => 'G', chr(196).chr(157) => 'g',
            chr(196).chr(158) => 'G', chr(196).chr(159) => 'g',
            chr(196).chr(160) => 'G', chr(196).chr(161) => 'g',
            chr(196).chr(162) => 'G', chr(196).chr(163) => 'g',
            chr(196).chr(164) => 'H', chr(196).chr(165) => 'h',
            chr(196).chr(166) => 'H', chr(196).chr(167) => 'h',
            chr(196).chr(168) => 'I', chr(196).chr(169) => 'i',
            chr(196).chr(170) => 'I', chr(196).chr(171) => 'i',
            chr(196).chr(172) => 'I', chr(196).chr(173) => 'i',
            chr(196).chr(174) => 'I', chr(196).chr(175) => 'i',
            chr(196).chr(176) => 'I', chr(196).chr(177) => 'i',
            chr(196).chr(178) => 'IJ',chr(196).chr(179) => 'ij',
            chr(196).chr(180) => 'J', chr(196).chr(181) => 'j',
            chr(196).chr(182) => 'K', chr(196).chr(183) => 'k',
            chr(196).chr(184) => 'k', chr(196).chr(185) => 'L',
            chr(196).chr(186) => 'l', chr(196).chr(187) => 'L',
            chr(196).chr(188) => 'l', chr(196).chr(189) => 'L',
            chr(196).chr(190) => 'l', chr(196).chr(191) => 'L',
            chr(197).chr(128) => 'l', chr(197).chr(129) => 'L',
            chr(197).chr(130) => 'l', chr(197).chr(131) => 'N',
            chr(197).chr(132) => 'n', chr(197).chr(133) => 'N',
            chr(197).chr(134) => 'n', chr(197).chr(135) => 'N',
            chr(197).chr(136) => 'n', chr(197).chr(137) => 'N',
            chr(197).chr(138) => 'n', chr(197).chr(139) => 'N',
            chr(197).chr(140) => 'O', chr(197).chr(141) => 'o',
            chr(197).chr(142) => 'O', chr(197).chr(143) => 'o',
            chr(197).chr(144) => 'O', chr(197).chr(145) => 'o',
            chr(197).chr(146) => 'OE',chr(197).chr(147) => 'oe',
            chr(197).chr(148) => 'R', chr(197).chr(149) => 'r',
            chr(197).chr(150) => 'R', chr(197).chr(151) => 'r',
            chr(197).chr(152) => 'R', chr(197).chr(153) => 'r',
            chr(197).chr(154) => 'S', chr(197).chr(155) => 's',
            chr(197).chr(156) => 'S', chr(197).chr(157) => 's',
            chr(197).chr(158) => 'S', chr(197).chr(159) => 's',
            chr(197).chr(160) => 'S', chr(197).chr(161) => 's',
            chr(197).chr(162) => 'T', chr(197).chr(163) => 't',
            chr(197).chr(164) => 'T', chr(197).chr(165) => 't',
            chr(197).chr(166) => 'T', chr(197).chr(167) => 't',
            chr(197).chr(168) => 'U', chr(197).chr(169) => 'u',
            chr(197).chr(170) => 'U', chr(197).chr(171) => 'u',
            chr(197).chr(172) => 'U', chr(197).chr(173) => 'u',
            chr(197).chr(174) => 'U', chr(197).chr(175) => 'u',
            chr(197).chr(176) => 'U', chr(197).chr(177) => 'u',
            chr(197).chr(178) => 'U', chr(197).chr(179) => 'u',
            chr(197).chr(180) => 'W', chr(197).chr(181) => 'w',
            chr(197).chr(182) => 'Y', chr(197).chr(183) => 'y',
            chr(197).chr(184) => 'Y', chr(197).chr(185) => 'Z',
            chr(197).chr(186) => 'z', chr(197).chr(187) => 'Z',
            chr(197).chr(188) => 'z', chr(197).chr(189) => 'Z',
            chr(197).chr(190) => 'z', chr(197).chr(191) => 's'
        );

        $string = strtr($string, $chars);

        return $string;
    }

    /**
     * Logs the given message to the debug log, if the settings permit it.
     *
     * @param string $msg
     *      the message to log
     */
    public static function logMsg(string $msg)
    {
        if (static::config('log_debug') === true) {
            file_put_contents(static::config('log_location') . '/debug.log', $msg . PHP_EOL, FILE_APPEND | LOCK_EX);
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
        if (static::config('log_errors') === true) {
            file_put_contents(static::config('log_location') . '/error.log', $msg . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
    }

    public static function logStats($chat_id, $user_id, bool $win)
    {
        file_put_contents(static::config('log_location') . '/stats.log',
            'c: ' . $chat_id . ' u: ' . $user_id . ' w: ' . $win . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}