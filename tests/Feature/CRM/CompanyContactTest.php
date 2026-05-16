<?php

namespace Tests\Feature\CRM;

use App\Models\Company;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyContactTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_company_with_normalized_data(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->make([
            'responsible_user_id' => $user->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('companies.store'), [
                ...$this->companyPayload($company),
                'cnpj' => $this->formatCnpj($company->cnpj),
                'phone' => '(11) 3333-4444',
                'email' => ' COMERCIAL@EXEMPLO.COM ',
            ]);

        $response->assertRedirect();

        $this->assertDatabaseHas('companies', [
            'cnpj' => $company->cnpj,
            'phone' => '1133334444',
            'email' => 'comercial@exemplo.com',
        ]);

        $this->assertDatabaseHas('timeline_events', [
            'type' => 'company.created',
            'user_id' => $user->id,
        ]);
    }

    public function test_company_cnpj_must_be_unique(): void
    {
        $user = User::factory()->create();
        $existing = Company::factory()->create([
            'responsible_user_id' => $user->id,
        ]);
        $company = Company::factory()->make([
            'responsible_user_id' => $user->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->from(route('companies.create'))
            ->post(route('companies.store'), [
                ...$this->companyPayload($company),
                'cnpj' => $existing->cnpj,
            ]);

        $response->assertRedirect(route('companies.create'));
        $response->assertSessionHasErrors('cnpj');
    }

    public function test_contact_requires_company_and_can_be_primary(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create([
            'responsible_user_id' => $user->id,
        ]);
        $oldPrimary = Contact::factory()->primary()->for($company)->create();

        $response = $this
            ->actingAs($user)
            ->post(route('contacts.store'), [
                'company_id' => $company->id,
                'name' => 'Maria Decisora',
                'position' => 'Diretora Financeira',
                'department' => 'Financeiro',
                'email' => 'MARIA@EXEMPLO.COM',
                'phone' => '(11) 4002-8922',
                'whatsapp' => '(11) 99999-0000',
                'linkedin_url' => null,
                'type' => 'decision_maker',
                'is_primary' => true,
                'receives_automations' => true,
                'notes' => 'Responsável pela negociação.',
            ]);

        $response->assertRedirect(route('companies.show', $company));

        $this->assertDatabaseHas('contacts', [
            'company_id' => $company->id,
            'name' => 'Maria Decisora',
            'email' => 'maria@exemplo.com',
            'phone' => '1140028922',
            'whatsapp' => '11999990000',
            'is_primary' => true,
        ]);
        $this->assertDatabaseHas('contacts', [
            'id' => $oldPrimary->id,
            'is_primary' => false,
        ]);
        $this->assertDatabaseHas('timeline_events', [
            'company_id' => $company->id,
            'type' => 'contact.created',
        ]);
    }

    public function test_company_search_finds_contact_data(): void
    {
        $user = User::factory()->create();
        $company = Company::factory()->create([
            'legal_name' => 'Academia Voz Educacional',
            'responsible_user_id' => $user->id,
        ]);
        Contact::factory()->for($company)->create([
            'name' => 'Contato Encontravel',
            'email' => 'contato.encontravel@example.com',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('companies.index', ['search' => 'encontravel']));

        $response->assertOk();
        $response->assertSee('Academia Voz Educacional');
    }

    public function test_contact_cannot_exist_without_company(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from(route('contacts.create'))
            ->post(route('contacts.store'), [
                'company_id' => null,
                'name' => 'Contato Solto',
                'type' => 'other',
                'is_primary' => false,
                'receives_automations' => true,
            ]);

        $response->assertRedirect(route('contacts.create'));
        $response->assertSessionHasErrors('company_id');
    }

    private function companyPayload(Company $company): array
    {
        return [
            'legal_name' => $company->legal_name,
            'trade_name' => $company->trade_name,
            'cnpj' => $company->cnpj,
            'segment' => $company->segment,
            'site' => $company->site,
            'phone' => $company->phone,
            'email' => $company->email,
            'whatsapp' => $company->whatsapp,
            'city' => $company->city,
            'state' => $company->state,
            'address' => $company->address,
            'status' => $company->status->value,
            'lead_source' => $company->lead_source,
            'responsible_user_id' => $company->responsible_user_id,
            'average_collection_ticket' => $company->average_collection_ticket,
            'overdue_customers_count' => $company->overdue_customers_count,
            'total_default_amount' => $company->total_default_amount,
            'approx_customers_count' => $company->approx_customers_count,
            'current_system' => $company->current_system,
            'has_internal_collection_team' => $company->has_internal_collection_team,
            'has_erp_integration' => $company->has_erp_integration,
            'portfolio_notes' => $company->portfolio_notes,
            'company_type' => $company->company_type->value,
            'company_size' => $company->company_size->value,
            'commercial_potential' => $company->commercial_potential,
            'lead_temperature' => $company->lead_temperature->value,
            'priority' => $company->priority->value,
            'pain_profile' => $company->pain_profile,
            'closing_probability' => $company->closing_probability,
        ];
    }

    private function formatCnpj(string $cnpj): string
    {
        return preg_replace('/^(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})$/', '$1.$2.$3/$4-$5', $cnpj);
    }
}
