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

namespace cooldogedev\TheBridge\block;

use pocketmine\block\BlockBreakInfo;
use pocketmine\block\BlockIdentifier;
use pocketmine\block\BlockLegacyIds;
use pocketmine\block\Transparent;
use pocketmine\block\utils\SupportType;
use pocketmine\entity\Entity;

final class EndPortalBlock extends Transparent
{
    public function __construct()
    {
        parent::__construct(
            new BlockIdentifier(BlockLegacyIds::END_PORTAL, 0),
            "End Portal",
            BlockBreakInfo::indestructible()
        );
    }

    protected function recalculateCollisionBoxes() : array
    {
        return [];
    }

    public function isSolid(): bool
    {
        return false;
    }

    public function getLightLevel(): int
    {
        return 15;
    }

    public function onEntityInside(Entity $entity): bool
    {
        return true;
    }

    public function hasEntityCollision(): bool
    {
        return true;
    }

    public function getSupportType(int $facing): SupportType
    {
        return SupportType::NONE();
    }
}
