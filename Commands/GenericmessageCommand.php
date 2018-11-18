<?php
namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Entities\ServerResponse;
use Longman\TelegramBot\Exception\TelegramException;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Commands\SystemCommand;
use Spatie\Emoji\Emoji;
use Taeir\Vliegbot\Util;

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
        //Check config
        if (!Util::isConfigValid()) {
            return $this->sendReply('Config file not found!');
        }

        $message = $this->getMessage();
        $chat_id = $message->getChat()->getId();
        $user_id = $message->getFrom()->getId();
        $letter  = strtoupper(trim($message->getText()));

        //TODO Allow full word guesses for 1 life?
        //Not a single letter
        if (strlen($letter) != 1) {
            return Request::emptyResponse();
        }

        $this->conversation = new Conversation(
            65961880,
            $chat_id,
            "game"
        );

        //There is no game in progress
        if (!$this->conversation->exists()) {
            return Request::emptyResponse();
        }

        $notes = &$this->conversation->notes;
        !is_array($notes['guessed']) && $notes['guessed'] = [];

        if ($notes['word'] === null) {
            return Request::emptyResponse();
        }

        //This letter was already guessed
        if (in_array($letter, $notes['guessed'])) {
            return Request::emptyResponse();
        }

        $positions = $this->strpos_all(strtoupper($notes['word']), $letter);
        $notes['guessed'][] = $letter;
        if (count($positions) === 0) {
            $notes['lives']--;
            $guess_part = "Verkeerde letter!\n";
        } else {
            $guess_part = "Correcte letter!\n";
        }

        $data = [
            'chat_id' => $chat_id,
            'reply_to_message_id' => $message->getMessageId(),
        ];

        //Check word guessed
        if ($notes['lives'] === 0) {
            $data['text'] = $guess_part . "\nJe hebt verloren " . Emoji::pensiveFace() . "!\nHet woord was \"" . $notes['word'] . "\"";
            $data['reply_markup'] = Keyboard::remove();
            $this->conversation->stop();
        } else if ($this->checkWon($notes['word'], $notes['guessed'])) {
            $data['text'] = "Gefeliciteerd! Je hebt gewonnen " . Emoji::partyPopper() . "!\nHet woord was \"" . $notes['word'] . "\"";
            $data['reply_markup'] = Keyboard::remove();
            $this->conversation->stop();
        } else {
            $data['text'] = $guess_part . Util::formatResponse($notes['word'], $notes['guessed'], $notes['lives']);
            $data['reply_markup'] = Util::getKeyboard($notes['guessed']);
        }

        $this->conversation->update();

        return Request::sendMessage($data);
    }

    private function checkWon(string $word, array $guessed)
    {
        return count(array_diff(str_split($word), $guessed)) === 0;
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