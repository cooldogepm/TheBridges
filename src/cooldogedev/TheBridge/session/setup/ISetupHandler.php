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

namespace cooldogedev\TheBridge\session\setup;

use cooldogedev\TheBridge\session\Session;
use pocketmine\math\Vector3;

abstract class ISetupHandler
{
    public const SETUP_QUIT_MESSAGE = ".quit";

    final public function handleMessage(Session $session, string $message): bool
    {
        match ($message) {
            ISetupHandler::SETUP_QUIT_MESSAGE => $this->onQuit($session),
            default => null
        };

        return $message === ISetupHandler::SETUP_QUIT_MESSAGE;
    }

    abstract protected function onQuit(Session $session, bool $cancelled = true): void;

    abstract public function handleBlockBreak(Session $session, Vector3 $vec): void;
}
