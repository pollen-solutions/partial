<?php

declare(strict_types=1);

namespace Pollen\Partial;

class CallablePartialDriver extends PartialDriver
{
    /**
     * Callback render.
     * @var callable
     */
    private $renderCallback;

    /**
     * @param callable $renderCallback
     * @param PartialManagerInterface|null $partialManager
     */
    public function __construct(callable $renderCallback, ?PartialManagerInterface $partialManager = null)
    {
        $this->renderCallback = $renderCallback;

        parent::__construct($partialManager);
    }

    /**
     * @inheritDoc
     */
    public function render(): string
    {
        $renderCallback = $this->renderCallback;

        return $renderCallback($this);
    }
}
