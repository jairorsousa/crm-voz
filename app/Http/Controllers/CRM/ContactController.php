<?php

namespace App\Http\Controllers\CRM;

use App\Http\Controllers\Controller;
use App\Http\Requests\CRM\StoreContactRequest;
use App\Http\Requests\CRM\UpdateContactRequest;
use App\Models\Company;
use App\Models\Contact;
use App\Support\CRM\CrmOptions;
use App\Support\CRM\FormatsCrmData;
use App\Support\CRM\Timeline;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'search' => $request->string('search')->toString(),
            'company_id' => $request->string('company_id')->toString(),
            'type' => $request->string('type')->toString(),
        ];

        $contacts = Contact::query()
            ->visibleTo($request->user())
            ->with('company:id,legal_name,trade_name,cnpj')
            ->search($filters['search'])
            ->when($filters['company_id'], fn ($query, string $value) => $query->where('company_id', $value))
            ->when($filters['type'], fn ($query, string $value) => $query->where('type', $value))
            ->latest('is_primary')
            ->latest()
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Contact $contact): array => $this->contactListPayload($contact));

        return Inertia::render('Contacts/Index', [
            'contacts' => $contacts,
            'filters' => $filters,
            'options' => $this->options(),
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Contacts/Form', [
            'mode' => 'create',
            'contact' => null,
            'selectedCompanyId' => $request->integer('company_id') ?: null,
            'options' => $this->options(),
        ]);
    }

    public function store(StoreContactRequest $request): RedirectResponse
    {
        abort_unless(Company::query()->visibleTo($request->user())->whereKey($request->validated()['company_id'])->exists(), 403);

        $contact = Contact::query()->create($request->validated());
        $this->syncPrimaryContact($contact);

        $contact->load('company');

        Timeline::record(
            company: $contact->company,
            type: 'contact.created',
            title: 'Contato cadastrado',
            description: "O contato {$contact->name} foi vinculado à empresa.",
            contact: $contact,
            user: $request->user(),
        );

        return redirect()
            ->route('companies.show', $contact->company)
            ->with('success', 'Contato cadastrado com sucesso.');
    }

    public function edit(Contact $contact): Response
    {
        $this->authorizeContact($contact);

        $contact->load('company:id,legal_name,trade_name,cnpj');

        return Inertia::render('Contacts/Form', [
            'mode' => 'edit',
            'contact' => $this->contactFormPayload($contact),
            'selectedCompanyId' => null,
            'options' => $this->options(),
        ]);
    }

    public function update(UpdateContactRequest $request, Contact $contact): RedirectResponse
    {
        $this->authorizeContact($contact);
        abort_unless(Company::query()->visibleTo($request->user())->whereKey($request->validated()['company_id'])->exists(), 403);

        $contact->update($request->validated());
        $this->syncPrimaryContact($contact);

        $contact->load('company');

        Timeline::record(
            company: $contact->company,
            type: 'contact.updated',
            title: 'Contato atualizado',
            description: "Os dados de {$contact->name} foram atualizados.",
            contact: $contact,
            user: $request->user(),
        );

        return redirect()
            ->route('companies.show', $contact->company)
            ->with('success', 'Contato atualizado com sucesso.');
    }

    public function destroy(Contact $contact): RedirectResponse
    {
        $this->authorizeContact($contact);

        $company = $contact->company;
        $contactName = $contact->name;
        $contact->delete();

        Timeline::record(
            company: $company,
            type: 'contact.deleted',
            title: 'Contato removido',
            description: "O contato {$contactName} foi removido da empresa.",
            user: request()->user(),
        );

        return back()->with('success', 'Contato removido com sucesso.');
    }

    /**
     * @return array<string, mixed>
     */
    private function options(): array
    {
        return [
            ...CrmOptions::all(),
            'companies' => Company::query()
                ->visibleTo(request()->user())
                ->orderBy('trade_name')
                ->orderBy('legal_name')
                ->get(['id', 'legal_name', 'trade_name', 'cnpj'])
                ->map(fn (Company $company): array => [
                    'value' => $company->id,
                    'label' => $company->displayName(),
                    'description' => FormatsCrmData::cnpj($company->cnpj),
                ]),
        ];
    }

    private function syncPrimaryContact(Contact $contact): void
    {
        if (! $contact->is_primary) {
            return;
        }

        Contact::query()
            ->where('company_id', $contact->company_id)
            ->whereKeyNot($contact->id)
            ->update(['is_primary' => false]);
    }

    /**
     * @return array<string, mixed>
     */
    private function contactListPayload(Contact $contact): array
    {
        return [
            'id' => $contact->id,
            'name' => $contact->name,
            'position' => $contact->position,
            'department' => $contact->department,
            'email' => $contact->email,
            'phone' => $contact->phone,
            'formatted_phone' => FormatsCrmData::phone($contact->phone),
            'whatsapp' => $contact->whatsapp,
            'formatted_whatsapp' => FormatsCrmData::phone($contact->whatsapp),
            'type' => [
                'value' => $contact->type->value,
                'label' => $contact->type->label(),
            ],
            'is_primary' => $contact->is_primary,
            'receives_automations' => $contact->receives_automations,
            'company' => [
                'id' => $contact->company->id,
                'display_name' => $contact->company->displayName(),
                'formatted_cnpj' => FormatsCrmData::cnpj($contact->company->cnpj),
            ],
            'created_at' => $contact->created_at?->toISOString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function contactFormPayload(Contact $contact): array
    {
        return [
            'id' => $contact->id,
            'company_id' => $contact->company_id,
            'name' => $contact->name,
            'position' => $contact->position,
            'department' => $contact->department,
            'email' => $contact->email,
            'phone' => $contact->phone,
            'whatsapp' => $contact->whatsapp,
            'linkedin_url' => $contact->linkedin_url,
            'type' => $contact->type->value,
            'is_primary' => $contact->is_primary,
            'receives_automations' => $contact->receives_automations,
            'notes' => $contact->notes,
            'company' => $contact->company ? [
                'id' => $contact->company->id,
                'display_name' => $contact->company->displayName(),
                'formatted_cnpj' => FormatsCrmData::cnpj($contact->company->cnpj),
            ] : null,
        ];
    }

    private function authorizeContact(Contact $contact): void
    {
        abort_unless(Contact::query()->visibleTo(auth()->user())->whereKey($contact->id)->exists(), 403);
    }
}
