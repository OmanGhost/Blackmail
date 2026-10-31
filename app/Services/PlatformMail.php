<?php

namespace App\Services;

use App\Models\EmailDelivery;
use App\Models\EmailTemplate;
use App\Models\MailSetting;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;
use Throwable;

class PlatformMail
{
    public function send(User $user, string $key, string $message = ''): bool
    {
        return $this->sendTo($user->email, $user->name, $key, $message, $user->id);
    }

    public function sendTo(string $email, string $name, string $key, string $message = '', ?int $userId = null): bool
    {
        $settings = MailSetting::first();
        $template = EmailTemplate::where('key', $key)->where('is_active', true)->first();
        if (! $settings?->enabled || ! $template) {
            return false;
        }
        $variables = ['{{name}}' => $name, '{{account_url}}' => route('account'), '{{message}}' => $message];
        $body = strtr($template->body, $variables);
        $subject = str_replace(["\r", "\n"], ' ', strtr($template->subject, $variables));
        $delivery = EmailDelivery::create(['user_id' => $userId, 'template_key' => $key, 'status' => 'pending']);
        try {
            config(['mail.mailers.platform' => [
                'transport' => 'smtp', 'scheme' => $settings->scheme, 'host' => $settings->host,
                'port' => $settings->port, 'username' => $settings->username, 'password' => $settings->password, 'timeout' => 10,
            ]]);
            Mail::purge('platform');
            Mail::mailer('platform')->raw($body, function (Message $mail) use ($settings, $email, $subject) {
                $mail->from($settings->from_address, $settings->from_name)->to($email)->subject($subject);
            });
            $delivery->update(['status' => 'sent']);
            return true;
        } catch (Throwable) {
            $delivery->update(['status' => 'failed']);
            return false;
        }
    }
}
