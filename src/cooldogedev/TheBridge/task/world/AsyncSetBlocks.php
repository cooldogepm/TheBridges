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

namespace cooldogedev\TheBridge\task\world;

use cooldogedev\TheBridge\task\ClosureBackgroundTask;
use cooldogedev\TheBridge\utility\Utils;
use pocketmine\world\format\Chunk;
use pocketmine\world\format\io\FastChunkSerializer;
use pocketmine\world\World;

final class AsyncSetBlocks extends ClosureBackgroundTask
{
    protected string $positions;
    protected string $chunks;

    public function __construct(array $chunks, protected int $block, array $positions)
    {
        $this->chunks = igbinary_serialize(array_map(fn(Chunk $chunk) => FastChunkSerializer::serializeTerrain($chunk), $chunks));
        $this->positions = igbinary_serialize($positions);
    }

    public function onRun(): void
    {
        $chunks = [];
        $positions = array_map(fn(string $position) => Utils::parseVec3($position), igbinary_unserialize($this->positions));

        // Deserialize chunks
        foreach (igbinary_unserialize($this->chunks) as $hash => $chunk) {
            $chunks[$hash] = FastChunkSerializer::deserializeTerrain($chunk);
        }

        // Set blocks
        foreach ($positions as $position) {
            $chunk = $chunks[World::chunkHash($position->x >> Chunk::COORD_BIT_SIZE, $position->z >> Chunk::COORD_BIT_SIZE)];

            $chunk->setFullBlock($position->x & Chunk::COORD_MASK, $position->y, $position->z & Chunk::COORD_MASK, $this->block);
        }

        // Serialize chunks
        $this->setResult(igbinary_serialize(array_map(fn(Chunk $chunk) => FastChunkSerializer::serializeTerrain($chunk), $chunks)));
    }
}
