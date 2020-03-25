# Laravel (Lumen) Telegram Group Management Bot
Allow you to verify the new member of the group and send some command to a user.



### Develop

Develop with [Lumen](https://lumen.laravel.com) V7.0, 

#### Requirements
* [Laravel Requirements](https://laravel.com/docs/7.x/installation#server-requirements)
* SSL for [Telegram Webhook](https://core.telegram.org/bots/webhooks) method


## Telegram Bot

### Create Bot
Create Telegram bot with [@BotFather](https://core.telegram.org/bots#6-botfather)

### Group Privacy
After creating the Telegram bot, you will need to disable "Group Privacy" in "Bot Settings".

### Add to group
You need to add Telegram bot to your Telegram group(s) as a Admin with `Delete Message` and `Ban Users` permission.

## How to install

#### First clone of repository
```bash
git clone https://github.com/parsakafi/laravel-telegram-group-management-bot.git
```

#### Install Requirements
```bash
cd laravel-telegram-group-management-bot
composer install
```

#### Config File (.env)

Copy `.env.example` file to `.env`
```bash
cp .env.example .env
```

Setup database connection in `.env` file.


##### Setup Telegram Bot 

###### Username bot

```
TELEGRAM_BOT_USERNAME=username_bot
```

###### API Token

```
TELEGRAM_BOT_TOKEN=bot_token
```

###### Allowed Telegram group IDs 

Allowed Telegram group IDs (Separated with comma)

```
TELEGRAM_BOT_GROUPS=-123
```

###### Group Commands

Add new command for reply to users (Separated with comma)

```
TELEGRAM_BOT_ALLOWED_COMMANDS=smart
```

For set reply command string, Edit language file.

###### Allow remove bots

```
TELEGRAM_BOT_REMOVE_BOTS=true
```

###### Temporary lock time (Unit: Minutes)

```
TELEGRAM_BOT_TEMP_LOCK_TIME=5
```

###### Maximum wrong answer number

```
TELEGRAM_BOT_MAX_WRONG_ANSWER=3
```

###### Maximum question number

```
TELEGRAM_BOT_MAX_QUESTION=10
```

###### Localize question string

Currently Persian/Farsi language is supported

```
TELEGRAM_BOT_LOCALIZE_QUESTION=false
```



#### Database [Migration](https://laravel.com/docs/7.x/migrations)

```bash
php artisan migrate
```

#### [Local Development Server](https://laravel.com/docs/7.x/installation#installing-laravel)
This command will start a development server at `http://localhost:8000`:
```bash
php artisan serve
```

### Set Webhook
First, make sure you set the `APP_URL` value in the `.env` file correctly. For set webhook run this url on your web browser (Replace your domain). Debugging mode is required to be enabled.
```text
https://your-web-bot-url.tld/set_webhook
```

### Remove old bot message
For remove old question message from group(s). Add this url to your server cron job. (Replace your domain)
```text
https://your-web-bot-url.tld/remove_message
```

### Debug page
To display latest Telegram request/error/result (Replace your domain). Debugging mode is required to be enabled.
```text
https://your-web-bot-url.tld/debug
```

## How to Work?

The bot sends an equation to new group member. If the user resolves the equation, Allows the user to continue group activity.

#### Reply command

Command start with exclamation mark (!), For example `!smart`

If you reply a command to user, The bot get command description from language file and reply to user.