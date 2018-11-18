<?php
/**
 * This file is part of the TelegramBot package.
 *
 * (c) Avtandil Kikabidze aka LONGMAN <akalongman@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Request;
use Taeir\Vliegbot\Util;

/**
 * Start command
 */
class StartCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'start';

    /**
     * @var string
     */
    protected $description = 'Start a new game';

    /**
     * @var string
     */
    protected $usage = '/start';

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

        //Get the conversation for a fixed user id, since the conversation must be shared between users.
        $this->conversation = new Conversation(
            65961880,
            $chat_id,
            "game"
        );

        $notes = &$this->conversation->notes;
        if (is_array($notes)) {
            $data['text'] = Util::getLang('game_in_progress');
            return Request::sendMessage($data);
        }

        $notes = [];
        $notes['word']    = $this->selectRandomWord();
        $notes['guessed'] = [];
        $notes['lives']   = Util::getConfig()['lives'];

        $this->conversation->update();

        Util::logMsg('Starting new game in ' . $chat_id . ' (' . $chat->getTitle() . ").\n\t"
            . 'Word: ' . $notes['word'] . ', Lives: ' . $notes['lives']);

        $data['text'] = Util::formatResponse($notes['word'], $notes['guessed'], $notes['lives']);
        $data['reply_markup'] = Util::getKeyboard($notes['guessed']);

        return Request::sendMessage($data);
    }

    /**
     * @return string
     *      a randomly selected word
     */
    private function selectRandomWord(): string
    {
        $config = Util::getConfig();
        $dictionary = file($config['dictionaries_path'] . '/' . $config['language'] . '.txt');
        return strtoupper(substr($dictionary[rand(0, count($dictionary) -1)], 0, -1));
    }
}
