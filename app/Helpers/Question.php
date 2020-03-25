<?php

namespace App\Helpers;

class Question
{
    // Operations array
    const operations = array('*', '+');

    // Minimum of random number
    const minRandomNumber = 2;

    // Maximum of random number
    const maxRandomNumber = 6;

    /**
     * Create question
     *
     * @return array
     */
    public static function create()
    {
        $ops    = self::operations;
        $op     = current($ops);
        $answer = -1;
        $num1   = $num2 = 0;
        while($answer < 0 || $answer > 99) {
            $num1 = rand(self::minRandomNumber, self::maxRandomNumber);
            $num2 = rand(self::minRandomNumber, self::maxRandomNumber);

            shuffle($ops);
            $op = current($ops);

            $answer = eval("return $num1 $op $num2;");
        }

        return array(
            'question' => "$num1 $op $num2 = ?",
            'answer'   => $answer
        );
    }

    /**
     * Create random answers
     *
     * @param  string|integer  $ignoreAnswer  Required.   Ignore answer for dont create duplicate answer
     * @param  integer  $number  (Optional). Number of generate answer
     *
     * @return array
     */
    public static function randomAnswers($ignoreAnswer, $number = 3)
    {
        $c       = 0;
        $answers = [];
        while($c < $number) {
            $answer = self::create()['answer'];
            if(in_array($answer, $answers) || $answer === $ignoreAnswer)
                continue;
            $answers[] = $answer;
            $c++;
        }

        return $answers;
    }
}