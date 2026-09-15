<?php

use App\Domain\Integration\IntegrationOperation;
use App\Domain\Integration\IntegrationScope;
use App\Http\Controllers\Api\V1\CorrespondenceIntegrationController;
use App\Http\Controllers\Api\V1\IntegrationProofWriteController;
use App\Http\Controllers\Api\V1\IntegrationSelfController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')
    ->middleware([
        'integration.correlation',
        'integration.auth',
    ])
    ->group(function (): void {
        Route::get('/integration/self', IntegrationSelfController::class)
            ->middleware([
                'integration.scope:'.IntegrationScope::SelfRead->value,
                'integration.rate',
                'integration.audit',
            ])
            ->name('api.v1.integration.self');

        Route::post('/integration/proof-writes', IntegrationProofWriteController::class)
            ->middleware([
                'integration.scope:'.IntegrationScope::ProofWrite->value,
                'integration.rate',
                'integration.idempotency:'.IntegrationOperation::ProofWrite->value,
                'integration.audit',
            ])
            ->name('api.v1.integration.proof-writes.store');

        Route::post('/correspondence', [CorrespondenceIntegrationController::class, 'store'])
            ->middleware([
                'integration.scope:'.IntegrationScope::CorrespondenceReceive->value,
                'integration.rate',
                'integration.idempotency:'.IntegrationOperation::CorrespondenceReceive->value,
                'integration.audit',
            ])
            ->name('api.v1.correspondence.store');

        Route::get('/correspondence/{publicId}', [CorrespondenceIntegrationController::class, 'show'])
            ->whereUuid('publicId')
            ->middleware([
                'integration.scope:'.IntegrationScope::CorrespondenceStatusRead->value,
                'integration.rate',
                'integration.audit',
            ])
            ->name('api.v1.correspondence.show');
    });
