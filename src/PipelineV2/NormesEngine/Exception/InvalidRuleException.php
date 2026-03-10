<?php

declare(strict_types=1);

namespace App\PipelineV2\NormesEngine\Exception;

/**
 * Exception pour règle invalide ou mal configurée.
 */
class InvalidRuleException extends NormesEngineException
{
    public function __construct(
        public readonly string $ruleId,
        string $reason,
        array $ruleConfig = [],
    ) {
        parent::__construct(
            message: "Règle invalide '{$ruleId}': {$reason}",
            contexte: ['rule_id' => $ruleId, 'rule_config' => $ruleConfig],
        );
    }
}
