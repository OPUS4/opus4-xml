<?php

/**
 * This file is part of OPUS. The software OPUS has been originally developed
 * at the University of Stuttgart with funding from the German Research Net,
 * the Federal Department of Higher Education and Research and the Ministry
 * of Science, Research and the Arts of the State of Baden-Wuerttemberg.
 *
 * OPUS 4 is a complete rewrite of the original OPUS software and was developed
 * by the Stuttgart University Library, the Library Service Center
 * Baden-Wuerttemberg, the Cooperative Library Network Berlin-Brandenburg,
 * the Saarland University and State Library, the Saxon State Library -
 * Dresden State and University Library, the Bielefeld University Library and
 * the University Library of Hamburg University of Technology with funding from
 * the German Research Foundation and the European Regional Development Fund.
 *
 * LICENCE
 * OPUS is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the Licence, or any later version.
 * OPUS is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more
 * details. You should have received a copy of the GNU General Public License
 * along with OPUS; if not, write to the Free Software Foundation, Inc., 51
 * Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 *
 * @copyright   Copyright (c) 2026, OPUS 4 development team
 * @license     http://www.gnu.org/licenses/gpl.html General Public License
 */

namespace Opus\Xml;

use XSLTProcessor;
use Zend_Exception;
use Zend_Registry;

use function array_key_exists;
use function array_map;
use function call_user_func_array;
use function count;
use function method_exists;
use function preg_split;

use const PREG_SPLIT_NO_EMPTY;

/**
 * Proxy for PHP functions to make them available in Xslt.
 */
class PhpFunctions
{
    /** @var self */
    private static $singleton;

    /** @var array */
    private static $functions;

    /**
     * Prevent external construction of this class.
     */
    private function __construct()
    {
    }

    /**
     * Returns singleton instance of this class.
     *
     * @return self
     */
    public static function getInstance()
    {
        if (self::$singleton === null) {
            self::$singleton = new self();
        }

        return self::$singleton;
    }

    /**
     * Catches calls to static functions and redirects them to registered functions.
     *
     * Delegation happens to
     * - static functions
     * - object methods
     * - view helpers
     */
    public static function __callStatic(string $method, array $arguments): mixed
    {
        if (array_key_exists($method, self::$functions)) {
            $method = self::$functions[$method];
            $parts  = preg_split('/::/', $method, 2, PREG_SPLIT_NO_EMPTY);
            if ($parts && count($parts) === 2) {
                $className  = $parts[0];
                $methodName = $parts[1];
                if (method_exists($className, $methodName)) {
                    return call_user_func_array($method, $arguments);
                }
            }
        } else {
            $helper = self::getInstance()->findHelper($method);

            if ($helper !== null) {
                return call_user_func_array([$helper, $method], $arguments);
            }
        }

        return ''; // self::__call($method, $arguments);
    }

    /**
     * Finds helpers in the set of available view helpers.
     *
     * @throws Zend_Exception
     */
    protected function findHelper(string $method): mixed
    {
        if (! Zend_Registry::isRegistered('Opus_View')) {
            return null;
        }
        return Zend_Registry::get('Opus_View')->getHelper($method);
    }

    /**
     * Register PHP functions to make them available with the same prefix class.
     *
     * Values are complete function references.
     */
    public static function registerPhpFunctions(XSLTProcessor $processor, array $functions): void
    {
        $mapped = array_map(
            function ($name) {
                $parts = preg_split('/::/', $name, 2, PREG_SPLIT_NO_EMPTY);
                if ($parts && count($parts) === 2) {
                    $alias                      = self::class . '::' . $parts[1];
                    self::$functions[$parts[1]] = $name;
                } else {
                    $alias                  = $name;
                    self::$functions[$name] = $name;
                }

                return $alias;
            },
            $functions
        );

        $processor->registerPHPFunctions($mapped);
    }

    /**
     * Returns the names of the functions that are supported.
     */
    public static function registerViewHelper(XSLTProcessor $processor, array $allowedFunctions): array
    {
        $functions = array_map(
            function ($value) {
                return 'Opus\Xml\PhpFunctions::' . $value;
            },
            $allowedFunctions
        );

        $processor->registerPHPFunctions($functions);

        return $functions;
    }
}
