<?php

namespace App\Providers;

use App\Domain\Workflow\Events\WorkflowTransactionCreated;
use App\Domain\Workflow\Events\WorkflowTransactionTransitioned;
use App\Domain\Workflow\Listeners\AuditWorkflowTransactionMutation;
use App\Domain\Workflow\Listeners\NotifyWorkflowTransactionMutation;
use App\Domain\Workflow\Listeners\SyncWorkflowTransactionCalendar;
use App\Models\WorkflowTransaction;
use App\Policies\TransactionPolicy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
    }

    public function boot(): void
    {
        if (str_starts_with((string) config('app.url'), 'https://') || $this->app->environment('production')) {
            URL::forceScheme('https');
        }

        Gate::policy(WorkflowTransaction::class, TransactionPolicy::class);
        $this->registerWorkflowListeners();
    }

    private function registerWorkflowListeners(): void
    {
        Event::listen(WorkflowTransactionCreated::class, AuditWorkflowTransactionMutation::class);
        Event::listen(WorkflowTransactionCreated::class, NotifyWorkflowTransactionMutation::class);
        Event::listen(WorkflowTransactionCreated::class, SyncWorkflowTransactionCalendar::class);
        Event::listen(WorkflowTransactionTransitioned::class, AuditWorkflowTransactionMutation::class);
        Event::listen(WorkflowTransactionTransitioned::class, NotifyWorkflowTransactionMutation::class);
        Event::listen(WorkflowTransactionTransitioned::class, SyncWorkflowTransactionCalendar::class);
    }
}
