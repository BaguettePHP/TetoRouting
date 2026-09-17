<?php

declare(strict_types=1);

namespace Teto\Routing;

/**
 * NotFoundAction object
 *
 * @copyright 2016 BaguetteHQ
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 *
 * @property array<int, string> $methods
 * @property array<int, string> $split_path
 * @property array<int, string> $param_pos
 * @property mixed    $value
 * @property string   $extension
 * @property bool  $is_wildcard
 * @property array<string, bool> $available_extensions
 */
class NotFoundAction extends Action
{
    protected static function assertMethods(array $methods): void
    {
        // thorough
    }
}
