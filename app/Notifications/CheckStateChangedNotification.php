<?php

namespace App\Notifications;

use App\Models\CheckLog;
use App\Models\DomainCheck;
use App\Notifications\Channels\TelegramChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CheckStateChangedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public DomainCheck $check,
        public bool $wasOk,
        public bool $nowOk,
        public CheckLog $log
    ) {
        $this->check->loadMissing('domain');
    }

    public function via(object $notifiable): array
    {
        $channels = ['mail'];

        if (config('services.telegram.bot_token') && config('services.telegram.chat_id')) {
            $channels[] = TelegramChannel::class;
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $host = $this->check->domain->hostname;
        $subject = $this->nowOk
            ? __('checks.mail_subject_up', ['host' => $host])
            : __('checks.mail_subject_down', ['host' => $host]);

        return (new MailMessage)
            ->subject($subject)
            ->line($this->lineText())
            ->line(__('checks.check_url').': '.$this->check->buildUrl())
            ->when($this->log->http_status, fn (MailMessage $m) => $m->line('HTTP: '.$this->log->http_status))
            ->when($this->log->error_message, fn (MailMessage $m) => $m->line($this->log->error_message));
    }

    public function toTelegram(object $notifiable): string
    {
        $host = $this->check->domain->hostname;
        $title = $this->nowOk
            ? __('checks.telegram_up', ['host' => $host])
            : __('checks.telegram_down', ['host' => $host]);

        $lines = [
            '<b>'.e($title).'</b>',
            e($this->check->buildUrl()),
        ];
        if ($this->log->http_status) {
            $lines[] = 'HTTP: '.e((string) $this->log->http_status);
        }
        if ($this->log->error_message) {
            $lines[] = e($this->log->error_message);
        }

        return implode("\n", $lines);
    }

    private function lineText(): string
    {
        $host = $this->check->domain->hostname;

        return $this->nowOk
            ? __('checks.state_recovered', ['host' => $host])
            : __('checks.state_failed', ['host' => $host]);
    }
}
