<?php

namespace App\Providers;

use App\Models\Activity;
use App\Models\CommunicationMessage;
use App\Models\Company;
use App\Models\Opportunity;
use App\Models\OpportunityStageMovement;
use App\Models\PipelineStage;
use App\Models\TimelineEvent;
use App\Models\User;
use App\Policies\UserPolicy;
use App\Support\CRM\DashboardMetrics;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(User::class, UserPolicy::class);
        $this->configureRateLimiting();

        Validator::extendImplicit('required_if_stage_lost', function (string $attribute, mixed $value, array $parameters): bool {
            $stageId = request()->input($parameters[0] ?? 'pipeline_stage_id');

            if (! $stageId) {
                return true;
            }

            $stage = PipelineStage::query()->find($stageId);

            return ! $stage?->is_lost || filled($value);
        }, 'Este campo é obrigatório ao mover para Fechado perdido.');

        Validator::extendImplicit('required_if_stage_won', function (string $attribute, mixed $value, array $parameters): bool {
            $stageId = request()->input($parameters[0] ?? 'pipeline_stage_id');

            if (! $stageId) {
                return true;
            }

            $stage = PipelineStage::query()->find($stageId);

            return ! $stage?->is_won || filled($value);
        }, 'Este campo é obrigatório ao mover para Fechado ganho.');

        foreach ([Company::class, Opportunity::class, OpportunityStageMovement::class, Activity::class, TimelineEvent::class, CommunicationMessage::class] as $model) {
            $model::saved(fn () => DashboardMetrics::invalidate());
            $model::deleted(fn () => DashboardMetrics::invalidate());
        }

        Vite::prefetch(concurrency: 3);
    }

    private function configureRateLimiting(): void
    {
        RateLimiter::for('communications', fn (Request $request): Limit => Limit::perMinute(30)->by(
            'communications:'.($request->user()?->id ?: $request->ip())
        ));

        RateLimiter::for('settings', fn (Request $request): Limit => Limit::perMinute(40)->by(
            'settings:'.($request->user()?->id ?: $request->ip())
        ));

        RateLimiter::for('reports', fn (Request $request): Limit => Limit::perMinute(20)->by(
            'reports:'.($request->user()?->id ?: $request->ip())
        ));

        RateLimiter::for('webhooks', fn (Request $request): Limit => Limit::perMinute(120)->by(
            'webhooks:'.$request->ip()
        ));
    }
}
