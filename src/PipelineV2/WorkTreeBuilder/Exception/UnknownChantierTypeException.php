<?php

declare(strict_types=1);

namespace App\PipelineV2\WorkTreeBuilder\Exception;

/**
 * Levée quand le type de chantier demandé n'existe pas dans la configuration.
 */
class UnknownChantierTypeException extends WorkTreeBuilderException
{
    public function __construct(string $typeChantier, array $typesDisponibles = [])
    {
        parent::__construct(
            sprintf("Type de chantier inconnu : '%s'", $typeChantier),
            [
                'type_demande' => $typeChantier,
                'types_disponibles' => $typesDisponibles,
            ]
        );
    }
}
