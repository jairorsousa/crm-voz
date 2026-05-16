<?php

namespace App\Jobs;

use App\Enums\CommunicationStatus;
use App\Models\CommunicationMessage;
use App\Support\CRM\CommunicationTimeline;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class SendEmailCommunication implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $messageId)
    {
        $this->onQueue('communications');
    }

    public function handle(): void
    {
        $message = CommunicationMessage::query()->findOrFail($this->messageId);

        try {
            $mailer = $this->mailerFor($message);
            $channelSettings = $message->communicationChannel?->settings() ?? [];

            Mail::mailer($mailer)->html(nl2br(e($message->body ?? '')), function ($mail) use ($message, $channelSettings): void {
                $mail
                    ->from($message->from_address ?: config('mail.from.address'), $channelSettings['from_name'] ?? config('mail.from.name'))
                    ->to($message->to_address)
                    ->subject($message->subject ?? 'Mensagem VOZ CRM');

                if (! empty($message->cc)) {
                    $mail->cc($message->cc);
                }

                if (! empty($message->bcc)) {
                    $mail->bcc($message->bcc);
                }

                foreach ($message->attachments ?? [] as $attachment) {
                    if (! isset($attachment['path']) || ! Storage::exists($attachment['path'])) {
                        continue;
                    }

                    $mail->attach(Storage::path($attachment['path']), [
                        'as' => $attachment['name'] ?? basename($attachment['path']),
                        'mime' => $attachment['mime'] ?? null,
                    ]);
                }
            });

            $message->update([
                'status' => CommunicationStatus::Sent,
                'sent_at' => now(),
                'error_message' => null,
            ]);

            CommunicationTimeline::record($message->refresh(), 'E-mail enviado');
        } catch (Throwable $exception) {
            $message->update([
                'status' => CommunicationStatus::Failed,
                'error_message' => $exception->getMessage(),
                'completed_at' => now(),
            ]);

            CommunicationTimeline::record($message->refresh(), 'Falha ao enviar e-mail', $exception->getMessage());
        }
    }

    private function mailerFor(CommunicationMessage $message): string
    {
        $channel = $message->communicationChannel;

        if (! $channel) {
            return config('mail.default', 'mail');
        }

        if (app()->environment('testing') && config('mail.default') === 'array') {
            return config('mail.default', 'mail');
        }

        $settings = $channel->settings();

        foreach (['host', 'port', 'from_address'] as $field) {
            if (blank($settings[$field] ?? null)) {
                throw new RuntimeException('Canal SMTP incompleto. Verifique host, porta e e-mail remetente.');
            }
        }

        $name = 'channel_smtp_'.$channel->id;

        config([
            "mail.mailers.{$name}" => [
                'transport' => 'smtp',
                'host' => $settings['host'],
                'port' => (int) $settings['port'],
                'encryption' => filled($settings['encryption'] ?? null) ? $settings['encryption'] : null,
                'username' => $settings['username'] ?? null,
                'password' => $settings['password'] ?? null,
                'timeout' => null,
                'local_domain' => null,
            ],
        ]);

        return $name;
    }
}
