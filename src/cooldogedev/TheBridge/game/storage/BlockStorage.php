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

namespace cooldogedev\TheBridge\game\storage;

use cooldogedev\TheBridge\game\data\GameData;
use cooldogedev\TheBridge\utility\Utils;
use pocketmine\math\Vector3;

final class BlockStorage
{
    protected Vector3 $min;
    protected Vector3 $max;

    protected array $blocks = [];

    public function __construct(GameData $data)
    {
        $min = Utils::parseVec3($data->getBridge()[GameData::GAME_DATA_BRIDGE_MIN]);
        $max = Utils::parseVec3($data->getBridge()[GameData::GAME_DATA_BRIDGE_MAX]);

        $this->min = Vector3::minComponents($min, $max);
        $this->max = Vector3::maxComponents($min, $max);
    }

    public function getBlocks(): array
    {
        return $this->blocks;
    }

    public function addBlock(Vector3 $vec3): bool
    {
        if (!$this->isWithin($vec3)) {
            return false;
        }

        $this->blocks[Utils::stringifyVec3($vec3)] = true;
        return true;
    }

    public function isWithin(Vector3 $vec3): bool
    {
        return $vec3->x >= $this->min->x && $vec3->x <= $this->max->x && $vec3->y >= $this->min->y && $vec3->y <= $this->max->y && $vec3->z >= $this->min->z && $vec3->z <= $this->max->z;
    }

    public function removeBlock(Vector3 $vec3): bool
    {
        if (!$this->isBreakable($vec3)) {
            return false;
        }

        unset($this->blocks[Utils::stringifyVec3($vec3)]);
        return true;
    }

    public function isBreakable(Vector3 $vec3): bool
    {
        return isset($this->blocks[Utils::stringifyVec3($vec3)]);
    }
}
