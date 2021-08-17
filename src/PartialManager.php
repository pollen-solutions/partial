<?php

declare(strict_types=1);

namespace Pollen\Partial;

use Exception;
use InvalidArgumentException;
use Pollen\Support\Concerns\ResourcesAwareTrait;
use Pollen\Support\Proxy\RouterProxy;
use Pollen\Http\ResponseInterface;
use Pollen\Partial\Drivers\TagDriver;
use Pollen\Routing\RouteInterface;
use Pollen\Support\Concerns\BootableTrait;
use Pollen\Support\Concerns\ConfigBagAwareTrait;
use Pollen\Support\Exception\ManagerRuntimeException;
use Pollen\Support\Proxy\ContainerProxy;
use Pollen\Routing\Exception\NotFoundException;
use Psr\Container\ContainerInterface as Container;
use RuntimeException;

class PartialManager implements PartialManagerInterface
{
    use BootableTrait;
    use ConfigBagAwareTrait;
    use ResourcesAwareTrait;
    use ContainerProxy;
    use RouterProxy;

    /**
     * Partial manager main instance.
     * @var PartialManagerInterface|null
     */
    private static ?PartialManagerInterface $instance = null;

    /**
     * List of default registered drivers classname.
     * @var array<string, string>
     */
    private array $defaultDrivers = [
        'tag' => TagDriver::class,
    ];

    /**
     * List of registered driver instances.
     * @var array<string, array<string, PartialDriverInterface>>|array
     */
    private array $drivers = [];

    /**
     * List of registered driver definitions.
     * @var array<string, PartialDriverInterface|callable|string>|array
     */
    protected array $driverDefinitions = [];

    /**
     * List of routes instance by method.
     * @var array<string, RouteInterface>|array
     */
    protected array $routes = [];

    /**
     * @param array $config
     * @param Container|null $container
     */
    public function __construct(array $config = [], ?Container $container = null)
    {
        $this->setConfig($config);

        if ($container !== null) {
            $this->setContainer($container);
        }

        $this->setResourcesBaseDir(dirname(__DIR__) . '/resources');

        $this->boot();

        if (!self::$instance instanceof static) {
            self::$instance = $this;
        }
    }

    /**
     * Retrieves the partial manager main instance.
     *
     * @return static
     */
    public static function getInstance(): PartialManagerInterface
    {
        if (self::$instance instanceof self) {
            return self::$instance;
        }
        throw new ManagerRuntimeException(sprintf('Unavailable [%s] instance', __CLASS__));
    }

    /**
     * @inheritDoc
     */
    public function all(): array
    {
        return $this->drivers;
    }

