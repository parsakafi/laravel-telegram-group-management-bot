<?php

namespace App\Http\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache as Cache;

use App\Helpers\BotUsers;
use App\Helpers\Question;
use App\Helpers\Helpers;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

/**
 * Class BotController.
 */
class BotController extends Controller
{
    protected $telegram, $botUser, $now;

    /**
     * Create a new controller instance.
     *
     * @param $telegram Api
     *
     * @return void
     */
    public function __construct(Api $telegram)
    {
        $this->telegram = $telegram;
        $this->botUser  = new BotUsers();
        $this->now      = date('Y-m-d H:i:s');
    }

    /**
     * Set Webhook
     *
     * @return void
     */
    public function setWebhook()
    {
        if( ! env('APP_DEBUG', false))
            return response('Access is forbidden to the requested page.', 403)
                ->header('Content-Type', 'text/plain');

        try {
            $url        = env('APP_URL', url('/'));
            $setWebhook = $this->telegram->setWebhook([
                'url' => $url,
                //'allowed_updates' => ['message', 'inline_query', 'chosen_inline_result', 'callback_query'] // Dont supported in current version of irazasyed/telegram-bot-sdk 3.0
            ]);
            if($setWebhook)
                echo 'Set Webhook Successfully.';
            else
                echo 'Set Webhook with Error!';

        } catch(Exception $e) {
            echo $e->getMessage().', '.$e->getFile().', '.$e->getLine();
        }
    }

    /**
     * Debug page
     * @return void
     */
    public function debug()
    {
        if( ! env('APP_DEBUG', false))
            return response('Access is forbidden to the requested page.', 403)
                ->header('Content-Type', 'text/plain');

        $telegram_error   = Cache::get('telegram_error', function() {
            return [];
        });
        $telegram_request = Cache::get('telegram_request', function() {
            return [];
        });
        $telegram_result  = Cache::get('telegram_result', function() {
            return [];
        });

        echo '<pre>';
        var_dump($telegram_error);
        var_dump($telegram_request);
        var_dump($telegram_result);
    }

    /**
     * Set old bot messages
     *
     * @return void
     */
    public function removeMessage()
    {
        $afterTime = intval(env('TELEGRAM_BOT_DELETE_MESSAGE_AFTER', 10));
        $messages  = $this->botUser->getOldMessages($afterTime);

        if(count($messages))
            foreach($messages as $message) {
                $this->deleteMessage($message->group_id, $message->question_message_id);

                $this->botUser->set(array(
                    'group_id'            => $message->group_id,
                    'user_id'             => $message->user_id,
                    'question_message_id' => null,
                    'answer'              => null
                ));

                echo "group_id: {$message->group_id}, user_id: {$message->user_id}, question_message_id: {$message->question_message_id}<br>";
            }
        else
            echo 'Not Messages!';
    }

