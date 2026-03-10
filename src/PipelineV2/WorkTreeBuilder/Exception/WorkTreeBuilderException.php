<?php

declare(strict_types=1);

namespace App\PipelineV2\WorkTreeBuilder\Exception;

use Exception;

/**
 * Exception de base pour toutes les erreurs du WorkTreeBuilder.
 */
class WorkTreeBuilderException extends Exception
{
    protected array $context = [];

    public function __construct(string $message, array $context = [], int $code = 0, ?\Throwable $previous = null)
    {
        $this->context = $context;
        parent::__construct($message, $code, $previous);
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
