<?php

declare(strict_types=1);

namespace Pollen\Partial;

use Closure;
use InvalidArgumentException;
use Pollen\Http\Response;
use Pollen\Http\ResponseInterface;
use Pollen\Support\Concerns\BootableTrait;
use Pollen\Support\Concerns\ParamsBagDelegateTrait;
use Pollen\Support\Proxy\HttpRequestProxy;
use Pollen\Support\Proxy\PartialProxy;
use Pollen\Support\Proxy\ViewProxy;
use Pollen\Support\Str;
use Pollen\View\ViewInterface;

abstract class PartialDriver implements PartialDriverInterface
{
    use BootableTrait;
    use HttpRequestProxy;
    use ParamsBagDelegateTrait;
    use PartialProxy;
    use ViewProxy;

    /**
     * Index value in partial manager.
     * @var int
     */
    private int $index = 0;

    /**
     * Identifier alias.
     * @var string
     */
    protected string $alias = '';

    /**
     * List of default HTML tag attributes of main container.
     * @var array
     */
    protected static array $defaults = [];

    /**
     * Identifier Id.
     * {@internal {{ alias }}-{{ index }} by default.}
     */
    protected string $id = '';

    /**
     * Template view instance.
     * @var ViewInterface|null
     */
    protected ?ViewInterface $view = null;

    /**
     * @param PartialManagerInterface|null $partialManager
     */
    public function __construct(?PartialManagerInterface $partialManager = null)
    {
        if ($partialManager !== null) {
            $this->setPartialManager($partialManager);
        }
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return $this->render();
    }

    /**
     * @inheritDoc
     */
    public function after(): void
    {
        echo ($after = $this->get('after')) instanceof Closure ? $after($this) : $after;
    }

    /**
     * @inheritDoc
     */
    public function before(): void
    {
        echo ($before = $this->get('before')) instanceof Closure ? $before($this) : $before;
    }

    /**
     * @inheritDoc
     */
    public function boot(): void
    {
        if (!$this->isBooted()) {
            $this->parseParams();

            $this->setBooted();
        }
    }

    /**
     * @inheritDoc
     *
     * @return array
     */
    public function defaultParams(): array
    {
        return array_merge(
            self::$defaults[__CLASS__] ?? [],
            [
                /**
                 * Main container HTML tag attributes.
                 * @var array $attrs
                 */
                'attrs'   => [],
                /**
                 * Content displayed after the main container.
                 * @var string|callable $after
                 */
                'after'   => '',
                /**
                 * Content displayed before the main container.
                 * @var string|callable $before
                 */
                'before'  => '',
                /**
                 * List of parameters of the template view|View instance.
                 * @var array|ViewInterface $view
                 */
                'view'  => []
            ]
        );
    }

    /**
     * @inheritDoc
     */
    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * @inheritDoc
     */
    public function getBaseClass(): string
    {
        return Str::studly($this->getAlias());
    }

    /**
     * @inheritDoc
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @inheritDoc
     */
    public function getIndex(): int
    {
        return $this->index;
    }

    /**
     * @inheritDoc
     */
    public function getRouteUrl(
        ?string $controllerMethod = null,
        array $params = [],
        ?string $httpMethod = null
    ): string {
        return $this->partial()->getRouteUrl($this->getAlias(), $controllerMethod, $params, $httpMethod);
    }

    /**
     * @inheritDoc
     */
    public function parseParams(): void
    {
        $this->parseAttrId()->parseAttrClass();
    }

    /**
     * @inheritDoc
     */
    public function parseAttrClass(): PartialDriverInterface
    {
        $base = $this->getBaseClass();

        $default_class = "$base $base--" . $this->getIndex();
        if (!$this->has('attrs.class')) {
            $this->set('attrs.class', $default_class);
        } else {
            $this->set('attrs.class', sprintf($this->get('attrs.class'), $default_class));
        }

        if (!$this->get('attrs.class')) {
            $this->forget('attrs.class');
        }

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function parseAttrId(): PartialDriverInterface
    {
        if (!$this->get('attrs.id')) {
            $this->forget('attrs.id');
        }
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function render(): string
    {
        return $this->view($this->get('view.template_name') ?? 'index', $this->all());
    }

    /**
     * @inheritDoc
     */
    public function setAlias(string $alias): PartialDriverInterface
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public static function setDefaults(array $defaults = []): void
    {
        self::$defaults[__CLASS__] = $defaults;
    }

    /**
     * @inheritDoc
     */
    public function setId(string $id): PartialDriverInterface
    {
        $this->id = $id;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function setIndex(int $index): PartialDriverInterface
    {
        $this->index = $index;

        return $this;
    }

    /**
     * @inheritDoc
     */
    public function view(?string $name = null, array $data = [])
    {
        if ($this->view === null) {
            $this->view = $this->viewResolver();
        }

        if (func_num_args() === 0) {
            return $this->view;
        }

        return $this->view->render($name, $data);
    }

    /**
     * @inheritDoc
     */
    public function viewDirectory(): ?string
    {
        return null;
    }

    /**
     * Resolves view instance.
     *
     * @return ViewInterface
     */
    protected function viewResolver(): ViewInterface
    {
        $default = $this->partial()->config('view', []);
        $viewDef = $this->get('view');
        
        if (!$viewDef instanceof ViewInterface) {
            $directory = $this->get('view.directory');
            if ($directory && !file_exists($directory)) {
                $directory = null;
            }

            $overrideDir = $this->get('view.override_dir');
            if ($overrideDir && !file_exists($overrideDir)) {
                $overrideDir = null;
            }

            if ($directory === null && is_array($default) && isset($default['directory'])) {
                $default['directory'] = rtrim($default['directory'], '/') . '/' . $this->getAlias();
                if (file_exists($default['directory'])) {
                    $directory = $default['directory'];
                }
            }

            if ($overrideDir === null && is_array($default) && isset($default['override_dir'])) {
                $default['override_dir'] = rtrim($default['override_dir'], '/') . '/' . $this->getAlias();
                if (file_exists($default['override_dir'])) {
                    $overrideDir = $default['override_dir'];
                }
            }

            if ($directory === null) {
                $directory = $this->viewDirectory();
                if ($directory === null || !file_exists($directory)) {
                    throw new InvalidArgumentException(
                        sprintf('Partial [%s] must have an accessible view directory', $this->getAlias())
                    );
                }
            }

            $view = $this->viewManager()->createView('plates')->setDirectory($directory);

            if ($overrideDir !== null) {
                $view->setOverrideDir($overrideDir);
            }
        } else {
            $view = $viewDef;
        }

        $functions = [
            'after',
            'before',
            'getAlias',
            'getId',
            'getIndex',
        ];
        foreach ($functions as $fn) {
            $view->addExtension($fn, [$this, $fn]);
        }

        return $view;
    }

    /**
     * @inheritDoc
     */
    public function responseController(...$args): ResponseInterface
    {
        return new Response(null, 404);
    }
}
