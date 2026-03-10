<?php

declare(strict_types=1);

namespace App\PipelineV2\WorkTreeBuilder\Exception;

/**
 * Levée quand le contexte fourni est insuffisant pour construire l'arbre
 * et qu'aucune valeur par défaut n'est disponible pour les champs obligatoires.
 */
class ContexteIncompletException extends WorkTreeBuilderException
{
    public function __construct(array $champManquants)
    {
        parent::__construct(
            sprintf(
                "Contexte incomplet : champs obligatoires manquants : %s",
                implode(', ', $champManquants)
            ),
            [
                'champs_manquants' => $champManquants,
            ]
        );
    }
}
