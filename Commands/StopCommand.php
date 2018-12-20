<?php

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Entities\Keyboard;
use Longman\TelegramBot\Request;
use Taeir\Galgbot\Util;

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

        $notes = &$this->conversation->notes;
        if (!is_array($notes) || empty($notes)) {
            $data['text'] = Util::getLang('no_game_to_end');
        } else {
            $data['text'] = Util::getLang('game_stopped') . "\n"
                            . Util::getLang('the_word_was') . ' "' . $notes['word'] . '"';
            $data['reply_markup'] = Keyboard::remove();

            $this->conversation->cancel();
            $this->conversation->update();
        }
        return Request::sendMessage($data);
    }
}
