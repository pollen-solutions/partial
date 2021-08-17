<?php

declare(strict_types=1);

namespace Pollen\Partial;

use Pollen\Http\ResponseInterface;
use Pollen\Routing\Exception\NotFoundException;
use Pollen\Support\Concerns\BootableTraitInterface;
use Pollen\Support\Concerns\ConfigBagAwareTraitInterface;
use Pollen\Support\Concerns\ResourcesAwareTraitInterface;
use Pollen\Support\Proxy\ContainerProxyInterface;
use Pollen\Support\Proxy\RouterProxyInterface;

interface PartialManagerInterface extends
    BootableTraitInterface,
    ConfigBagAwareTraitInterface,
    ResourcesAwareTraitInterface,
    ContainerProxyInterface,
    RouterProxyInterface
{
    /**
     * Returns the list of registered drivers instances.
     *
     * @return array<string, array<string, PartialDriverInterface>>|array
     */
    public function all(): array;

    /**
     * Booting.
     *
     * @return static
     */
    public function boot(): PartialManagerInterface;

    /**
     * Gets a registered driver instance.
     *
     * @param string $alias
     * @param string|array|null $idOrParams
     * @param array|null $params
     *
     * @return PartialDriverInterface|null
     */
    public function get(string $alias, $idOrParams = null, ?array $params = []): ?PartialDriverInterface;

    /**
     * Get the route url of a driver HTTP request handle.
     *
     * @param string $partial
     * @param string|null $controller
     * @param array $params
     * @param string|null $httpMethod
     *
     * @return string|null
     */
    public function getRouteUrl(
        string $partial,
        ?string $controller = null,
        array $params = [],
        ?string $httpMethod = null
    ): ?string;

    /**
     * HTTP request dispatcher for a partial driver.
     *
     * @param string $partial
     * @param string $controller
     * @param mixed ...$args
     *
     * @return ResponseInterface
     *
     * @throws NotFoundException
     */
    public function httpRequestDispatcher(string $partial, string $controller, ...$args): ResponseInterface;

    /**
     * Register a driver.
     *
     * @param string $alias
     * @param string|PartialDriverInterface|callable|null $driverDefinition
     * @param callable|null $registerCallback
     *
     * @return static
     */
    public function register(
        string $alias,
        $driverDefinition = null,
        ?callable $registerCallback = null
    ): PartialManagerInterface;
}