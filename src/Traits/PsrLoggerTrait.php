<?php

namespace MRussell\REST\Traits;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

trait PsrLoggerTrait
{
    /**
     * The logger instance.
     *
     * @var LoggerInterface|null
     */
    protected $logger;

    /**
     * Sets a logger.
     */
    public function setLogger(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Get the Logger instance
     *
     * @return LoggerInterface
     */
    public function getLogger()
    {
        if (!isset($this->logger)) {
            $this->setLogger(new NullLogger());
        }

        return $this->logger;
    }
}
