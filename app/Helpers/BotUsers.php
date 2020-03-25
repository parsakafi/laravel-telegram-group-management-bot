<?php

namespace App\Helpers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use phpDocumentor\Reflection\Types\Integer;

class BotUsers
{
    protected $table = 'bot_users';

    /**
     * Create/Update User
     *
     * <code>
     * $params = [
     *   'user_id'          => '',
     *   'first_name'       => '',
     *   'last_name'        => '',
     *   'user_name'        => '',
     *   'answer'           => '',
     *   'wrong_count'      => 1,
     *   'confirmed'        => true,
     *   'removed_at'       => '',
     * ];
     * </code>
     *
     * @param  array  $params
     *
     * @return Model|object|static|null
     *
     */
    public function set($params)
    {
        $userID  = $params['user_id'];
        $groupID = $params['group_id'];
        $user    = $this->getUser($userID, $groupID);
        $now     = date('Y-m-d H:i:s');

        $params['updated_at'] = $now;
        if($user) {
            $affected = DB::table($this->table)
                          ->where('user_id', $userID)
                          ->where('group_id', $groupID)
                          ->update($params);
        } else {
            $params['created_at'] = $now;
            $affected             = DB::table($this->table)->insert($params);
        }

        if($affected)
            return $this->getUser($userID, $groupID);
        elseif($user)
            return $user;
        else
            return false;
    }

    /**
     * Get user by Telegram user ID
     *
     * @param  Integer  $userID  User ID
     * @param  Integer  $groupID  Group ID
     *
     * @return Model|object|static|null
     */
    public function getUser($userID, $groupID)
    {
        return DB::table($this->table)
                 ->where('user_id', $userID)
                 ->where('group_id', $groupID)
                 ->first();
    }

    public function checkLimit($userID, $groupID, $user = null)
    {
        if($user == null)
            $user = $this->getUser($userID, $groupID);

        if($user->confirmed || $user->joined_at == null)
            return false;

        $maxWrongAnswer = intval(env('TELEGRAM_BOT_MAX_WRONG_ANSWER', 3));
        $maxQuestion    = intval(env('TELEGRAM_BOT_MAX_QUESTION', 10));

        return $user->wrong_count >= $maxWrongAnswer || $user->question_count >= $maxQuestion;
    }

    public function getOldMessages($minutes = 10)
    {
        return DB::table($this->table)
                 ->where(array(
                     ['confirmed', '=', 0],
                     ['question_at', '<', date('Y-m-d H:i:s', strtotime("-{$minutes} minutes"))],
                 ))
                 ->whereNotNull('question_message_id')
                 ->select('group_id', 'user_id', 'question_message_id')
                 ->limit(5)
                 ->get();
    }
}