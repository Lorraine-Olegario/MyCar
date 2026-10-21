<?php

namespace App\Domain\Fipe;

use RuntimeException;
use Throwable;

/**
 * Falha ao consultar a FIPE. Sempre traz uma mensagem em português para o usuário;
 * a aplicação nunca inventa valor quando a consulta falha.
 */
class FipeException extends RuntimeException
{
    public const UNAVAILABLE = 'unavailable';

    public const TIMEOUT = 'timeout';

    public const NOT_FOUND = 'not_found';

    public const MALFORMED = 'malformed';

    private function __construct(public readonly string $reason, string $message, ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }

    public static function unavailable(?Throwable $previous = null): self
    {
        return new self(self::UNAVAILABLE, 'A Tabela FIPE está indisponível no momento. Tente novamente em alguns minutos.', $previous);
    }

    public static function timeout(?Throwable $previous = null): self
    {
        return new self(self::TIMEOUT, 'A consulta à Tabela FIPE demorou demais para responder. Tente novamente.', $previous);
    }

    public static function notFound(): self
    {
        return new self(self::NOT_FOUND, 'A FIPE não encontrou essa combinação de marca, modelo e ano.');
    }

    public static function malformed(?Throwable $previous = null): self
    {
        return new self(self::MALFORMED, 'A FIPE devolveu uma resposta que não foi possível interpretar.', $previous);
    }
}
