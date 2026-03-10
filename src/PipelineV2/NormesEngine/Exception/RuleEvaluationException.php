<?php

declare(strict_types=1);

namespace App\PipelineV2\NormesEngine\Exception;

/**
 * Exception lors de l'évaluation d'une règle.
 */
class RuleEvaluationException extends NormesEngineException
{
    public function __construct(
        public readonly string $ruleId,
        string $reason,
        array $contexte = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            message: "Erreur d'évaluation de la règle '{$ruleId}': {$reason}",
            contexte: array_merge(['rule_id' => $ruleId], $contexte),
            previous: $previous,
        );
    }
}
