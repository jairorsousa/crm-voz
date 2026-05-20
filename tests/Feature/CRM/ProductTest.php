<?php

namespace Tests\Feature\CRM;

use App\Enums\UserRole;
use App\Models\Company;
use App\Models\Product;
use App\Models\User;
use App\Support\CRM\PipelineDefaults;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_create_product(): void
    {
        $manager = User::factory()->create(['role' => UserRole::CommercialManager]);

        $response = $this->actingAs($manager)->post(route('products.store'), [
            'name' => 'Cobrança consultiva',
            'slug' => 'cobranca-consultiva',
            'category' => 'Consultoria',
            'description' => 'Diagnóstico e operação assistida.',
            'base_price' => 1200,
            'is_active' => true,
            'sort_order' => 5,
        ]);

        $response->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', [
            'name' => 'Cobrança consultiva',
            'slug' => 'cobranca-consultiva',
            'category' => 'Consultoria',
        ]);
    }

    public function test_sdr_cannot_manage_products(): void
    {
        $sdr = User::factory()->create(['role' => UserRole::Sdr]);

        $this->actingAs($sdr)->get(route('products.index'))->assertForbidden();
    }

    public function test_opportunity_can_be_linked_to_products(): void
    {
        $manager = User::factory()->create(['role' => UserRole::CommercialManager]);
        $company = Company::factory()->create(['responsible_user_id' => $manager->id]);
        $stage = PipelineDefaults::ensureDefaultPipeline()->stages()->firstOrFail();
        $product = Product::factory()->create();

        $response = $this->actingAs($manager)->post(route('opportunities.store'), [
            'company_id' => $company->id,
            'pipeline_stage_id' => $stage->id,
            'responsible_user_id' => $manager->id,
            'title' => 'Projeto com produto',
            'estimated_value' => 5000,
            'probability' => 50,
            'product_ids' => [$product->id],
        ]);

        $response->assertRedirect(route('opportunities.index'));

        $this->assertDatabaseHas('opportunity_product', [
            'product_id' => $product->id,
        ]);
    }
}
