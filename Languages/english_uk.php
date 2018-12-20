<?php
return [
    //======================================================
    //Single words used in the game message (capitalized)
    'Word' => 'Word',
    'Lives' => 'Lives',

    //======================================================
    //Feedback on guesses
    'wrong_guess' => 'Wrong guess!',
    'correct_guess' => 'Correct guess!',

    //======================================================
    //Messages for the stop command
    'game_in_progress' => 'There is already a game in progress!',
    'no_game_to_end' => 'There is no game to stop.',
    'game_stopped' => 'The current game has been stopped.',

    //======================================================
    //Messages for winning and losing
    'game_lost' => 'You have lost',
    'game_won' => 'Congratulations! You won',
    'the_word_was' => 'The word was',
    'play_again' => 'Do you want to play again?',

    //======================================================
    //Word explanation (definition)
    'definition_text' => 'Definition',
    'definition_url' => [
        'https://en.wiktionary.org/wiki/',
    ],

    //======================================================
    //Messages for statistics
    'stats_Games_won' => 'Games won',
    'stats_Games_stopped' => 'Games stopped',
    'stats_avg_lives_left' => 'Average number of lives left',
    'stats_letters' => 'Guessed letters: correct/total (percentage correct)',
];