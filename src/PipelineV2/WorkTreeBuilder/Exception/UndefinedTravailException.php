<?php

declare(strict_types=1);

namespace App\PipelineV2\WorkTreeBuilder\Exception;

/**
 * Levée quand un travail référencé dans chantier_types.php
 * n'existe pas dans travaux_definitions.php.
 */
class UndefinedTravailException extends WorkTreeBuilderException
{
    public function __construct(string $travailId, string $typeChantier)
    {
        parent::__construct(
            sprintf(
                "Travail '%s' référencé dans le chantier '%s' mais non défini dans travaux_definitions.php",
                $travailId,
                $typeChantier
            ),
            [
                'travail_id' => $travailId,
                'type_chantier' => $typeChantier,
            ]
        );
    }
}
