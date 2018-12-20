<?php
namespace Longman\TelegramBot\Commands\SystemCommands;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Commands\SystemCommand;
use Spatie\Emoji\Emoji;
use Taeir\Galgbot\Util;

/**
 * Generic message command
 */
class GenericmessageCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'genericmessage';
    /**
     * @var string
     */
    protected $description = 'Handle generic message';
    /**
     * @var string
     */
    protected $version = '1.1.0';
    
    /**
     * @var bool
     */
    protected $need_mysql = true;

    /**
     * Conversation Object
     *
     * @var \Longman\TelegramBot\Conversation
     */
    protected $conversation;
    
    /**
     * Execute command
     *
     * @return \Longman\TelegramBot\Entities\ServerResponse
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute()
    {
        if (!Util::isConfigValid()) {
            return $this->sendReply('Config file not found!');
        }

        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();
        $letter  = strtoupper(trim($message->getText()));

        //TODO Allow full word guesses for 1 life?
        //Ignore messages which contain more than one letter or that do not consist of only A-Z.
        if (strlen($letter) != 1 || !preg_match('/[A-Z]/', $letter)) {
            return Request::emptyResponse();
        }

        //Get the conversation for a fixed user id, since the conversation must be shared between users.
        $this->conversation = new Conversation(
            65961880,
            $chat_id,
            "game"
        );

        $notes = &$this->conversation->notes;

        //There is no game in progress
        if (!$this->conversation->exists() || !is_array($notes) || empty($notes)) {
            return Request::emptyResponse();
        }

        !is_array($notes['guessed']) && $notes['guessed'] = [];

        //This letter was already guessed
        if (in_array($letter, $notes['guessed'])) {
            return Request::emptyResponse();
        }

        $positions = $this->strpos_all(Util::normalizeAccents($notes['word']), $letter);
        $notes['guessed'][] = $letter;
        if (count($positions) === 0) {
            $notes['lives']--;
            $guess_part = Util::getLang('wrong_guess') . "\n";
        } else {
            $guess_part = Util::getLang('correct_guess') . "\n";
        }

        $data = [
            'chat_id' => $chat_id,
            'reply_to_message_id' => $message->getMessageId(),
        ];

        //Check word guessed
        if ($notes['lives'] === 0) {
            $notes['won'] = false;
            $this->conversation->update();

            $data['text'] = $guess_part . "\n"
                            . $this->resultText($notes['word'], false);
            $data['reply_markup'] = Keyboard::remove();
            $data['parse_mode'] = 'Markdown';
            $data['disable_web_page_preview'] = true;

            $this->conversation->stop();

            Util::logStats($chat_id, $message->getFrom()->getId(), false);
        } else if ($this->checkWon($notes['word'], $notes['guessed'])) {
            $notes['won'] = true;
            $this->conversation->update();

            $data['text'] = $this->resultText($notes['word'], true);
            $data['reply_markup'] = Keyboard::remove();
            $data['parse_mode'] = 'Markdown';
            $data['disable_web_page_preview'] = true;

            $this->conversation->stop();

            Util::logStats($chat_id, $message->getFrom()->getId(), true);
        } else {
            $data['text'] = $guess_part . Util::formatResponse($notes['word'], $notes['guessed'], $notes['lives']);
            $data['reply_markup'] = Util::getKeyboard($notes['guessed']);
        }

        $this->conversation->update();

        return Request::sendMessage($data);
    }

    /**
     * @param string $word
     *      the word
     * @param array $guessed
     *      the guesses made
     *
     * @return bool
     *      true if the game has been won, false otherwise
     */
    private function checkWon(string $word, array $guessed)
    {
        return count(array_diff(str_split(Util::normalizeAccents($word)), $guessed)) === 0;
    }

    /**
     * Generates the result text.
     *
     * @param string $word
     *      the word
     * @param bool $won
     *      if the game was won or not
     *
     * @return string
     *      the result text
     */
    private function resultText(string $word, bool $won): string
    {
        if ($won) {
            return Util::getLang('game_won') . ' ' . Emoji::partyPopper() . "!\n"
                . Util::getLang('the_word_was') . "\"$word\"\n"
                . '[' . Util::getLang('definition_text') . '](' . $this->getDefinitionUrl($word) . ")\n"
                . Util::getLang('play_again') . ' /start';
        } else {
            return Util::getLang('game_lost') . '! ' . Emoji::pensiveFace() . "\n"
                . Util::getLang('the_word_was') . "\"$word\"\n"
                . '[' . Util::getLang('definition_text') . '](' . $this->getDefinitionUrl($word) . ")\n"
                . Util::getLang('play_again') . ' /start';
        }
    }

    /**
     * Gets a url with a definition for the given word. The given url does not 404 on the requested page.
     *
     * @param string $word
     *      the word
     *
     * @return string
     *      the url
     */
    private function getDefinitionUrl(string $word): string
    {
        $word = urlencode(mb_strtolower($word));
        foreach (Util::getLang('definition_url') as $base_url) {
            $url = $base_url . $word;
            try {
                $client = new Client([
                    'base_uri' => $base_url
                ]);

                $response = $client->get($url);
            } catch (RequestException $e) {
                continue;
            }

            if ($response->getStatusCode() == 200) {
                return $url;
            }
        }

        return 'https://google.com/search?q=' . $word;
    }


    /**
     * Finds all occurrences of $needle in $haystack, and returns an array of all the positions.
     *
     * @param string $haystack
     *      the string to search in
     * @param string $needle
     *      the string to search for
     *
     * @return array
     *      an array containing all locations where $needle occurs
     */
    private function strpos_all(string $haystack, string $needle): array
    {
        $offset = 0;
        $positions = array();
        while (($pos = strpos($haystack, $needle, $offset)) !== false) {
            $offset   = $pos + 1;
            $positions[] = $pos;
        }

        return $positions;
    }

    /**
     * Sends the given message as a reply.
     *
     * @param string $text
     *      the message to send
     *
     * @return ServerResponse
     */
    public function sendReply(string $text): ServerResponse
    {
        try {
            $msg = $this->getMessage();
            $data = [
                'chat_id' => $msg->getChat()->getId(),
                'reply_to_message_id' => $msg->getMessageId(),
                'text' => $text,
            ];
            return Request::sendMessage($data);
        } catch (TelegramException $e) {
            Util::errMsg('Failed to send reply to user: ' . $e->getMessage());
        }

        return static::fakeResponse();
    }

    /** @noinspection PhpDocMissingThrowsInspection */
    /**
     * Returns a non-ok response.
     *
     * @return ServerResponse
     */
    protected static function fakeResponse(): ServerResponse
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return new ServerResponse(['ok' => false, 'result' => false], null);
    }

    /** @noinspection PhpDocMissingThrowsInspection */
    /**
     * Returns a response wrapping the given error message.
     *
     * @param TelegramException $e
     * @return ServerResponse
     */
    protected static function errorResponse(TelegramException $e): ServerResponse
    {
        /** @noinspection PhpUnhandledExceptionInspection */
        return new ServerResponse([
            'ok' => false,
            'description' => 'TelegramException: ' . $e->getMessage(),
            'error_code' => is_int($e->getCode()) ? $e->getCode() : 0,
        ], null);
    }
}