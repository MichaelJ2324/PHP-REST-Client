<?php

namespace MRussell\REST\Traits;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

trait PsrLoggerTrait
{
    /**
     * The logger instance.
     */
    protected LoggerInterface $logger;

    /**
     * Sets a logger.
     */
    public function setLogger(LoggerInterface $logger): static
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * Get the Logger instance
     */
    public function getLogger(): LoggerInterface
    {
        if (!isset($this->logger)) {
            $this->setLogger(new NullLogger());
        }

        return $this->logger;
    }
}
