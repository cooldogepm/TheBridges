<?php

/**
 * Copyright (c) 2022 cooldogedev
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * @auto-license
 */

declare(strict_types=1);

namespace cooldogedev\TheBridge\utility;

use pocketmine\entity\Location;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;

final class Utils
{
    public static function recursiveClone(string $directory, string $target): void
    {
        $dir = opendir($directory);
        @mkdir($target);

        while ($file = readdir($dir)) {
            if (Utils::isPointer($file)) {
                continue;
            }

            if (is_dir($directory . DIRECTORY_SEPARATOR . $file)) {
                Utils::recursiveClone($directory . DIRECTORY_SEPARATOR . $file, $target . DIRECTORY_SEPARATOR . $file);
                continue;
            }

            copy($directory . DIRECTORY_SEPARATOR . $file, $target . DIRECTORY_SEPARATOR . $file);
        }

        closedir($dir);
    }

    public static function isPointer(string $file): bool
    {
        return $file === "." || $file === "..";
    }

    public static function recursiveDelete(string $directory): void
    {
        if (Utils::isPointer($directory)) {
            return;
        }

        foreach (scandir($directory) as $item) {
            if (Utils::isPointer($item)) {
                continue;
            }

            if (is_dir($directory . DIRECTORY_SEPARATOR . $item)) {
                Utils::recursiveDelete($directory . DIRECTORY_SEPARATOR . $item);
            }

            if (is_file($directory . DIRECTORY_SEPARATOR . $item)) {
                unlink($directory . DIRECTORY_SEPARATOR . $item);
            }
        }

        rmdir($directory);
    }

    public static function stringifyVec3(Vector3 $vec): string
    {
        return implode(":", [$vec->x, $vec->y, $vec->z]);
    }

    public static function parseVec3(string $vec3): Vector3
    {
        return new Vector3(...array_map(fn(string $value) => (int)$value, explode(":", $vec3)));
    }

    public static function stringifyLoc(Location $loc): string
    {
        return implode(":", [$loc->x, $loc->y, $loc->z, $loc->yaw, $loc->pitch]);
    }

    public static function parseLoc(string $loc): Location
    {
        $arguments = array_map(fn(string $value) => (int)$value, explode(":", $loc));

        return new Location($arguments[0], $arguments[1], $arguments[2], null, $arguments[3] ?? 0, $arguments[4] ?? 0);
    }

    public static function facingToDegrees(int $facing): int
    {
        return match ($facing) {
            Facing::NORTH => 0,
            Facing::EAST => 90,
            Facing::SOUTH => 180,
            Facing::WEST => 270,
        };
    }
}
