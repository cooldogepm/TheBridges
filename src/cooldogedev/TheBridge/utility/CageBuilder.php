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

use pocketmine\math\Vector3;

final class CageBuilder
{
    /**
     * @param Vector3 $center
     * @param int $width
     * @param int $height
     * @return Vector3[]
     */
    public static function build(Vector3 $center, int $width, int $height): array
    {
        $center = $center->floor();

        $width = floor($width / 2);
        $height = floor($height / 2);

        $positions = [];

        $pos1 = $center->subtract($width, $height, $width);
        $pos2 = $center->add($width, $height, $width);

        $min = Vector3::minComponents($pos1, $pos2);
        $max = Vector3::maxComponents($pos1, $pos2);

        for ($x = $min->x; $x <= $max->x; $x++) {
            for ($y = $min->y; $y <= $max->y; $y++) {
                for ($z = $min->z; $z <= $max->z; $z++) {
                    if (($x !== $min->x && $x !== $max->x) && ($y !== $min->y && $y !== $max->y) && ($z !== $min->z && $z !== $max->z)) {
                        continue;
                    }

                    $positions[] = new Vector3($x, $y, $z);
                }
            }
        }

        return $positions;
    }
}
