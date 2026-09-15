<?php

namespace Tests\Feature;

use App\Domain\Workflow\SlaResolver;
use App\Domain\Workflow\WorkflowDefinitionResolver;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class WorkflowDefinitionNormalizationTest extends TestCase
{
    public function test_existing_workflow_vocabulary_is_resolved_from_definition(): void
    {
        $definition = app(WorkflowDefinitionResolver::class)->resolve('funding_request');

        $this->assertSame([
            'assign',
            'mark_review',
            'forward',
            'send_to_mayor',
            'return_origin',
            'request_information',
            'approve',
            'disapprove',
        ], $definition->actions());

        $this->assertSame('for_approval', $definition->transition('send_to_mayor')->status);
        $this->assertSame('MAYOR', $definition->transition('send_to_mayor')->officeCode);
        $this->assertTrue($definition->isTerminal('approved'));
        $this->assertFalse($definition->isTerminal('for_review'));
    }

    public function test_transaction_type_can_override_routing_rule_without_service_code_change(): void
    {
        config()->set('workflow.types.funding_request.transitions.send_to_mayor.office_code', 'BUDGET');

        $definition = app(WorkflowDefinitionResolver::class)->resolve('funding_request');

        $this->assertSame('BUDGET', $definition->transition('send_to_mayor')->officeCode);
    }

    public function test_sla_resolver_uses_configurable_priority_days(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-22 08:00:00'));
        config()->set('workflow.sla.priority_days.urgent', 2);

        $dueAt = app(SlaResolver::class)->resolve('urgent');

        $this->assertSame('2026-08-24 23:59:59', $dueAt->format('Y-m-d H:i:s'));

        Carbon::setTestNow();
    }

    public function test_unsupported_action_is_rejected_by_definition(): void
    {
        $this->expectException(ValidationException::class);

        app(WorkflowDefinitionResolver::class)
            ->resolve('funding_request')
            ->transition('unknown_action');
    }
}
