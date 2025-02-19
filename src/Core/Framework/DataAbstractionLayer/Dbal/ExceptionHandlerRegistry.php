<?php declare(strict_types=1);

namespace Shopware\Core\Framework\DataAbstractionLayer\Dbal;

use Shopware\Core\Framework\Log\Package;

/**
 * @internal
 */
#[Package('framework')]
class ExceptionHandlerRegistry
{
    /**
     * @var array<int, list<ExceptionHandlerInterface>>
     */
    protected array $exceptionHandlers = [];

    /**
     * @internal
     *
     * @param iterable<ExceptionHandlerInterface> $exceptionHandlers
     */
    public function __construct(iterable $exceptionHandlers)
    {
        foreach ($exceptionHandlers as $exceptionHandler) {
            $this->add($exceptionHandler);
        }
    }

    public function add(ExceptionHandlerInterface $exceptionHandler): void
    {
        $this->exceptionHandlers[$exceptionHandler->getPriority()][] = $exceptionHandler;
    }

    public function matchException(\Throwable $e): ?\Throwable
    {
        foreach ($this->getExceptionHandlers() as $priorityExceptionHandlers) {
            foreach ($priorityExceptionHandlers as $exceptionHandler) {
                $innerException = $exceptionHandler->matchException($e);

                if ($innerException !== null) {
                    return $innerException;
                }
            }
        }

        return null;
    }

    /**
     * @return array<int, list<ExceptionHandlerInterface>>
     */
    public function getExceptionHandlers(): array
    {
        return $this->exceptionHandlers;
    }
}
