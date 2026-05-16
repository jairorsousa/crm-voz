<?php

namespace Tests\Feature\CRM;

use App\Enums\CommunicationChannel;
use App\Enums\UserRole;
use App\Models\CommunicationTemplate;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class CommunicationTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_manage_email_and_whatsapp_templates(): void
    {
        $manager = User::factory()->create(['role' => UserRole::CommercialManager]);
        $template = CommunicationTemplate::factory()->create([
            'channel' => CommunicationChannel::Email,
            'name' => 'Follow-up antigo',
            'subject' => 'Assunto antigo',
            'body' => 'Texto antigo',
            'is_active' => true,
        ]);

        $this->actingAs($manager)->get(route('templates.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Templates/Index')
                ->where('templates.data.0.name', 'Follow-up antigo')
                ->has('options.channels', 2));

        $this->actingAs($manager)->post(route('templates.store'), [
            'channel' => CommunicationChannel::Whatsapp->value,
            'name' => 'Mensagem de retomada',
            'subject' => '',
            'body' => 'Olá {{contato}}, podemos retomar a conversa com a {{empresa}}?',
            'is_active' => true,
        ])->assertRedirect(route('templates.index'));

        $this->assertDatabaseHas('communication_templates', [
            'channel' => CommunicationChannel::Whatsapp->value,
            'name' => 'Mensagem de retomada',
            'subject' => null,
        ]);

        $this->actingAs($manager)->patch(route('templates.update', $template), [
            'channel' => CommunicationChannel::Email->value,
            'name' => 'Follow-up atualizado',
            'subject' => 'Assunto atualizado',
            'body' => 'Texto atualizado',
            'is_active' => true,
        ])->assertRedirect(route('templates.index'));

        $this->assertSame('Follow-up atualizado', $template->refresh()->name);

        $this->actingAs($manager)->patch(route('templates.toggle', $template))->assertRedirect();
        $this->assertFalse($template->refresh()->is_active);

        $this->assertDatabaseHas('audit_logs', [
            'event' => 'communication.template.created',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'communication.template.updated',
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'event' => 'communication.template.toggled',
        ]);
    }

    public function test_sdr_cannot_manage_templates(): void
    {
        $sdr = User::factory()->create(['role' => UserRole::Sdr]);

        $this->actingAs($sdr)->get(route('templates.index'))->assertForbidden();
    }

    public function test_template_name_must_be_unique_per_channel(): void
    {
        $manager = User::factory()->create(['role' => UserRole::CommercialManager]);
        CommunicationTemplate::factory()->create([
            'channel' => CommunicationChannel::Email,
            'name' => 'Primeiro contato',
        ]);

        $this->actingAs($manager)->from(route('templates.create'))->post(route('templates.store'), [
            'channel' => CommunicationChannel::Email->value,
            'name' => 'Primeiro contato',
            'subject' => 'Olá',
            'body' => 'Texto do modelo',
            'is_active' => true,
        ])->assertRedirect(route('templates.create'))
            ->assertSessionHasErrors('name');

        $this->actingAs($manager)->post(route('templates.store'), [
            'channel' => CommunicationChannel::Whatsapp->value,
            'name' => 'Primeiro contato',
            'subject' => '',
            'body' => 'Texto do WhatsApp',
            'is_active' => true,
        ])->assertRedirect(route('templates.index'));
    }
}
