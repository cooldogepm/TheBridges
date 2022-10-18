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
use cooldogedev\TheBridge\game\data\TeamData;
use cooldogedev\TheBridge\session\Session;
use cooldogedev\TheBridge\TheBridge;
use cooldogedev\TheBridge\utility\Utils;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;

final class TeamsSetupHandler extends ISetupHandler
{
    protected string $team = TeamData::TEAM_TYPE_BLUE;
    protected string $step = TeamData::TEAM_DATA_MIN;
    protected array $data = [];

    public function __construct(protected TheBridge $plugin, protected ?World $world, protected string $map)
    {
        $this->data[TeamData::TEAM_TYPE_BLUE] = [];
        $this->data[TeamData::TEAM_TYPE_RED] = [];
    }

    protected function onQuit(Session $session, bool $cancelled = true): void
    {
        if (!$cancelled) {
            $session->getPlayer()->sendMessage(TextFormat::GREEN . "You've entered bridge setup mode");
            $session->getPlayer()->sendMessage(TextFormat::GREEN . "Place blocks around the build area and break them");
            $session->getPlayer()->sendMessage(TextFormat::GREEN . "The blocks you place will not be saved");

            $session->setSetupHandler(new BridgeSetupHandler($this->plugin, $this->world, $this->map));
            return;
        }

        $this->plugin->getServer()->getWorldManager()->unloadWorld($this->world, true);

        $session->getPlugin()->getGameManager()->removeMap($this->map);
        $session->getPlayer()->sendMessage(TextFormat::RED . "Setup was cancelled.");
        $session->getPlayer()->teleport($session->getPlugin()->getServer()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
        $session->getPlayer()->setGamemode($session->getPlugin()->getServer()->getGamemode());

        $this->world = null;
        $this->map = "";
        $this->data = [];
        $this->team = TeamData::TEAM_DATA_MIN;
        $this->step = TeamData::TEAM_TYPE_BLUE;
    }

    public function handleBlockBreak(Session $session, Vector3 $vec): void
    {
        $location = new Location($vec->x, $vec->y, $vec->z, $this->world, Utils::facingToDegrees($session->getPlayer()->getHorizontalFacing()), 0);

        $gameManager = $session->getPlugin()->getGameManager();
        $mapData = $session->getPlugin()->getGameManager()->getMap($this->map);

        if ($mapData === null) {
            $session->getPlayer()->sendMessage(TextFormat::RED . "The map you created no longer exists.");
            $session->setSetupHandler(null);
            return;
        }

        $session->getPlayer()->sendMessage(TextFormat::GREEN . "Set " . $this->step . " " . $this->team . " to " . Utils::stringifyVec3($vec));

        if ($this->team === TeamData::TEAM_TYPE_RED && $this->step === TeamData::TEAM_DATA_STEP_SPAWN) {
            $this->data[TeamData::TEAM_TYPE_RED][TeamData::TEAM_DATA_STEP_SPAWN] = Utils::stringifyLoc($location);

            $newData = $gameManager->getMap($this->map);

            $newData[GameData::GAME_DATA_TEAMS] = $this->data;

            $gameManager->addMap($this->map, $newData, true);

            $this->onQuit($session, false);
            return;
        }

        $this->data[$this->team][$this->step] = $this->step === TeamData::TEAM_DATA_STEP_SPAWN ? Utils::stringifyLoc($location) : Utils::stringifyVec3($vec);
        $this->step = match ($this->step) {
            TeamData::TEAM_DATA_MIN => TeamData::TEAM_DATA_MAX,
            TeamData::TEAM_DATA_MAX => TeamData::TEAM_DATA_STEP_SPAWN,
            TeamData::TEAM_DATA_STEP_SPAWN => TeamData::TEAM_DATA_MIN,
        };

        // Switch to next team
        if (count($this->data[$this->team]) === 3 && $this->team !== TeamData::TEAM_TYPE_RED) {
            $this->team = TeamData::TEAM_TYPE_RED;
        }
    }
}
