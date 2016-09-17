<?php

namespace Teto\Routing;

/**
 * NotFoundAction object
 *
 * @author    USAMI Kenta <tadsan@zonu.me>
 * @copyright 2016 BaguetteHQ
 * @license   http://www.apache.org/licenses/LICENSE-2.0 Apache-2.0
 *
 * @property-read string[] $methods
 * @property-read string[] $split_path
 * @property-read array    $param_pos
 * @property-read mixed    $value
 * @property-read string   $extension
 * @property-read boolean  $is_wildcard
 * @property-read string[] $available_extensions
 */
class NotFoundAction extends Action
{
    protected static function assertMethods(array $methods)
    {
        // thorough
    }
}
