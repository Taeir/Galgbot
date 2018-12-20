<?php

namespace Longman\TelegramBot\Commands\SystemCommands;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\Request;
use Taeir\Galgbot\Util;

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
        if (is_array($notes) && !empty($notes)) {
            $data['text'] = Util::getLang('game_in_progress');
            return Request::sendMessage($data);
        }

        $notes = [];
        $notes['word']    = $this->selectRandomWord();
        $notes['guessed'] = [];
        $notes['lives']   = $notes['startlives'] = Util::getLives($notes['word']);

        $this->conversation->update();

        Util::logMsg("Starting new game in {$chat_id} ({$chat->getTitle()}).\n\t"
            . "Word: {$notes['word']}, Lives: {$notes['lives']}");

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
        $only_defined = $config['only_defined_words'];
        $dictionary = file("{$config['dictionaries_path']}/{$config['language']}.txt");

        if (!$only_defined) {
            return mb_strtoupper(substr($dictionary[rand(0, count($dictionary) - 1)], 0, -1));
        } else {
            for ($i = 0; $i < 40; $i++) {
                $word = mb_strtoupper(substr($dictionary[rand(0, count($dictionary) - 1)], 0, -1));
                $wordurl = urlencode(mb_strtolower($word));
                foreach (Util::getLang('definition_url') as $base_url) {
                    $url = $base_url . $wordurl;
                    try {
                        $client = new Client([
                            'base_uri' => $base_url
                        ]);

                        $response = $client->get($url);
                    } catch (RequestException $e) {
                        continue;
                    }

                    if ($response->getStatusCode() == 200) {
                        return $word;
                    }
                }
            }

            Util::logMsg('Unable to find word with definition after 20 attempts. Giving up.');
            return $word;
        }
    }
}
