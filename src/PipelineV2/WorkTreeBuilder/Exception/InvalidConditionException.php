<?php

declare(strict_types=1);

namespace App\PipelineV2\WorkTreeBuilder\Exception;

/**
 * Levée quand une condition structurée est malformée ou utilise un opérateur inconnu.
 */
class InvalidConditionException extends WorkTreeBuilderException
{
    public function __construct(array $condition, string $raison)
    {
        parent::__construct(
            sprintf("Condition invalide : %s", $raison),
            [
                'condition' => $condition,
                'raison' => $raison,
            ]
        );
    }
}
