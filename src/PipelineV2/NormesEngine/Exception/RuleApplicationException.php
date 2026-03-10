<?php

declare(strict_types=1);

namespace App\PipelineV2\NormesEngine\Exception;

/**
 * Exception lors de l'application d'une action de règle.
 */
class RuleApplicationException extends NormesEngineException
{
    public function __construct(
        public readonly string $ruleId,
        public readonly string $actionType,
        string $reason,
        array $contexte = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct(
            message: "Erreur d'application de l'action '{$actionType}' pour la règle '{$ruleId}': {$reason}",
            contexte: array_merge(['rule_id' => $ruleId, 'action_type' => $actionType], $contexte),
            previous: $previous,
        );
    }
}
