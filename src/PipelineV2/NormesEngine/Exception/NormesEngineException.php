<?php

declare(strict_types=1);

namespace App\PipelineV2\NormesEngine\Exception;

use RuntimeException;

/**
 * Exception de base du NormesEngine.
 */
class NormesEngineException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly array $contexte = [],
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
