<?php

declare(strict_types=1);

namespace Pollen\Partial\Drivers;

use Closure;
use Pollen\Partial\PartialDriver;

class TagDriver extends PartialDriver implements TagDriverInterface
{
    /**
     * List of known singleton tags.
     * @see http://html-css-js.com/html/tags
     * @var string[]
     */
    protected array $knownSingletonTags = [
        'area',
        'base',
        'br',
        'col',
        'embed',
        'hr',
        'img',
        'input',
        'link',
        'meta',
        'param',
        'source',
    ];

    /**
     * @inheritDoc
     */
    public function defaultParams(): array
    {
        return array_merge(parent::defaultParams(), [
            /**
             * HTML tag.
             * @var string $tag div|span|a|... default div.
             */
            'tag'       => 'div',
            /**
             * HTML tag content.
             * @var string|callable $content
             */
            'content'   => '',
            /**
             * Enable tag as singleton.
             * {@internal Auto-resolve if null based on list of known singleton tags.}
             * @var bool|null $singleton
             */
            'singleton' => null,
        ]);
    }

    /**
     * @inheritDoc
     */
    public function render(): string
    {
        if ($this->get('singleton') === null) {
            if (in_array($this->get('tag'), $this->knownSingletonTags, true)) {
                $this->set('singleton', true);
            } else {
                $this->set('singleton', false);
            }
        }

        $this->set('content', ($content = $this->get('content')) instanceof Closure ? $content($this) : $content);

        return parent::render();
    }

    /**
     * @inheritDoc
     */
    public function viewDirectory(): string
    {
        return $this->partial()->resources('/views/tag');
    }
}