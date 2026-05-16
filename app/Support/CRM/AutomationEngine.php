<?php

namespace App\Support\CRM;

use App\Enums\ActivityStatus;
use App\Enums\ActivityType;
use App\Enums\AutomationActionType;
use App\Enums\AutomationExecutionStatus;
use App\Enums\AutomationTrigger;
use App\Enums\CommunicationChannel;
use App\Enums\CommunicationDirection;
use App\Enums\CommunicationOrigin;
use App\Enums\CommunicationStatus;
use App\Enums\PriorityLevel;
use App\Jobs\SendEmailCommunication;
use App\Jobs\SendWhatsappCommunication;
use App\Models\Activity;
use App\Models\AutomationExecution;
use App\Models\CommercialAutomation;
use App\Models\CommunicationMessage;
use App\Models\CommunicationTemplate;
use App\Models\Company;
use App\Models\Contact;
use App\Models\InternalNotification;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

class AutomationEngine
{
    /**
     * @param  array<string, mixed>  $context
     * @return Collection<int, AutomationExecution>
     */
    public function handle(AutomationTrigger $trigger, array $context, string $eventKey): Collection
    {
        return CommercialAutomation::query()
            ->where('trigger', $trigger->value)
            ->where('is_active', true)
            ->get()
            ->filter(fn (CommercialAutomation $automation): bool => $this->matchesConditions($automation, $context))
            ->map(fn (CommercialAutomation $automation): AutomationExecution => $this->run($automation, $trigger, $context, $eventKey))
            ->values();
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function run(CommercialAutomation $automation, AutomationTrigger $trigger, array $context, string $eventKey): AutomationExecution
    {
        $key = hash('sha256', "{$automation->id}:{$trigger->value}:{$eventKey}");

        $existing = AutomationExecution::query()->where('idempotency_key', $key)->first();

        if ($existing) {
            return $existing;
        }

        $lock = Cache::lock("crm:automation:{$key}", 30);

        if (! $lock->get()) {
            return AutomationExecution::query()->firstOrCreate([
                'commercial_automation_id' => $automation->id,
                'idempotency_key' => $key,
            ], [
                ...$this->executionContext($automation, $trigger, $context),
                'status' => AutomationExecutionStatus::Skipped,
                'result' => ['reason' => 'lock_not_acquired'],
                'executed_at' => now(),
            ]);
        }

        try {
            $execution = AutomationExecution::query()->create([
                ...$this->executionContext($automation, $trigger, $context),
                'idempotency_key' => $key,
                'status' => AutomationExecutionStatus::Success,
                'result' => [],
                'executed_at' => now(),
            ]);

            $results = [];

            foreach ($automation->actions ?? [] as $action) {
                $results[] = $this->executeAction($automation, $action, $context);
            }

            $execution->update([
                'result' => ['actions' => $results],
            ]);

            $this->recordAutomationTimeline($automation, $execution, $context);

            return $execution->refresh();
        } catch (Throwable $exception) {
            return AutomationExecution::query()->updateOrCreate([
                'commercial_automation_id' => $automation->id,
                'idempotency_key' => $key,
            ], [
                ...$this->executionContext($automation, $trigger, $context),
                'status' => AutomationExecutionStatus::Failed,
                'error_message' => $exception->getMessage(),
                'executed_at' => now(),
            ]);
        } finally {
            $lock->release();
        }
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function executionContext(CommercialAutomation $automation, AutomationTrigger $trigger, array $context): array
    {
        return [
            'commercial_automation_id' => $automation->id,
            'company_id' => $this->company($context)?->id,
            'contact_id' => $this->contact($context)?->id,
            'opportunity_id' => $this->opportunity($context)?->id,
            'activity_id' => $this->activity($context)?->id,
            'user_id' => $this->user($context)?->id,
            'trigger' => $trigger,
            'payload' => $this->payload($context),
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function payload(array $context): array
    {
        return [
            'company_id' => $this->company($context)?->id,
            'contact_id' => $this->contact($context)?->id,
            'opportunity_id' => $this->opportunity($context)?->id,
            'activity_id' => $this->activity($context)?->id,
            'user_id' => $this->user($context)?->id,
            'stage_id' => data_get($context, 'stage.id'),
            'stage_slug' => data_get($context, 'stage.slug'),
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function matchesConditions(CommercialAutomation $automation, array $context): bool
    {
        $conditions = $automation->conditions ?? [];

        if ($stageSlug = data_get($conditions, 'to_stage_slug')) {
            if (data_get($context, 'stage.slug') !== $stageSlug) {
                return false;
            }
        }

        if ($stageSlug = data_get($conditions, 'stage_slug')) {
            if (data_get($context, 'stage.slug') !== $stageSlug) {
                return false;
            }
        }

        if ($stageId = data_get($conditions, 'to_stage_id')) {
            if ((int) data_get($context, 'stage.id') !== (int) $stageId) {
                return false;
            }
        }

        if ($statuses = data_get($conditions, 'company_statuses')) {
            $company = $this->company($context);

            if (! $company || ! in_array($company->status->value, (array) $statuses, true)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $action
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function executeAction(CommercialAutomation $automation, array $action, array $context): array
    {
        $type = AutomationActionType::from($action['type']);

        return match ($type) {
            AutomationActionType::CreateActivity => $this->createActivity($automation, $action, $context),
            AutomationActionType::SendEmail => $this->sendEmail($automation, $action, $context),
            AutomationActionType::SendWhatsapp => $this->sendWhatsapp($automation, $action, $context),
            AutomationActionType::NotifyUser => $this->notifyUser($automation, $action, $context),
            AutomationActionType::AddTimelineNote => $this->addTimelineNote($automation, $action, $context),
        };
    }

    /**
     * @param  array<string, mixed>  $action
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function createActivity(CommercialAutomation $automation, array $action, array $context): array
    {
        $company = $this->company($context);
        $assignee = $this->resolveUser((string) data_get($action, 'assigned_to', 'responsible'), $context);

        if (! $company || ! $assignee) {
            return ['type' => AutomationActionType::CreateActivity->value, 'status' => 'skipped'];
        }

        $activity = Activity::query()->create([
            'company_id' => $company->id,
            'contact_id' => $this->contact($context)?->id,
            'opportunity_id' => $this->opportunity($context)?->id,
            'assigned_to_user_id' => $assignee->id,
            'created_by_user_id' => $this->user($context)?->id,
            'type' => ActivityType::from((string) data_get($action, 'activity_type', ActivityType::Task->value)),
            'status' => ActivityStatus::Pending,
            'priority' => PriorityLevel::from((string) data_get($action, 'priority', PriorityLevel::Medium->value)),
            'title' => $this->render((string) data_get($action, 'title', 'Ação automática'), $context),
            'description' => $this->render((string) data_get($action, 'description', $automation->name), $context),
            'due_at' => now()->addDays((int) data_get($action, 'due_in_days', 1))->setTime(10, 0),
        ]);

        Timeline::record(
            company: $company,
            type: 'automation.activity_created',
            title: 'Tarefa criada por automação',
            description: "{$activity->title} foi criada pela automação {$automation->name}.",
            contact: $activity->contact,
            user: $this->user($context),
            metadata: [
                'automation_id' => $automation->id,
                'activity_id' => $activity->id,
            ],
        );

        return [
            'type' => AutomationActionType::CreateActivity->value,
            'activity_id' => $activity->id,
        ];
    }

    /**
     * @param  array<string, mixed>  $action
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function sendEmail(CommercialAutomation $automation, array $action, array $context): array
    {
        $contact = $this->targetContact($context);
        $company = $this->company($context);

        if (! $company || ! $contact || blank($contact->email)) {
            return ['type' => AutomationActionType::SendEmail->value, 'status' => 'skipped'];
        }

        $user = $this->user($context);
        $channel = $user ? CommunicationChannelResolver::defaultFor(CommunicationChannel::Email, $user) : null;

        if (! $channel) {
            return ['type' => AutomationActionType::SendEmail->value, 'status' => 'skipped', 'reason' => 'missing_channel'];
        }

        $template = $this->template($action);
        $message = CommunicationMessage::query()->create([
            'company_id' => $company->id,
            'contact_id' => $contact->id,
            'opportunity_id' => $this->opportunity($context)?->id,
            'user_id' => $user?->id,
            'communication_template_id' => $template?->id,
            'communication_channel_id' => $channel->id,
            'channel' => CommunicationChannel::Email,
            'direction' => CommunicationDirection::Outbound,
            'status' => CommunicationStatus::Queued,
            'origin' => CommunicationOrigin::Automated,
            'provider' => $channel->provider,
            'from_address' => $channel->settings()['from_address'] ?? null,
            'to_address' => $contact->email,
            'subject' => $this->render((string) data_get($action, 'subject', $template?->subject ?? $automation->name), $context),
            'body' => $this->render((string) data_get($action, 'body', $template?->body ?? ''), $context),
            'queued_at' => now(),
        ]);

        CommunicationTimeline::record($message, 'E-mail automático enfileirado');
        SendEmailCommunication::dispatch($message->id);

        return [
            'type' => AutomationActionType::SendEmail->value,
            'communication_message_id' => $message->id,
        ];
    }

    /**
     * @param  array<string, mixed>  $action
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function sendWhatsapp(CommercialAutomation $automation, array $action, array $context): array
    {
        $contact = $this->targetContact($context);
        $company = $this->company($context);
        $number = $contact?->whatsapp ?: $contact?->phone;

        if (! $company || ! $contact || blank($number)) {
            return ['type' => AutomationActionType::SendWhatsapp->value, 'status' => 'skipped'];
        }

        $user = $this->user($context);
        $channel = $user ? CommunicationChannelResolver::defaultFor(CommunicationChannel::Whatsapp, $user) : null;

        if (! $channel) {
            return ['type' => AutomationActionType::SendWhatsapp->value, 'status' => 'skipped', 'reason' => 'missing_channel'];
        }

        $template = $this->template($action);
        $message = CommunicationMessage::query()->create([
            'company_id' => $company->id,
            'contact_id' => $contact->id,
            'opportunity_id' => $this->opportunity($context)?->id,
            'user_id' => $user?->id,
            'communication_template_id' => $template?->id,
            'communication_channel_id' => $channel->id,
            'channel' => CommunicationChannel::Whatsapp,
            'direction' => CommunicationDirection::Outbound,
            'status' => CommunicationStatus::Queued,
            'origin' => CommunicationOrigin::Automated,
            'provider' => $channel->provider,
            'from_address' => $channel->settings()['instance'] ?? null,
            'to_address' => $number,
            'body' => $this->render((string) data_get($action, 'body', $template?->body ?? ''), $context),
            'queued_at' => now(),
        ]);

        CommunicationTimeline::record($message, 'WhatsApp automático enfileirado');
        SendWhatsappCommunication::dispatch($message->id);

        return [
            'type' => AutomationActionType::SendWhatsapp->value,
            'communication_message_id' => $message->id,
        ];
    }

    /**
     * @param  array<string, mixed>  $action
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function notifyUser(CommercialAutomation $automation, array $action, array $context): array
    {
        $user = $this->resolveUser((string) data_get($action, 'recipient', 'responsible'), $context);

        if (! $user) {
            return ['type' => AutomationActionType::NotifyUser->value, 'status' => 'skipped'];
        }

        $notification = InternalNotification::query()->create([
            'user_id' => $user->id,
            'company_id' => $this->company($context)?->id,
            'commercial_automation_id' => $automation->id,
            'title' => $this->render((string) data_get($action, 'title', $automation->name), $context),
            'body' => $this->render((string) data_get($action, 'body', $automation->description), $context),
        ]);

        return [
            'type' => AutomationActionType::NotifyUser->value,
            'internal_notification_id' => $notification->id,
        ];
    }

    /**
     * @param  array<string, mixed>  $action
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function addTimelineNote(CommercialAutomation $automation, array $action, array $context): array
    {
        $company = $this->company($context);

        if (! $company) {
            return ['type' => AutomationActionType::AddTimelineNote->value, 'status' => 'skipped'];
        }

        $event = Timeline::record(
            company: $company,
            type: 'automation.note',
            title: $this->render((string) data_get($action, 'title', $automation->name), $context),
            description: $this->render((string) data_get($action, 'description', $automation->description), $context),
            contact: $this->contact($context),
            user: $this->user($context),
            metadata: ['automation_id' => $automation->id],
        );

        return [
            'type' => AutomationActionType::AddTimelineNote->value,
            'timeline_event_id' => $event->id,
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function recordAutomationTimeline(CommercialAutomation $automation, AutomationExecution $execution, array $context): void
    {
        $company = $this->company($context);

        if (! $company) {
            return;
        }

        Timeline::record(
            company: $company,
            type: 'automation.executed',
            title: 'Automação executada',
            description: "{$automation->name} foi executada com sucesso.",
            contact: $this->contact($context),
            user: $this->user($context),
            metadata: [
                'automation_id' => $automation->id,
                'automation_execution_id' => $execution->id,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function render(string $value, array $context): string
    {
        $company = $this->company($context);
        $contact = $this->targetContact($context);
        $opportunity = $this->opportunity($context);

        return Str::of($value)
            ->replace('{{empresa}}', $company?->displayName() ?? 'empresa')
            ->replace('{{contato}}', $contact?->name ?? 'contato')
            ->replace('{{oportunidade}}', $opportunity?->title ?? 'oportunidade')
            ->replace('{{etapa}}', (string) (data_get($context, 'stage.name') ?? 'etapa atual'))
            ->toString();
    }

    /**
     * @param  array<string, mixed>  $action
     */
    private function template(array $action): ?CommunicationTemplate
    {
        $templateId = data_get($action, 'template_id');

        return $templateId ? CommunicationTemplate::query()->find($templateId) : null;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function resolveUser(string $mode, array $context): ?User
    {
        return match ($mode) {
            'current_user' => $this->user($context),
            'activity_assignee' => $this->activity($context)?->assignedTo,
            default => $this->opportunity($context)?->responsibleUser
                ?? $this->company($context)?->responsibleUser
                ?? $this->activity($context)?->assignedTo
                ?? $this->user($context),
        };
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function targetContact(array $context): ?Contact
    {
        $contact = $this->contact($context);

        if ($contact) {
            return $contact;
        }

        return $this->company($context)?->contacts()
            ->latest('is_primary')
            ->latest()
            ->first();
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function company(array $context): ?Company
    {
        return $context['company'] ?? $this->opportunity($context)?->company ?? $this->activity($context)?->company;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function contact(array $context): ?Contact
    {
        return $context['contact'] ?? $this->opportunity($context)?->contact ?? $this->activity($context)?->contact;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function opportunity(array $context): ?Opportunity
    {
        return $context['opportunity'] ?? $this->activity($context)?->opportunity;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function activity(array $context): ?Activity
    {
        return $context['activity'] ?? null;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function user(array $context): ?User
    {
        return $context['user'] ?? null;
    }
}
