<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;

class TelegramChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        $token = config('services.telegram.bot_token');
        $chatId = config('services.telegram.chat_id');
        if (! $token || ! $chatId || ! method_exists($notification, 'toTelegram')) {
            return;
        }

        $text = $notification->toTelegram($notifiable);
        if ($text === '' || $text === null) {
            return;
        }

        Http::timeout(15)->post("https://api.telegram.org/bot{$token}/sendMessage", [
            'chat_id' => $chatId,
            'text' => $text,
            'parse_mode' => 'HTML',
        ]);
    }
}
