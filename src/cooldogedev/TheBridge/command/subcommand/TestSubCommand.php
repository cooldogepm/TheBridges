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

namespace cooldogedev\TheBridge\command\subcommand;

use cooldogedev\TheBridge\permission\PermissionsList;
use cooldogedev\TheBridge\utility\message\KnownMessages;
use cooldogedev\TheBridge\utility\message\LanguageManager;
use CortexPE\Commando\BaseSubCommand;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

final class TestSubCommand extends BaseSubCommand
{
    public function __construct()
    {
        parent::__construct("test", "Force all online players to join a game", ["debug"]);
    }

    public function onRun(CommandSender $sender, string $aliasUsed, array $args): void
    {
        $sender->sendMessage(TextFormat::RED . "Forcing all players to join a game...");

        foreach ($this->getOwningPlugin()->getServer()->getOnlinePlayers() as $onlinePlayer) {
            $onlinePlayer->chat("/" . LanguageManager::getMessage(KnownMessages::TOPIC_COMMAND, KnownMessages::COMMAND_NAME) . " join");
        }
    }

    protected function prepare(): void
    {
        $this->setPermission(PermissionsList::THEBRIDGE_SUBCOMMAND_TEST);
    }
}
