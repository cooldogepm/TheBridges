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

namespace cooldogedev\TheBridge\game\data;

final class TeamData
{
    public const TEAM_DATA_MIN = "min_goal";
    public const TEAM_DATA_MAX = "max_goal";
    public const TEAM_DATA_STEP_SPAWN = "spawn";

    public const TEAM_TYPE_BLUE = "Blue";
    public const TEAM_TYPE_RED = "Red";

    public function __construct(protected string $type, protected string $min, protected string $max, protected string $spawn)
    {
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getMin(): string
    {
        return $this->min;
    }

    public function getMax(): string
    {
        return $this->max;
    }

    public function getSpawn(): string
    {
        return $this->spawn;
    }
}
