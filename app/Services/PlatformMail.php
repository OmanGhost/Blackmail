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
        $settings = MailSetting::first();
        $template = EmailTemplate::where('key', $key)->where('is_active', true)->first();
        if (! $settings?->enabled || ! $template) {
            return false;
        }
        $variables = ['{{name}}' => $user->name, '{{account_url}}' => route('account'), '{{message}}' => $message];
        $body = strtr($template->body, $variables);
        $subject = str_replace(["\r", "\n"], ' ', strtr($template->subject, $variables));
        $delivery = EmailDelivery::create(['user_id' => $user->id, 'template_key' => $key, 'status' => 'pending']);
        try {
            config(['mail.mailers.platform' => [
                'transport' => 'smtp', 'scheme' => $settings->scheme, 'host' => $settings->host,
                'port' => $settings->port, 'username' => $settings->username, 'password' => $settings->password, 'timeout' => 10,
            ]]);
            Mail::purge('platform');
            Mail::mailer('platform')->raw($body, function (Message $mail) use ($settings, $user, $subject) {
                $mail->from($settings->from_address, $settings->from_name)->to($user->email)->subject($subject);
            });
            $delivery->update(['status' => 'sent']);
            return true;
        } catch (Throwable) {
            $delivery->update(['status' => 'failed']);
            return false;
        }
    }
}
