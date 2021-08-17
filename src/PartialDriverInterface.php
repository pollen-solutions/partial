<?php

declare(strict_types=1);

namespace Pollen\Partial;

use Pollen\Http\ResponseInterface;
use Pollen\Support\Concerns\ParamsBagDelegateTraitInterface;
use Pollen\Support\Proxy\HttpRequestProxyInterface;
use Pollen\Support\Proxy\PartialProxyInterface;
use Pollen\Support\Proxy\ViewProxyInterface;
use Pollen\View\ViewInterface;

interface PartialDriverInterface extends
    HttpRequestProxyInterface,
    ParamsBagDelegateTraitInterface,
    PartialProxyInterface,
    ViewProxyInterface
{
    /**
     * Resolves class as a string and returns the render.
     *
     * @return string
     */
    public function __toString(): string;

    /**
     * Content render displayed after the main container.
     *
     * @return void
     */
    public function after(): void;

    /**
     * Content render displayed before the main container.
     *
     * @return void
     */
    public function before(): void;

    /**
     * Booting.
     *
     * @return void
     */
    public function boot(): void;

    /**
     * Gets alias identifier.
     *
     * @return string
     */
    public function getAlias(): string;

    /**
     * Gets the base prefix of HTML class.
     *
     * @return string
     */
    public function getBaseClass(): string;

    /**
     * Gets the unique identifier.
     *
     * @return string
     */
    public function getId(): string;

    /**
     * Gets the index in related partial manager.
     *
     * @return int
     */
    public function getIndex(): int;

    /**
     * Gets the handle route url.
     *
     * @param string|null $controllerMethod
     * @param array $params
     * @param string|null $httpMethod
     *
     * @return string
     */
    public function getRouteUrl(
        ?string $controllerMethod = null,
        array $params = [],
        ?string $httpMethod = null
    ): string;

    /**
     * Parse the HTML class attribute of main container.
     *
     * @return static
     */
    public function parseAttrClass(): PartialDriverInterface;

    /**
     * Parse the HTML tag attribute of main container.
     *
     * @return static
     */
    public function parseAttrId(): PartialDriverInterface;

    /**
     * Render.
     *
     * @return string
     */
    public function render(): string;

    /**
     * Sets the alias identifier.
     *
     * @param string $alias
     *
     * @return static
     */
    public function setAlias(string $alias): PartialDriverInterface;

    /**
     * Sets the default parameters.
     *
     * @param array $defaults
     *
     * @return void
     */
    public static function setDefaults(array $defaults = []): void;

    /**
     * Sets the identifier.
     *
     * @param string $id
     *
     * @return static
     */
    public function setId(string $id): PartialDriverInterface;

    /**
     * Sets the index in partial manager.
     *
     * @param int $index
     *
     * @return static
     */
    public function setIndex(int $index): PartialDriverInterface;

    /**
     * Resolves view instance|returns a particular template render.
     *
     * @param string|null $name .
     * @param array $data
     *
     * @return ViewInterface|string
     */
    public function view(?string $name = null, array $data = []);

    /**
     * Gets the absolute path to the template directory.
     *
     * @return string|null
     */
    public function viewDirectory(): ?string;

    /**
     * Route controller method.
     *
     * @param array ...$args
     *
     * @return ResponseInterface
     */
    public function responseController(...$args): ResponseInterface;
}