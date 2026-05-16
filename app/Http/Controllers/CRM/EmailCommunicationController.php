<?php

namespace App\Http\Controllers\CRM;

use App\Enums\CommunicationChannel;
use App\Enums\CommunicationDirection;
use App\Enums\CommunicationOrigin;
use App\Enums\CommunicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\StoreEmailCommunicationRequest;
use App\Jobs\SendEmailCommunication;
use App\Models\CommunicationMessage;
use App\Models\Company;
use App\Support\CRM\CommunicationChannelResolver;
use App\Support\CRM\CommunicationOptions;
use App\Support\CRM\CommunicationTimeline;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Inertia\Inertia;
use Inertia\Response;

class EmailCommunicationController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'status' => $request->string('status')->toString(),
            'company_id' => $request->string('company_id')->toString(),
            'contact_id' => $request->string('contact_id')->toString(),
        ];

        $messages = CommunicationMessage::query()
            ->visibleTo($request->user())
            ->with(['company:id,legal_name,trade_name', 'contact:id,name', 'opportunity:id,title', 'user:id,name', 'communicationChannel:id,name'])
            ->where('channel', CommunicationChannel::Email)
            ->search($filters['search'])
            ->when($filters['status'], fn (Builder $query, string $value) => $query->where('status', $value))
            ->when($filters['company_id'], fn (Builder $query, string $value) => $query->where('company_id', $value))
            ->when($filters['contact_id'], fn (Builder $query, string $value) => $query->where('contact_id', $value))
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (CommunicationMessage $message): array => $this->payload($message));

        return Inertia::render('Communications/Emails', [
            'messages' => $messages,
            'filters' => $filters,
            'options' => CommunicationOptions::for(CommunicationChannel::Email, $request->user()),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Communications/EmailForm', [
            'selectedCompanyId' => $request->integer('company_id') ?: null,
            'selectedContactId' => $request->integer('contact_id') ?: null,
            'options' => CommunicationOptions::for(CommunicationChannel::Email, $request->user()),
        ]);
    }

    public function store(StoreEmailCommunicationRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        abort_unless(Company::query()->visibleTo($request->user())->whereKey($validated['company_id'])->exists(), 403);

        try {
            $channel = CommunicationChannelResolver::authorizedFor($validated['communication_channel_id'] ?? null, CommunicationChannel::Email, $request->user());
        } catch (RuntimeException $exception) {
            return back()->withErrors(['communication_channel_id' => $exception->getMessage()])->withInput();
        }

        $attachments = $this->storeAttachments($request);
        unset($validated['attachments']);
        $mailSettings = $channel->settings();

        $message = CommunicationMessage::query()->create([
            ...$validated,
            'attachments' => $attachments,
            'communication_channel_id' => $channel->id,
            'user_id' => $request->user()->id,
            'channel' => CommunicationChannel::Email,
            'direction' => CommunicationDirection::Outbound,
            'status' => CommunicationStatus::Queued,
            'origin' => CommunicationOrigin::Manual,
            'provider' => $channel->provider,
            'from_address' => $mailSettings['from_address'] ?? config('mail.from.address'),
            'queued_at' => now(),
        ]);

        CommunicationTimeline::record($message, 'E-mail enfileirado');

        SendEmailCommunication::dispatch($message->id);

        return redirect()
            ->route('emails.index')
            ->with('success', 'E-mail enviado para a fila de comunicação.');
    }

    /**
     * @return list<array{name: string, path: string, mime: string|null, size: int|null}>
     */
    private function storeAttachments(StoreEmailCommunicationRequest $request): array
    {
        return collect($request->file('attachments', []))
            ->map(function ($file): array {
                $path = Storage::putFile('communications/attachments', $file);

                return [
                    'name' => $file->getClientOriginalName(),
                    'path' => $path,
                    'mime' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ];
            })
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(CommunicationMessage $message): array
    {
        return [
            'id' => $message->id,
            'channel' => ['value' => $message->channel->value, 'label' => $message->channel->label()],
            'direction' => ['value' => $message->direction->value, 'label' => $message->direction->label()],
            'status' => ['value' => $message->status->value, 'label' => $message->status->label()],
            'origin' => ['value' => $message->origin->value, 'label' => $message->origin->label()],
            'to_address' => $message->to_address,
            'subject' => $message->subject,
            'body' => $message->body,
            'notes' => $message->notes,
            'error_message' => $message->error_message,
            'duration_seconds' => $message->duration_seconds,
            'created_at' => $message->created_at?->toISOString(),
            'sent_at' => $message->sent_at?->toISOString(),
            'completed_at' => $message->completed_at?->toISOString(),
            'company' => ['id' => $message->company->id, 'display_name' => $message->company->displayName()],
            'contact' => ['id' => $message->contact->id, 'name' => $message->contact->name],
            'opportunity' => $message->opportunity ? ['id' => $message->opportunity->id, 'title' => $message->opportunity->title] : null,
            'user' => $message->user ? ['id' => $message->user->id, 'name' => $message->user->name] : null,
            'communication_channel' => $message->communicationChannel ? ['id' => $message->communicationChannel->id, 'name' => $message->communicationChannel->name] : null,
            'attachments_count' => count($message->attachments ?? []),
        ];
    }
}
