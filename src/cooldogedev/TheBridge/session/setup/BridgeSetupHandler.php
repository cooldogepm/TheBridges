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

use cooldogedev\TheBridge\game\data\GameData;
use cooldogedev\TheBridge\session\Session;
use cooldogedev\TheBridge\TheBridge;
use cooldogedev\TheBridge\utility\Utils;
use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;

final class BridgeSetupHandler extends ISetupHandler
{
    protected string $step = GameData::GAME_DATA_BRIDGE_MIN;
    protected array $data = [];

    public function __construct(protected TheBridge $plugin, protected ?World $world, protected string $map)
    {
    }

    public function handleBlockBreak(Session $session, Vector3 $vec): void
    {
        $gameManager = $session->getPlugin()->getGameManager();
        $mapData = $session->getPlugin()->getGameManager()->getMap($this->map);

        if ($mapData === null) {
            $session->getPlayer()->sendMessage(TextFormat::RED . "The map you created no longer exists.");
            $session->setSetupHandler(null);
            return;
        }

        if ($this->step === GameData::GAME_DATA_BRIDGE_MAX) {
            $this->data[GameData::GAME_DATA_BRIDGE_MAX] = Utils::stringifyVec3($vec);

            $newData = $gameManager->getMap($this->map);

            $newData[GameData::GAME_DATA_BRIDGE] = $this->data;

            $gameManager->addMap($this->map, $newData, true);

            $this->onQuit($session, false);
            return;
        }

        $session->getPlayer()->sendMessage(TextFormat::GREEN . "Set " . $this->step . " to " . Utils::stringifyVec3($vec));

        $this->data[$this->step] = Utils::stringifyVec3($vec);
        $this->step = match ($this->step) {
            GameData::GAME_DATA_BRIDGE_MIN => GameData::GAME_DATA_BRIDGE_MAX,
            GameData::GAME_DATA_BRIDGE_MAX => GameData::GAME_DATA_BRIDGE_MIN,
        };
    }

    protected function onQuit(Session $session, bool $cancelled = true): void
    {
        $this->plugin->getServer()->getWorldManager()->unloadWorld($this->world, true);

        $cancelled && $session->getPlugin()->getGameManager()->removeMap($this->map);
        $session->getPlayer()->sendMessage($cancelled ? TextFormat::RED . "Setup was cancelled." : TextFormat::GREEN . "Setup was completed.");
        $session->getPlayer()->teleport($session->getPlugin()->getServer()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
        $session->getPlayer()->setGamemode($session->getPlugin()->getServer()->getGamemode());
        $session->setSetupHandler(null);

        $this->world = null;
        $this->map = "";
        $this->data = [];
        $this->step = GameData::GAME_DATA_BRIDGE_MIN;
    }
}
