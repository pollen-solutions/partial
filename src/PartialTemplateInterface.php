<?php

declare(strict_types=1);

namespace Pollen\Partial;

use Pollen\ViewExtends\PlatesTemplateInterface;

/**
 * @method string after()
 * @method string before()
 * @method string getAlias()
 * @method string getId()
 * @method string getIndex()
 */
interface PartialTemplateInterface extends PlatesTemplateInterface
{
}