    /**
     * Start Bot
     *
     * @param  Request  $request
     *
     * @return void
     * @throws Exception
     */
    public function start(Request $request)
    {
        $telegramRequest = $request->all();
        Cache::put('telegram_request', $telegramRequest);

        if(isset($telegramRequest['callback_query'])) {
            $this->dataResponse($telegramRequest);

            exit();
        } elseif(isset($telegramRequest['message']['left_chat_member']))
            exit();

        $reply_markup = $replyText = null;

        $allowedGroups    = explode(',', env('TELEGRAM_BOT_GROUPS', ''));
        $allowedCommands  = explode(',', env('TELEGRAM_BOT_ALLOWED_COMMANDS', ''));
        $removeBots       = env('TELEGRAM_BOT_REMOVE_BOTS', false);
        $localizeQuestion = env('TELEGRAM_BOT_LOCALIZE_QUESTION', false);

        if(
            ! isset($telegramRequest['message']['chat']['id']) ||
            ! in_array($telegramRequest['message']['chat']['type'], ['group', 'supergroup'])
        )
            exit();

        $groupID       = $telegramRequest['message']['chat']['id'];
        $isBot         = $telegramRequest['message']['from']['is_bot'];
        $from          = $telegramRequest['message']['from'];
        $fromID        = $from['id'];
        $fromFirstName = isset($from['first_name']) ? $from['first_name'] : null;
        $fromLastName  = isset($from['last_name']) ? $from['last_name'] : null;
        $fromUserName  = isset($from['username']) ? $from['username'] : null;
        $fromName      = $fromFirstName.' '.$fromLastName;
        $fromName      = trim(str_replace(['(',')','[',']'] , '' , $fromName));
        if(empty($fromName))
            $fromName = "X";

        if( ! in_array($groupID, $allowedGroups)) {
            $this->leaveChat($groupID);

            exit();

        } elseif($isBot && $removeBots) {
            $this->removeUser($groupID, $fromID);

            exit();
        }

        $user = $this->botUser->set(array(
            'group_id'   => $groupID,
            'user_id'    => $fromID,
            'first_name' => $fromFirstName,
            'last_name'  => $fromLastName,
            'user_name'  => $fromUserName,
        ));

        $confirmed       = intval($user->confirmed);
        $isNewChatMember = isset($telegramRequest['message']['new_chat_member']);
        $text            = isset($telegramRequest['message']['text']) ? trim($telegramRequest['message']['text']) : false;
        $command         = str_replace('!', '', $text);
        $isCommand       = in_array($command, $allowedCommands);

        if($isNewChatMember) {
            $newMember = $telegramRequest['message']['new_chat_member'];
            $user      = $this->botUser->set(array(
                'group_id'       => $groupID,
                'user_id'        => $newMember['id'],
                'first_name'     => isset($newMember['first_name']) ? $newMember['first_name'] : null,
                'last_name'      => isset($newMember['last_name']) ? $newMember['last_name'] : null,
                'user_name'      => isset($newMember['username']) ? $newMember['username'] : null,
                'question_count' => 0,
                'wrong_count'    => 0,
                'removed_at'     => null,
                'question_at'    => null,
                'joined_at'      => $this->now,
            ));
        }

        $messageID = $toMessageID = $telegramRequest['message']['message_id'];
        $isReplyID = isset($telegramRequest['message']['reply_to_message']) ? $telegramRequest['message']['reply_to_message']['message_id'] : false;

        if(($isNewChatMember || ! $confirmed) && $user->joined_at != null) {
            try {
                $toMessageID = null;
                $this->deleteMessage($groupID, $messageID);

                if( ! $isNewChatMember) {
                    $lockTime = intval(env('TELEGRAM_BOT_TEMP_LOCK_TIME', 5)); // Unit: Minutes
                    if($user->question_at != null && strtotime("-{$lockTime} minutes") < strtotime($user->question_at))
                        return;
                }

                if($user->question_message_id != null)
                    $this->deleteMessage($groupID, $messageID);

                if($user->question_message_id == null && $this->botUser->checkLimit($fromID, $groupID, $user)) {
                    $this->removeUser($groupID, $fromID);

                    return;
                }

                $question       = Question::create();
                $questionString = $question['question'];
                if($localizeQuestion)
                    $questionString = localizeString($questionString);
                $questionString = escapeMarkdown($questionString);

                $userName  = '['.$fromName.'](tg://user?id='.$fromID.')';
                $replyText = trans('bot.welcome', ['username' => $userName, 'question' => $questionString]);

                $answers   = Question::randomAnswers($question['answer'], 4);
                $answers[] = $question['answer'];
                $keyboard  = $this->keyboard($fromID, $groupID, $answers);

                $reply_markup = $this->telegramKeyboard($keyboard, 'inline_keyboard');

            } catch(Exception $e) {
                Cache::put('telegram_error', $e->getMessage().', '.$e->getFile().', '.$e->getLine());

                exit();
            }

        } elseif($isReplyID && $text && $isCommand) {      // Commands
            $replyFromID = $telegramRequest['message']['reply_to_message']['from']['id'];
            if($fromID === $replyFromID)
                exit();

            $isBot = $telegramRequest['message']['reply_to_message']['from']['is_bot'];
            if($isBot)
                exit();

            $this->deleteMessage($groupID, $messageID);

            $toMessageID = $isReplyID;
            $text        = str_replace('!', '', $text);
            $replyText   = trans('bot.'.$text);
            $replyText   = escapeMarkdown($replyText);
        } else
            exit();

        try {
            $result = $this->telegram->sendMessage([
                'chat_id'                  => $groupID,
                'reply_to_message_id'      => $toMessageID,
                'text'                     => $replyText,
                'reply_markup'             => $reply_markup,
                'disable_web_page_preview' => false,
                'parse_mode'               => 'Markdown'
            ])->getRawResponse();

            if($isNewChatMember || ! $confirmed) {
                $updateUserDate = array(
                    'user_id'             => $fromID,
                    'group_id'            => $groupID,
                    'question_message_id' => $result['message_id'],
                    'question_at'         => $this->now,
                    'question_count'      => ++$user->question_count,
                    'answer'              => $question['answer']
                );

                if($isNewChatMember) {
                    $updateUserDate['joined_at'] = $this->now;
                }

                $this->botUser->set($updateUserDate);
            }

        } catch(Exception $e) {
            $result = $e->getMessage();
        }

        Cache::put('telegram_result', $result);
    }

