<?php

declare(strict_types=1);

namespace Mautic\MessengerBundle\Message\Traits;

use Symfony\Component\HttpFoundation\Request;

trait MessageRequestTrait
{
    private ?\DateTimeInterface $eventTime = null;
    private Request $request;
    private bool $isSynchronous = false;

    public function getEventTime(): ?\DateTimeInterface
    {
        return $this->eventTime;
    }

    public function setEventTime(\DateTimeInterface $eventTime = null): self
    {
        $this->eventTime = $eventTime;

        return $this;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function setIsSynchronousRequest(bool $isSynchronous = true): self
    {
        $this->isSynchronous = $isSynchronous;

        return $this;
    }

    public function isSynchronousRequest(): bool
    {
        return $this->isSynchronous;
    }
}