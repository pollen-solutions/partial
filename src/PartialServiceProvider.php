<?php

declare(strict_types=1);

namespace Pollen\Partial;

use Pollen\Container\ServiceProvider;
use Pollen\Partial\Drivers\TagDriver;

class PartialServiceProvider extends ServiceProvider
{
    /**
     * @var string[]
     */
    protected $provides = [
        PartialManagerInterface::class,
        TagDriver::class,
    ];

    /**
     * @inheritDoc
     */
    public function register(): void
    {
        $this->getContainer()->share(
            PartialManagerInterface::class,
            function () {
                return new PartialManager([], $this->getContainer());
            }
        );
        $this->registerDrivers();
    }

    /**
     * Register default driver services.
     *
     * @return void
     */
    public function registerDrivers(): void
    {
        $this->getContainer()->add(
            TagDriver::class,
            function () {
                return new TagDriver($this->getContainer()->get(PartialManagerInterface::class));
            }
        );
    }
}