    /**
     * Leave from group
     *
     * @param  array  $telegramRequest  Required. Telegram Request
     *
     * @return void
     */
    private function dataResponse($telegramRequest)
    {
        try {
            $callbackQuery   = $telegramRequest['callback_query'];
            $callbackQueryID = $callbackQuery['id'];
            $groupID         = $callbackQuery['message']['chat']['id'];
            $messageID       = $callbackQuery['message']['message_id'];
            $fromID          = $callbackQuery['from']['id'];
            $data            = $callbackQuery['data'];

            list($type, $groupID2, $userID, $answer) = explode('_', $data);

            if($fromID != $userID) {
                $this->telegram->answerCallbackQuery([
                    'callback_query_id' => $callbackQueryID,
                    'text'              => trans('bot.not_answer_permission'),
                    'show_alert'        => true
                ]);

                return;
            }

            if($type == 'answer') {
                $user = $this->botUser->getUser($userID, $groupID);

                if( ! $user)
                    return;

                $updateUserDate = array(
                    'group_id'            => $groupID,
                    'user_id'             => $fromID,
                    'question_message_id' => null,
                    'answer'              => null
                );

                $correctAnswer = $user->answer == $answer;

                if($correctAnswer) {
                    $updateUserDate = array_merge(
                        $updateUserDate, ['confirmed' => true, 'confirmed_at' => $this->now]
                    );

                    $message = trans('bot.correct_answer');

                } else {
                    $updateWrongCount = ++$user->wrong_count;
                    $maxWrongAnswer   = intval(env('TELEGRAM_BOT_MAX_WRONG_ANSWER', 3));
                    $attempts         = $maxWrongAnswer - $updateWrongCount;

                    $updateUserDate = array_merge(
                        $updateUserDate, ['wrong_count' => $updateWrongCount]
                    );

                    $message = trans('bot.wrong_answer', ['attempts' => $attempts]);
                }

                $user = $this->botUser->set($updateUserDate);

                if( ! $correctAnswer) {
                    $allowRemoveUser = $user->answer != $answer && $this->botUser->checkLimit($fromID, $groupID, $user);

                    if($allowRemoveUser)
                        $message = trans('bot.wrong_answer_and_banned');
                }

                $this->telegram->answerCallbackQuery([
                    'callback_query_id' => $callbackQueryID,
                    'text'              => $message
                ]);

                $this->deleteMessage($groupID, $messageID);

                if( ! $correctAnswer && $allowRemoveUser) {
                    sleep(5);
                    $this->removeUser($groupID, $fromID);
                }
            }

        } catch(Exception $e) {
            Cache::put('telegram_error', $e->getMessage().', '.$e->getFile().', '.$e->getLine());
        }
    }

    /**
     * Delete message
     *
     * @param  integer|string  $groupID  Group ID
     * @param  integer|string  $messageID  Message ID
     *
     * @return  boolean
     */
    private function deleteMessage($groupID, $messageID)
    {
        try {
            return $this->telegram->deleteMessage(array(
                'chat_id'    => $groupID,
                'message_id' => $messageID
            ));
        } catch(Exception $e) {
            echo $e->getMessage().', '.$e->getFile().', '.$e->getLine().'<br>';

            return false;
        }
    }

    /**
     * Leave from group
     *
     * @param  integer|string  $groupID  Required. Telegram group ID
     *
     * @return boolean
     * @throws TelegramSDKException
     */
    private function leaveChat($groupID)
    {
        return $this->telegram->leaveChat(['chat_id' => $groupID]);
    }

    /**
     * Remove user from group
     *
     * @param  integer|string  $groupID  Required. Telegram group ID
     * @param  integer|string  $userID  Required. Telegram User ID
     *
     * @return boolean
     * @throws TelegramSDKException
     */
    private function removeUser($groupID, $userID)
    {
        $removed = $this->telegram->kickChatMember([
            'chat_id' => $groupID,
            'user_id' => $userID
        ]);

        if($removed)
            $this->botUser->set(array(
                'user_id'    => $userID,
                'group_id'   => $groupID,
                'removed_at' => $this->now
            ));

        return $removed;
    }

    /**
     * Create answers keyboards
     *
     * @param  integer|string  $userID  Required. Telegram User ID
     * @param  integer|string  $groupID  Required. Telegram Group ID
     * @param  array  $answers  Required. Answers
     *
     * @return array Array of keyboard buttons
     */
    private function keyboard($userID, $groupID, $answers)
    {
        $groupID          = abs($groupID);
        $localizeQuestion = env('TELEGRAM_BOT_LOCALIZE_QUESTION', false);
        $keyboard         = array(array());
        shuffle($answers);

        foreach($answers as $answer) {
            $keyboard[0][] = array(
                'text'          => $localizeQuestion ? localizeString($answer) : $answer,
                'callback_data' => 'answer_'.$groupID.'_'.$userID.'_'.$answer
            );
        }

        return $keyboard;
    }

    /**
     * Create Telegram keyboard
     *
     * @param  array  $keys  Required. Keyboards Array
     * @param  string  $type  (Optional). Keyboard type: keyboard, inline_keyboard
     *
     * @return string json of keyboards
     */
    private function telegramKeyboard($keys, $type = 'keyboard')
    {
        if( ! is_array($keys))
            return null;
        $keyboard = $keys;
        $reply    = array($type => $keyboard);
        if($type == 'keyboard')
            $reply['resize_keyboard'] = true;

        return json_encode($reply, true);
    }
}

