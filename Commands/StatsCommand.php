<?php

namespace Longman\TelegramBot\Commands\SystemCommands;

use Longman\TelegramBot\Commands\SystemCommand;
use Longman\TelegramBot\Conversation;
use Longman\TelegramBot\ConversationDB;
use Longman\TelegramBot\Request;
use Taeir\Vliegbot\Util;

/**
 * Stats command
 */
class StatsCommand extends SystemCommand
{
    /**
     * @var string
     */
    protected $name = 'stats';

    /**
     * @var string
     */
    protected $description = 'Shows statistics';

    /**
     * @var string
     */
    protected $usage = '/stats';

    /**
     * @var string
     */
    protected $version = '1.0.0';

    /**
     * @var bool
     */
    protected $need_mysql = true;

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

        $letters = [];
        $correct = [];
        foreach(range('A', 'Z') as $letter) {
            $letters[$letter] = $correct[$letter] = 0;
        }

        $won = 0;
        $total = 0;
        $lives_left = 0.0;

        $conversations = ConversationDB::selectConversationWithStatus(65961880, $chat_id, 'stopped');
        foreach ($conversations as $conversation) {
            $total++;

            $notes = json_decode($conversation['notes'], true);
            $word = strtoupper(Util::normalizeAccents($notes['word']));
            foreach($notes['guessed'] as $guess) {
                $letters[$guess]++;
                if (strpos($word, $guess) !== false) {
                    $correct[$guess]++;
                }
            }

            if (isset($notes['won']) && $notes['won'] === true) {
                $won++;
                $lives_left += $notes['lives'];

//                if (isset($notes['startlives']))
            }
        }

        $conversations = ConversationDB::selectConversationWithStatus(65961880, $chat_id, 'cancelled');
        $stopped = count($conversations);
        $total += $stopped;

        $lives_left_avg = $won === 0 ? 0 : (round($lives_left * 100 / $won) / 100);
        $won_percentage = $total === 0 ? 100 : (round($won * 100 / $total));
        $stopped_percentage = $total === 0 ? 0 : (round($stopped * 100 / $total));

        $text = Util::getLang('stats_Games_won') . ": $won/$total ($won_percentage%)\n"
                . Util::getLang('stats_Games_stopped') . ": $stopped ($stopped_percentage%)\n"
                . Util::getLang('stats_avg_lives_left') . ': ' . $lives_left_avg . "\n"
                . Util::getLang('stats_letters') . "\n```";


        //#mooiecodevanbram
        $letterStats = [];
        $longest = 0;
        foreach (range('A', 'Z') as $letter) {
            if ($letters[$letter] != 0) {
                $letterExplanation = "${letter}: $correct[$letter]/$letters[$letter]";
                $percentage = round(($correct[$letter] * 100) / $letters[$letter]);
            } else {
                $letterExplanation = "${letter}: 0/0";
                $percentage = 100;
            }
            $letterStats[$letter] = [
                'stats' => $letterExplanation,
                'percentage' => "(${percentage}%)",
            ];
            $longest = strlen($letterExplanation) > $longest ? strlen($letterExplanation) : $longest;
        }

        $text .= array_reduce(
            $letterStats,
            function(string $resultText, array $letterStats) use ($longest): string {
                return $resultText . str_pad($letterStats['stats'], $longest + 1) . $letterStats['percentage'] . "\n\t";
            },
            "\n\t"
        );

        $text .= "\n```"
;
        $data = [
            'chat_id' => $chat_id,
            'reply_to_message_id' => $message->getMessageId(),
            'text' => $text,
            'parse_mode' => 'Markdown'
        ];

        return Request::sendMessage($data);
    }
}
