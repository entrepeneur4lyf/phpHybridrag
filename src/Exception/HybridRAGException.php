<?php

declare(strict_types=1);

namespace HybridRAG\Exception;

/**
 * Class HybridRAGException
 *
 * This class represents a custom exception for the HybridRAG system.
 */
class HybridRAGException extends \Exception
{
    /**
     * HybridRAGException constructor.
     *
     * @param string $message The exception message
     * @param int $code The exception code
     * @param \Throwable|null $previous The previous throwable used for exception chaining
     */
    public function __construct(string $message, int $code = 0, \Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
