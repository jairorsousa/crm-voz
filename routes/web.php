<?php

use App\Http\Controllers\CRM\ActivityController;
use App\Http\Controllers\CRM\CallController;
use App\Http\Controllers\CRM\CommercialAutomationController;
use App\Http\Controllers\CRM\CommunicationChannelController;
use App\Http\Controllers\CRM\CommunicationTemplateController;
use App\Http\Controllers\CRM\CommunicationWebhookController;
use App\Http\Controllers\CRM\CompanyController;
use App\Http\Controllers\CRM\CompanyTimelineController;
use App\Http\Controllers\CRM\ContactController;
use App\Http\Controllers\CRM\DashboardController;
use App\Http\Controllers\CRM\EmailCommunicationController;
use App\Http\Controllers\CRM\OpportunityController;
use App\Http\Controllers\CRM\PipelineController;
use App\Http\Controllers\CRM\ProductController;
use App\Http\Controllers\CRM\ReportController;
use App\Http\Controllers\CRM\SettingsController;
use App\Http\Controllers\CRM\WhatsappCommunicationController;
use App\Http\Controllers\ProfileController;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return Inertia::render('Auth/Login', [
        'canResetPassword' => Route::has('password.request'),
        'status' => session('status'),
    ]);
});

Route::get('/dashboard', DashboardController::class)
    ->middleware(['auth', 'verified'])
    ->can('viewDashboard', User::class)
    ->name('dashboard');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::resource('/empresas', CompanyController::class)
        ->parameters(['empresas' => 'company'])
        ->names('companies')
        ->middleware('can:viewCompanies,'.User::class);
    Route::get('/empresas/{company}/historico', CompanyTimelineController::class)
        ->can('viewCompanies', User::class)
        ->name('companies.timeline');

    Route::resource('/contatos', ContactController::class)
        ->parameters(['contatos' => 'contact'])
        ->except('show')
        ->names('contacts')
        ->middleware('can:viewContacts,'.User::class);

    Route::get('/pipeline', [PipelineController::class, 'index'])
        ->can('viewPipeline', User::class)
        ->name('pipeline.index');
    Route::patch('/pipeline/oportunidades/{opportunity}/mover', [PipelineController::class, 'move'])
        ->can('viewPipeline', User::class)
        ->name('pipeline.move');

    Route::resource('/oportunidades', OpportunityController::class)
        ->parameters(['oportunidades' => 'opportunity'])
        ->except('show')
        ->names('opportunities')
        ->middleware('can:viewOpportunities,'.User::class);

    Route::resource('/produtos', ProductController::class)
        ->parameters(['produtos' => 'product'])
        ->except('show', 'destroy')
        ->names('products')
        ->middleware('can:viewProducts,'.User::class);
    Route::patch('/produtos/{product}/alternar', [ProductController::class, 'toggle'])
        ->can('viewProducts', User::class)
        ->name('products.toggle');

    Route::resource('/atividades', ActivityController::class)
        ->parameters(['atividades' => 'activity'])
        ->except('show')
        ->names('activities')
        ->middleware('can:viewActivities,'.User::class);
    Route::patch('/atividades/{activity}/concluir', [ActivityController::class, 'complete'])
        ->can('viewActivities', User::class)
        ->name('activities.complete');
    Route::patch('/atividades/{activity}/cancelar', [ActivityController::class, 'cancel'])
        ->can('viewActivities', User::class)
        ->name('activities.cancel');
    Route::patch('/atividades/{activity}/reagendar', [ActivityController::class, 'reschedule'])
        ->can('viewActivities', User::class)
        ->name('activities.reschedule');

    Route::get('/ligacoes', [CallController::class, 'index'])
        ->can('viewCalls', User::class)
        ->name('calls.index');
    Route::post('/ligacoes', [CallController::class, 'store'])
        ->can('viewCalls', User::class)
        ->middleware('throttle:communications')
        ->name('calls.store');
    Route::post('/ligacoes/token', [CallController::class, 'token'])
        ->can('viewCalls', User::class)
        ->middleware('throttle:communications')
        ->name('calls.token');
    Route::patch('/ligacoes/{message}', [CallController::class, 'update'])
        ->can('viewCalls', User::class)
        ->name('calls.update');

    Route::get('/emails', [EmailCommunicationController::class, 'index'])
        ->can('viewEmails', User::class)
        ->name('emails.index');
    Route::get('/emails/create', [EmailCommunicationController::class, 'create'])
        ->can('viewEmails', User::class)
        ->name('emails.create');
    Route::post('/emails', [EmailCommunicationController::class, 'store'])
        ->can('viewEmails', User::class)
        ->middleware('throttle:communications')
        ->name('emails.store');

    Route::get('/whatsapp', [WhatsappCommunicationController::class, 'index'])
        ->can('viewWhatsapp', User::class)
        ->name('whatsapp.index');
    Route::get('/whatsapp/create', [WhatsappCommunicationController::class, 'create'])
        ->can('viewWhatsapp', User::class)
        ->name('whatsapp.create');
    Route::post('/whatsapp', [WhatsappCommunicationController::class, 'store'])
        ->can('viewWhatsapp', User::class)
        ->middleware('throttle:communications')
        ->name('whatsapp.store');

    Route::resource('/modelos', CommunicationTemplateController::class)
        ->parameters(['modelos' => 'template'])
        ->except('show')
        ->names('templates')
        ->middleware('can:viewTemplates,'.User::class);
    Route::patch('/modelos/{template}/alternar', [CommunicationTemplateController::class, 'toggle'])
        ->can('viewTemplates', User::class)
        ->name('templates.toggle');

    Route::resource('/canais', CommunicationChannelController::class)
        ->parameters(['canais' => 'channel'])
        ->except('show', 'destroy')
        ->names('channels')
        ->middleware('can:viewChannels,'.User::class);
    Route::patch('/canais/{channel}/alternar', [CommunicationChannelController::class, 'toggle'])
        ->can('viewChannels', User::class)
        ->name('channels.toggle');

    Route::get('/automacoes', [CommercialAutomationController::class, 'index'])
        ->can('viewAutomations', User::class)
        ->name('automations.index');
    Route::patch('/automacoes/{automation}/alternar', [CommercialAutomationController::class, 'toggle'])
        ->can('viewAutomations', User::class)
        ->name('automations.toggle');
    Route::post('/automacoes/executar-checks', [CommercialAutomationController::class, 'runChecks'])
        ->can('viewAutomations', User::class)
        ->name('automations.run-checks');

    Route::get('/relatorios', [ReportController::class, 'index'])
        ->can('viewReports', User::class)
        ->name('reports.index');
    Route::get('/relatorios/exportar/{report}/{format}', [ReportController::class, 'export'])
        ->can('viewReports', User::class)
        ->middleware('throttle:reports')
        ->name('reports.export');
    Route::post('/relatorios/exportacoes/{report}/{format}', [ReportController::class, 'queueExport'])
        ->can('viewReports', User::class)
        ->middleware('throttle:reports')
        ->name('reports.exports.queue');
    Route::get('/relatorios/exportacoes/{export}/download', [ReportController::class, 'download'])
        ->can('viewReports', User::class)
        ->name('reports.exports.download');

    Route::get('/configuracoes', [SettingsController::class, 'index'])
        ->can('viewSettings', User::class)
        ->name('settings.index');
    Route::patch('/configuracoes/geral', [SettingsController::class, 'updateGeneral'])
        ->can('viewSettings', User::class)
        ->middleware('throttle:settings')
        ->name('settings.general.update');
    Route::patch('/configuracoes/integracoes/{integration}', [SettingsController::class, 'updateIntegration'])
        ->can('viewSettings', User::class)
        ->middleware('throttle:settings')
        ->name('settings.integrations.update');
    Route::patch('/configuracoes/usuarios/{user}', [SettingsController::class, 'updateUser'])
        ->can('viewSettings', User::class)
        ->middleware('throttle:settings')
        ->name('settings.users.update');
    Route::patch('/configuracoes/pipeline/etapas/{stage}', [SettingsController::class, 'updateStage'])
        ->can('viewSettings', User::class)
        ->middleware('throttle:settings')
        ->name('settings.stages.update');
    Route::post('/configuracoes/opcoes', [SettingsController::class, 'storeOption'])
        ->can('viewSettings', User::class)
        ->middleware('throttle:settings')
        ->name('settings.options.store');
    Route::patch('/configuracoes/opcoes/{option}', [SettingsController::class, 'updateOption'])
        ->can('viewSettings', User::class)
        ->middleware('throttle:settings')
        ->name('settings.options.update');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::post('/webhooks/twilio/calls', [CommunicationWebhookController::class, 'twilioCall'])
    ->middleware('throttle:webhooks')
    ->name('webhooks.twilio.calls');
Route::post('/twilio/voice', [CommunicationWebhookController::class, 'twilioVoice'])
    ->middleware('throttle:webhooks')
    ->name('twilio.voice');
Route::post('/webhooks/evolution/whatsapp', [CommunicationWebhookController::class, 'evolutionWhatsapp'])
    ->middleware('throttle:webhooks')
    ->name('webhooks.evolution.whatsapp');

require __DIR__.'/auth.php';