    /**
     * @inheritDoc
     */
    public function boot(): PartialManagerInterface
    {
        if (!$this->isBooted()) {
            if ($router = $this->router()) {
                $this->routes['get'] = $router->get(
                    '/_partial/{partial}/{controller}',
                    [$this, 'httpRequestDispatcher']
                );
                $this->routes['post'] = $router->post(
                    '/_partial/{partial}/{controller}',
                    [$this, 'httpRequestDispatcher']
                );
                $this->routes['put'] = $router->put(
                    '/_partial/{partial}/{controller}',
                    [$this, 'httpRequestDispatcher']
                );
                $this->routes['patch'] = $router->patch(
                    '/_partial/{partial}/{controller}',
                    [$this, 'httpRequestDispatcher']
                );
                $this->routes['options'] = $router->options(
                    '/_partial/{partial}/{controller}',
                    [$this, 'httpRequestDispatcher']
                );
                $this->routes['delete'] = $router->delete(
                    '/_partial/{partial}/{controller}',
                    [$this, 'httpRequestDispatcher']
                );
                $this->routes['api'] = $router->xhr(
                    '/api/_partial/{partial}/{controller}',
                    [$this, 'httpRequestDispatcher']
                );
            }

            $this->registerDefaultDrivers();

            $this->setBooted();
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function get(string $alias, $idOrParams = null, ?array $params = []): ?PartialDriverInterface
    {
        if (is_array($idOrParams)) {
            $params = (array)$idOrParams;
            $id = null;
        } else {
            $id = $idOrParams;
        }

        if ($id !== null && isset($this->drivers[$alias][$id])) {
            return $this->drivers[$alias][$id];
        }

        if (!$driver = $this->resolveDriverFromDefinition($alias)) {
            return null;
        }

        $this->drivers[$alias] = $this->drivers[$alias] ?? [];
        $index = count($this->drivers[$alias]);
        $id = $id ?? $alias . $index;
        if (!$driver->getAlias()) {
            $driver->setAlias($alias);
        }
        $params = array_merge($driver->defaultParams(), $this->config("driver.$alias", []), $params ?: []);

        $driver->setIndex($index)->setId($id)->setParams($params);
        $driver->boot();

        return $this->drivers[$alias][$id] = $driver;
    }

    /**
     * @inheritDoc
     */
    public function getRouteUrl(
        string $partial,
        ?string $controller = null,
        array $params = [],
        ?string $httpMethod = null
    ): ?string {
        if (!$this->router()) {
            return null;
        }

        $route = $this->routes[$httpMethod ?? 'api'] ?? null;

        if (!$route instanceof RouteInterface) {
            if ($httpMethod === null || $httpMethod === 'api') {
                throw new RuntimeException(sprintf(
                    'The api route for route for the [%s] partial driver is not available.', $partial
                ));
            } else {
                throw new RuntimeException(sprintf(
                    'The web route for HTTP method [%s] the [%s] partial driver is not available.',
                    $partial,
                    $httpMethod
                ));
            }
        }

        $controller = $controller ?? 'responseController';

        return $this->router()->getRouteUrl($route, array_merge($params, compact('partial', 'controller')));
    }

    /**
     * @inheritDoc
     */
    public function httpRequestDispatcher(string $partial, string $controller, ...$args): ResponseInterface
    {
        try {
            $driver = $this->get($partial);
        } catch (Exception $e) {
            throw new NotFoundException(
                sprintf('PartialDriver [%s] return exception : %s.', $partial, $e->getMessage()),
                'PartialDriver Error',
                $e
            );
        }

        if ($driver !== null) {
            try {
                return $driver->{$controller}(...$args);
            } catch (Exception $e) {
                throw new NotFoundException(
                    sprintf('PartialDriver [%s] Controller [%s] call return exception.', $controller, $partial),
                    'PartialDriver Error',
                    $e
                );
            }
        }

        throw new NotFoundException(
            sprintf('PartialDriver [%s] unreachable.', $partial),
            'PartialDriver Error'
        );
    }

    /**
     * @inheritDoc
     */
    public function register(
        string $alias,
        $driverDefinition = null,
        ?callable $registerCallback = null
    ): PartialManagerInterface {
        $this->driverDefinitions[$alias] = $driverDefinition ?? TagDriver::class;

        if ($registerCallback !== null) {
            $registerCallback($this);
        }
        return $this;
    }

    /**
     * Default drivers registration.
     *
     * @return static
     */
    protected function registerDefaultDrivers(): PartialManagerInterface
    {
        foreach ($this->defaultDrivers as $alias => $driverDefinition) {
            $this->register($alias, $driverDefinition);
        }
        return $this;
    }

    /**
     * Resolves the driver instance related of a driver definition from its alias.
     *
     * @param string $alias
     *
     * @return PartialDriverInterface|null
     */
    protected function resolveDriverFromDefinition(string $alias): ?PartialDriverInterface
    {
        if (!$def = $this->driverDefinitions[$alias] ?? null) {
            throw new InvalidArgumentException(sprintf('Partial with alias [%s] unavailable', $alias));
        }

        $driver = null;

        if ($def instanceof PartialDriverInterface) {
            $driver = clone $def;
        } elseif (is_string($def) && !is_callable($def)) {
            if ($this->containerHas($def)) {
                $driver = clone $this->containerGet($def);
            } elseif (class_exists($def)) {
                $driver = new $def($this);
            }
        } elseif(is_callable($def)) {
            $driver = new CallablePartialDriver($def);
        }

        if ($driver instanceof PartialDriverInterface) {
            $driver->setPartialManager($this);

            return $driver;
        }

        return null;
    }
}