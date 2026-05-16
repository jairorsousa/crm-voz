<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Activity;
use App\Models\CommercialAutomation;
use App\Models\CommunicationChannel;
use App\Models\CommunicationTemplate;
use App\Models\Company;
use App\Models\Contact;
use App\Models\CrmOptionValue;
use App\Models\CrmSetting;
use App\Models\User;
use App\Support\CRM\PipelineDefaults;
use Faker\Generator;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::query()->updateOrCreate([
            'email' => env('SEED_USER_EMAIL', 'admin@voz.local'),
        ], [
            'name' => env('SEED_USER_NAME', 'Administrador VOZ'),
            'role' => UserRole::Admin,
            'password' => bcrypt(env('SEED_USER_PASSWORD', 'change-me-now')),
            'email_verified_at' => now(),
        ]);

        collect([
            ['Gestor Comercial VOZ', 'gestor@voz.local', UserRole::CommercialManager],
            ['SDR VOZ', 'sdr@voz.local', UserRole::Sdr],
            ['Closer VOZ', 'closer@voz.local', UserRole::Closer],
        ])->each(function (array $user): void {
            User::query()->updateOrCreate([
                'email' => $user[1],
            ], [
                'name' => $user[0],
                'role' => $user[2],
                'password' => bcrypt(env('SEED_USER_PASSWORD', 'change-me-now')),
                'email_verified_at' => now(),
            ]);
        });

        $users = User::query()->orderBy('id')->get();
        $teamUserIds = $users->pluck('id')->all();

        $callChannel = CommunicationChannel::query()->updateOrCreate([
            'name' => 'Ligação VOZ',
            'type' => 'call',
        ], [
            'provider' => 'twilio',
            'config' => [
                'account_sid' => config('services.twilio.account_sid'),
                'auth_token' => config('services.twilio.auth_token'),
                'api_key' => config('services.twilio.api_key'),
                'api_secret' => config('services.twilio.api_secret'),
                'twiml_app_sid' => config('services.twilio.twiml_app_sid'),
                'caller_id' => config('services.twilio.caller_id'),
                'from_number' => config('services.twilio.from_number'),
                'voice_webhook_url' => config('services.twilio.voice_webhook_url'),
                'webhook_token' => config('services.twilio.webhook_token'),
            ],
            'is_active' => true,
            'is_shared' => true,
            'is_default' => true,
        ]);
        $callChannel->users()->sync($teamUserIds);

        $users->each(function (User $user): void {
            $emailChannel = CommunicationChannel::query()->updateOrCreate([
                'name' => 'E-mail '.$user->name,
                'type' => 'email',
            ], [
                'provider' => 'smtp',
                'config' => [
                    'host' => config('mail.mailers.smtp.host'),
                    'port' => config('mail.mailers.smtp.port'),
                    'username' => config('mail.mailers.smtp.username'),
                    'password' => config('mail.mailers.smtp.password'),
                    'encryption' => config('mail.mailers.smtp.encryption'),
                    'from_address' => $user->email,
                    'from_name' => $user->name,
                ],
                'is_active' => true,
                'is_shared' => false,
                'is_default' => true,
            ]);
            $emailChannel->users()->sync([$user->id]);

            $whatsappChannel = CommunicationChannel::query()->updateOrCreate([
                'name' => 'WhatsApp '.$user->name,
                'type' => 'whatsapp',
            ], [
                'provider' => 'evolution',
                'config' => [
                    'url' => config('services.evolution.url'),
                    'key' => config('services.evolution.key'),
                    'instance' => config('services.evolution.instance') ?: 'voz-'.$user->id,
                    'webhook_token' => config('services.evolution.webhook_token'),
                ],
                'is_active' => true,
                'is_shared' => false,
                'is_default' => true,
            ]);
            $whatsappChannel->users()->sync([$user->id]);
        });

        if (class_exists(Generator::class) && Company::query()->doesntExist()) {
            Company::factory()
                ->count(8)
                ->recycle($users)
                ->create()
                ->each(function (Company $company): void {
                    Contact::factory()->primary()->for($company)->create();
                    Contact::factory()->count(2)->for($company)->create();
                    Activity::factory()->today()->for($company)->create();
                    Activity::factory()->overdue()->for($company)->create();
                });
        }

        PipelineDefaults::ensureDefaultPipeline();

        collect([
            [
                'channel' => 'email',
                'name' => 'Primeiro contato',
                'subject' => 'Como reduzir inadimplência com a VOZ',
                'body' => "Olá {{contato}},\n\nVi que a {{empresa}} pode ter oportunidades para melhorar a régua de cobrança. Podemos conversar esta semana?",
            ],
            [
                'channel' => 'email',
                'name' => 'Follow-up de proposta',
                'subject' => 'Próximo passo da proposta VOZ',
                'body' => "Olá {{contato}},\n\nPassando para retomar a proposta enviada e alinhar o próximo passo com a {{empresa}}.",
            ],
            [
                'channel' => 'whatsapp',
                'name' => 'Mensagem inicial',
                'subject' => null,
                'body' => 'Olá {{contato}}, tudo bem? Aqui é da VOZ. Podemos falar rapidamente sobre a operação de cobrança da {{empresa}}?',
            ],
            [
                'channel' => 'whatsapp',
                'name' => 'Confirmação de reunião',
                'subject' => null,
                'body' => 'Olá {{contato}}, confirmando nossa reunião sobre a operação da {{empresa}}. Qualquer ajuste, me avise por aqui.',
            ],
        ])->each(fn (array $template): CommunicationTemplate => CommunicationTemplate::query()->updateOrCreate([
            'channel' => $template['channel'],
            'name' => $template['name'],
        ], $template + ['is_active' => true]));

        CrmSetting::putValue('voz', 'voz.company', 'Dados da VOZ', [
            'name' => 'VOZ',
            'document' => null,
            'site' => 'https://voz.local',
            'email' => 'comercial@voz.local',
            'phone' => null,
            'address' => null,
        ]);

        collect([
            ['lost_reasons', 'preco', 'Preço', '#EF4444', 1],
            ['lost_reasons', 'sem-fit', 'Sem fit', '#F59E0B', 2],
            ['lost_reasons', 'sem-orcamento', 'Sem orçamento', '#8B5CF6', 3],
            ['lead_sources', 'indicacao', 'Indicação', '#10B981', 1],
            ['lead_sources', 'site', 'Site', '#3B82F6', 2],
            ['lead_sources', 'prospeccao-ativa', 'Prospecção ativa', '#FF6F00', 3],
            ['segments', 'varejo', 'Varejo', '#14B8A6', 1],
            ['segments', 'servicos', 'Serviços', '#6366F1', 2],
            ['segments', 'financeiro', 'Financeiro', '#0EA5E9', 3],
            ['contact_types', 'decisor', 'Decisor', '#10B981', 1],
            ['contact_types', 'influenciador', 'Influenciador', '#3B82F6', 2],
            ['contact_types', 'operacional', 'Operacional', '#64748B', 3],
        ])->each(fn (array $option): CrmOptionValue => CrmOptionValue::query()->updateOrCreate([
            'group' => $option[0],
            'key' => $option[1],
        ], [
            'label' => $option[2],
            'color' => $option[3],
            'position' => $option[4],
            'is_active' => true,
        ]));

        collect([
            [
                'name' => 'Follow-up automático após proposta',
                'description' => 'Cria um follow-up quando uma oportunidade chega em Proposta enviada.',
                'trigger' => 'opportunity_stage_changed',
                'conditions' => ['to_stage_slug' => 'proposta-enviada'],
                'actions' => [
                    [
                        'type' => 'create_activity',
                        'activity_type' => 'follow_up',
                        'priority' => 'high',
                        'assigned_to' => 'responsible',
                        'title' => 'Follow-up da proposta — {{empresa}}',
                        'description' => 'Retomar {{oportunidade}} após envio de proposta.',
                        'due_in_days' => 2,
                    ],
                    [
                        'type' => 'add_timeline_note',
                        'title' => 'Automação de proposta ativada',
                        'description' => 'Follow-up automático criado para {{oportunidade}}.',
                    ],
                ],
            ],
            [
                'name' => 'Follow-up após reunião agendada',
                'description' => 'Cria uma tarefa de preparação quando uma reunião é agendada.',
                'trigger' => 'meeting_scheduled',
                'conditions' => [],
                'actions' => [
                    [
                        'type' => 'create_activity',
                        'activity_type' => 'task',
                        'priority' => 'medium',
                        'assigned_to' => 'activity_assignee',
                        'title' => 'Preparar reunião — {{empresa}}',
                        'description' => 'Revisar contexto da empresa e próximos passos antes da reunião.',
                        'due_in_days' => 0,
                    ],
                    [
                        'type' => 'add_timeline_note',
                        'title' => 'Reunião entrou na rotina comercial',
                        'description' => 'Automação acionada a partir de reunião agendada.',
                    ],
                ],
            ],
            [
                'name' => 'Proposta sem resposta',
                'description' => 'Cria alerta e tarefa quando uma proposta fica parada.',
                'trigger' => 'proposal_no_response',
                'conditions' => ['stage_slug' => 'proposta-enviada', 'days_without_response' => 3],
                'actions' => [
                    [
                        'type' => 'create_activity',
                        'activity_type' => 'follow_up',
                        'priority' => 'critical',
                        'assigned_to' => 'responsible',
                        'title' => 'Cobrar retorno da proposta — {{empresa}}',
                        'description' => '{{oportunidade}} está sem resposta desde a etapa {{etapa}}.',
                        'due_in_days' => 1,
                    ],
                    [
                        'type' => 'notify_user',
                        'recipient' => 'responsible',
                        'title' => 'Proposta sem resposta',
                        'body' => '{{oportunidade}} precisa de follow-up.',
                    ],
                ],
            ],
            [
                'name' => 'Lead sem interação',
                'description' => 'Cria tarefa para leads novos ou em prospecção sem interação recente.',
                'trigger' => 'lead_no_interaction',
                'conditions' => ['days_without_interaction' => 7, 'company_statuses' => ['new_lead', 'prospecting']],
                'actions' => [
                    [
                        'type' => 'create_activity',
                        'activity_type' => 'task',
                        'priority' => 'high',
                        'assigned_to' => 'responsible',
                        'title' => 'Retomar lead — {{empresa}}',
                        'description' => 'Lead sem interação recente. Fazer novo contato.',
                        'due_in_days' => 0,
                    ],
                    [
                        'type' => 'notify_user',
                        'recipient' => 'responsible',
                        'title' => 'Lead sem interação',
                        'body' => '{{empresa}} precisa de contato.',
                    ],
                ],
            ],
            [
                'name' => 'Tarefa vencida',
                'description' => 'Notifica o responsável e registra no histórico quando uma tarefa vence.',
                'trigger' => 'task_overdue',
                'conditions' => [],
                'actions' => [
                    [
                        'type' => 'notify_user',
                        'recipient' => 'activity_assignee',
                        'title' => 'Tarefa vencida',
                        'body' => 'Existe uma tarefa vencida em {{empresa}}.',
                    ],
                    [
                        'type' => 'add_timeline_note',
                        'title' => 'Tarefa vencida sinalizada',
                        'description' => 'Automação registrou uma pendência vencida para acompanhamento.',
                    ],
                ],
            ],
        ])->each(fn (array $automation): CommercialAutomation => CommercialAutomation::query()->firstOrCreate([
            'name' => $automation['name'],
        ], $automation + ['is_active' => true]));
    }
}
