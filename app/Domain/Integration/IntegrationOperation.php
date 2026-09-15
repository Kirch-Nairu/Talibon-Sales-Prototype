<?php

namespace App\Domain\Integration;

enum IntegrationOperation: string
{
    case ProofWrite = 'integration.proof.write';
    case CorrespondenceReceive = 'correspondence.receive';
}
