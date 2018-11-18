<?php

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Request;
use Taeir\Vliegbot\Util;

/**
 * Stop command
 */
class StopCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'stop';

    /**
     * @var string
     */
    protected $description = 'Stops the current game';

    /**
     * @var string
     */
    protected $usage = '/stop';

    /**
     * @var string
     */
    protected $version = '1.0.0';

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
     * Command execute method
     *
     * @return mixed
     * @throws \Longman\TelegramBot\Exception\TelegramException
     */
    public function execute()
    {
        $message = $this->getMessage();
        $chat = $message->getChat();
        $chat_id = $chat->getId();
        $user_id = $message->getFrom()->getId();

        $data = [
            'chat_id' => $chat_id,
            'reply_to_message_id' => $message->getMessageId(),
        ];

        //Get the conversation for user id 0, since the conversation must be shared between users.
        $this->conversation = new Conversation(
            65961880,
            $chat_id,
            "game"
        );

//        if ($this->conversation->exists()) {
//            $notes = &$this->conversation->notes;
//
//            $data['text'] = 'There is already a game in progress!';
//            !is_array($notes['guessed']) && $notes['guessed'] = [];
//            $data['reply_markup'] = Util::getKeyboard($notes['guessed']);
//            return Request::sendMessage($data);
//        }

        $notes = &$this->conversation->notes;
        !is_array($notes) && $notes = [];


        //TODO select a word randomly from a list
        $notes['word']    = $this->select_random_word();
        $notes['guessed'] = [];
        $notes['lives']   = Util::getConfig()['lives'];

        $this->conversation->update();

        $data['text'] = Util::formatResponse($notes['word'], $notes['guessed'], $notes['lives']);
        $data['reply_markup'] = Util::getKeyboard($notes['guessed']);

        return Request::sendMessage($data);
    }

    private function select_random_word(): string
    {
        $config = Util::getConfig();
        $dictionary = file($config['dictionaries_path'] . '/' . $config['language'] . '.txt');
        $word = strtoupper(substr($dictionary[rand(0, count($dictionary) -1)], 0, -1));

        print($word . "\n");

        return $word;
    }
}
