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

namespace cooldogedev\TheBridge\game\queue;

use cooldogedev\TheBridge\game\Game;
use cooldogedev\TheBridge\session\Session;

final class QueueManager
{
    protected array $queues = [];

    public function __construct(protected Game $game)
    {
    }

    public function clearQueue(): void
    {
        if (count($this->queues) <= 0) {
            return;
        }

        foreach ($this->queues as $key => $_) {
            $player = $this->game->getPlugin()->getServer()->getPlayerByPrefix($key);

            if ($player === null || !$player->isConnected()) {
                continue;
            }

            $session = $this->game->getPlugin()->getSessionManager()->getSession($player->getUniqueId()->getBytes());

            $this->game->getPlayerManager()->addToGame($session, true);

            unset($this->queues[$key]);
        }
    }

    public function flush(): void
    {
        if (count($this->queues) <= 0) {
            return;
        }

        foreach ($this->queues as $key => $data) {
            $this->game->getPlugin()->getGameManager()->queueToGame([$key], $data);

            unset($this->queues[$key]);
        }
    }

    public function add(string $playerName, array $data): bool
    {
        if ($this->exists($playerName)) {
            return false;
        }

        $this->queues[strtolower($playerName)] = $data;

        return true;
    }

    public function exists(string $playerName): bool
    {
        return isset($this->queues[strtolower($playerName)]);
    }

    public function remove(string $playerName): bool
    {
        if (!$this->exists($playerName)) {
            return false;
        }

        unset($this->queues[strtolower($playerName)]);

        return true;
    }

    /**
     * @return Session[]
     */
    public function getQueues(): array
    {
        return $this->queues;
    }

    public function isEmpty(): bool
    {
        return count($this->queues) === 0;
    }
}
