<?php
return [
    'welcome'                 => "Welcome, :username".
                                 "\n".
                                 "To prevent robots from entering, users are allowed to send messages until they can confirm. To confirm your account, answer the following equation:".
                                 "\n".
                                 " :question ".
                                 "\n".
                                 "Select the button with the correct answer.",
    'smart'                   => 'Please give clear and complete questions'.
                                 "\nhttps://stackoverflow.com/help/how-to-ask",
    'not_answer_permission'   => 'You do not have permission to answer another person\'s question!',
    'correct_answer'          => 'Correct answer, Welcome.',
    'wrong_answer'            => 'Wrong answer, Number of attempts left: :attempts',
    'wrong_answer_and_banned' => 'The answer is wrong and you will be removed from the group.',
    'g'                       => 'Let me google that for you'."\n".'[:word](https://lmgtfy.com/?q=:encodeword)'
];