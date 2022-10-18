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

namespace cooldogedev\TheBridge\game;

use cooldogedev\TheBridge\constant\ItemConstants;
use cooldogedev\TheBridge\game\data\GameData;
use cooldogedev\TheBridge\game\data\TeamData;
use cooldogedev\TheBridge\game\handler\EndHandler;
use cooldogedev\TheBridge\game\handler\GraceHandler;
use cooldogedev\TheBridge\game\handler\IHandler;
use cooldogedev\TheBridge\game\handler\PreStartHandler;
use cooldogedev\TheBridge\game\handler\RoundHandler;
use cooldogedev\TheBridge\game\player\PlayerManager;
use cooldogedev\TheBridge\game\queue\QueueManager;
use cooldogedev\TheBridge\game\storage\BlockStorage;
use cooldogedev\TheBridge\game\team\Team;
use cooldogedev\TheBridge\game\team\TeamManager;
use cooldogedev\TheBridge\task\BackgroundTaskPool;
use cooldogedev\TheBridge\task\directory\AsyncDirectoryClone;
use cooldogedev\TheBridge\task\directory\AsyncDirectoryDelete;
use cooldogedev\TheBridge\TheBridge;
use cooldogedev\TheBridge\utility\message\KnownMessages;
use cooldogedev\TheBridge\utility\message\LanguageManager;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\VanillaItems;
use pocketmine\player\GameMode;
use pocketmine\utils\TextFormat;
use pocketmine\world\World;

final class Game
{
    public const GAME_WORLD_IDENTIFIER = "TB-GAME-";
    public const GAME_LOBBY_IDENTIFIER = Game::GAME_WORLD_IDENTIFIER . "-LOBBY-";

    protected ?PlayerManager $playerManager;
    protected ?QueueManager $queueManager;
    protected ?TeamManager $teamManager;
    protected ?BlockStorage $blockStorage;
    protected ?IHandler $handler;
    protected int $timeLeft;

    protected ?Team $winner = null;
    protected ?World $world = null;
    protected ?World $lobby = null;
    protected bool $loading = true;

    public function __construct(protected TheBridge $plugin, protected ?GameData $data)
    {
        $this->playerManager = new PlayerManager($this);
        $this->queueManager = new QueueManager($this);
        $this->teamManager = new TeamManager($this);
        $this->blockStorage = new BlockStorage($data);
        $this->handler = new PreStartHandler($this);
        $this->timeLeft = $data->getDuration();

        $directories = [];

        $directories[$plugin->getDataFolder() . "maps" . DIRECTORY_SEPARATOR . $this->getData()->getName() . DIRECTORY_SEPARATOR . GameData::GAME_DATA_WORLD] = $plugin->getServer()->getDataPath() . "worlds" . DIRECTORY_SEPARATOR . $this->data->getWorld();

        if ($this->hasSeparateLobby()) {
            $directories[$this->plugin->getDataFolder() . "maps" . DIRECTORY_SEPARATOR . $this->getData()->getName() . DIRECTORY_SEPARATOR . GameData::GAME_DATA_LOBBY] = $plugin->getServer()->getDataPath() . "worlds" . DIRECTORY_SEPARATOR . $this->data->getLobby();
        }

        $task = new AsyncDirectoryClone($directories);
        $task->setClosure(
            function () use (&$plugin): void {
                if ($plugin->getServer()->getWorldManager()->loadWorld($this->data->getWorld())) {
                    $this->world = $plugin->getServer()->getWorldManager()->getWorldByName($this->data->getWorld());
                    $this->world->setSpawnLocation($this->world->getSpawnLocation()->add(0.5, 0, 0.5));
                } else {
                    $plugin->getLogger()->debug("Failed to load world " . $this->data->getWorld() . " of arena " . $this->getData()->getId());
                    $this->startDestruction();

                    return;
                }

                if ($this->hasSeparateLobby() && $plugin->getServer()->getWorldManager()->loadWorld($this->data->getLobby())) {
                    $this->lobby = $plugin->getServer()->getWorldManager()->getWorldByName($this->data->getLobby());
                    $this->lobby->setSpawnLocation($this->lobby->getSpawnLocation()->add(0.5, 0, 0.5));
                } else {
                    $plugin->getLogger()->debug("Failed to load lobby " . $this->data->getLobby() . " of arena " . $this->getData()->getId() . " used the game world as the lobby: " . $this->data->getWorld());
                    $this->lobby = $this->world;
                }

                $this->world->setTime(World::TIME_DAY);
                $this->world->stopTime();
                $this->world->setAutoSave(false);
                $this->lobby->setTime(World::TIME_DAY);
                $this->lobby->stopTime();
                $this->lobby->setAutoSave(false);

                foreach ($this->data->getTeams() as $team => $data) {
                    $this->teamManager->addTeam($team, new TeamData($team, $data[TeamData::TEAM_DATA_MIN], $data[TeamData::TEAM_DATA_MAX], $data[TeamData::TEAM_DATA_STEP_SPAWN]));
                }

                $this->loading = false;
            }
        );

        BackgroundTaskPool::getInstance()->submitTask($task);
    }

    public function getData(): GameData
    {
        return $this->data;
    }

    public function getWorld(): ?World
    {
        return $this->world;
    }

    public function hasSeparateLobby(): bool
    {
        return strtolower($this->data->getLobby()) !== strtolower($this->data->getWorld()) && file_exists($this->plugin->getDataFolder() . "maps" . DIRECTORY_SEPARATOR . $this->getData()->getName() . DIRECTORY_SEPARATOR . GameData::GAME_DATA_LOBBY);
    }

    public function getLobby(): ?World
    {
        return $this->lobby;
    }

    public function startDestruction(): void
    {
        foreach ($this->playerManager->getSessions() as $session) {
            $this->playerManager->removeFromGame($session);
        }

        $this->plugin->getGameManager()->removeGame($this->getData()->getId());
        $this->world !== null && $this->plugin->getServer()->getWorldManager()->unloadWorld($this->world, true);

        $directories = [];
        $directories[] = $this->plugin->getServer()->getDataPath() . "worlds" . DIRECTORY_SEPARATOR . $this->data->getWorld();

        if ($this->hasSeparateLobby()) {
            $this->lobby !== null && $this->plugin->getServer()->getWorldManager()->unloadWorld($this->lobby, true);
            $directories[] = $this->plugin->getServer()->getDataPath() . "worlds" . DIRECTORY_SEPARATOR . $this->data->getLobby();
        }

        $task = new AsyncDirectoryDelete($directories);

        // Copy the id because we set the data to null afterwards.
        $id = $this->getData()->getId();
        $task->setClosure(fn() => $this->plugin->getLogger()->debug("Deleted " . Game::GAME_WORLD_IDENTIFIER . $id . " game."));
        BackgroundTaskPool::getInstance()->submitTask($task);

        $this->handler = null;
        $this->playerManager = null;
        $this->teamManager = null;
        $this->world = null;
        $this->winner = null;
        $this->data = null;
        $this->loading = true;
    }

    public function getBlockStorage(): ?BlockStorage
    {
        return $this->blockStorage;
    }

    public function getTeamManager(): ?TeamManager
    {
        return $this->teamManager;
    }

    public function getPlugin(): TheBridge
    {
        return $this->plugin;
    }

    public function getQueueManager(): ?QueueManager
    {
        return $this->queueManager;
    }

    public function isFree(bool $fromQueue = false): bool
    {
        if (
            !$this->handler instanceof PreStartHandler ||
            !$this->loading && $this->teamManager->getRandomTeam() === null ||
            count($this->playerManager->getSessions()) >= $this->getData()->getMaxPlayers() ||
            $this->loading && !$fromQueue && count($this->queueManager->getQueues()) >= $this->getData()->getMaxPlayers()
        ) {
            return false;
        }
        return true;
    }

    public function isLoading(): bool
    {
        return $this->loading;
    }

    public function getWinner(): ?Team
    {
        return $this->winner;
    }

    public function setWinner(?Team $winner): void
    {
        $this->winner = $winner;
    }

    public function tickGame(): void
    {
        if ($this->loading) {
            return;
        }

        if ($this->handler instanceof PreStartHandler) {
            $this->queueManager->clearQueue();
        }

        if (count($this->teamManager->getTeams(true)) < 2 && !$this->handler instanceof EndHandler && !$this->handler instanceof PreStartHandler) {
            if (count($this->teamManager->getTeams(true)) === 0) {
                $this->startDestruction();
            } else {
                $this->calculateResults();
            }

            return;
        }

        if ($this->handler instanceof RoundHandler || $this->handler instanceof GraceHandler) {
            if ($this->timeLeft > 0) {
                $this->timeLeft--;
            } else {
                $this->calculateResults();

                return;
            }
        }

        $this->getHandler()?->handleTicking();
    }

    public function calculateResults(): void
    {
        $winnerTeam = null;

        foreach ($this->teamManager->getTeams() as $team) {
            if ($winnerTeam === null || !$team->isEmpty() && $team->getGoals() > $winnerTeam->getGoals()) {
                $winnerTeam = $team;
            }
        }

        $this->winner = $winnerTeam;

        foreach ($this->teamManager->getTeams() as $team) {
            $team->buildCage(VanillaBlocks::AIR());
        }

        foreach ($this->playerManager->getSessions() as $session) {
            $session->resetPlayer();
            $session->getPlayer()->teleport($session->getTeam()->getSpawn());

            if ($this->winner !== null && $this->winner->inTeam($session)) {
                $session->addWin(1);
                $session->addWinStreak(1);
            } else {
                $session->addLoss(1);
                $session->setWinStreak(0);
            }

            $session->getPlayer()->setGamemode(GameMode::SPECTATOR());

            $playAgainItem = VanillaItems::PAPER()->setCustomName(LanguageManager::getMessage(KnownMessages::TOPIC_ITEMS, KnownMessages::ITEMS_PLAY_AGAIN));
            $backToLobbyItem = VanillaItems::RED_BED()->setCustomName(LanguageManager::getMessage(KnownMessages::TOPIC_ITEMS, KnownMessages::ITEMS_BACK_TO_LOBBY));

            $playAgainItem->getNamedTag()->setInt(ItemConstants::ITEM_TYPE_IDENTIFIER, ItemConstants::ITEM_PLAY_AGAIN);
            $backToLobbyItem->getNamedTag()->setInt(ItemConstants::ITEM_TYPE_IDENTIFIER, ItemConstants::ITEM_BACK_TO_LOBBY);

            $session->getPlayer()->getInventory()->setItem(4, $playAgainItem);
            $session->getPlayer()->getInventory()->setItem(8, $backToLobbyItem);
        }

        $this->setHandler(new EndHandler($this));
    }

    public function getHandler(): ?IHandler
    {
        return $this->handler;
    }

    public function setHandler(?IHandler $handler): void
    {
        $this->handler = $handler;
    }

    public function getTimeLeft(): string
    {
        return date("i:s", $this->timeLeft);
    }

    public function broadcastMessage(string $message, array $translations = [], ?int $mode = null): void
    {
        if (trim($message) === "") {
            return;
        }

        $sessions = $this->playerManager->getSessions($mode);

        foreach ($sessions as $session) {
            $session->getPlayer()->sendMessage(LanguageManager::translate($message, $translations));
        }
    }

    /**
     * TODO: remove subtitle parameter
     */
    public function broadcastTitle(string $message, string $subtitle = "", array $translations = [], ?int $mode = null, int $fadeIn = 5, int $stay = 20, int $fadeOut = 5): void
    {
        if (trim($message) === "") {
            return;
        }

        $sessions = $this->playerManager->getSessions($mode);

        foreach ($sessions as $session) {
            $session->getPlayer()->sendTitle(LanguageManager::translate($message, $translations), fadeIn: $fadeIn, stay: $stay, fadeOut: $fadeOut);

            trim(TextFormat::clean($subtitle)) !== "" && $session->getPlayer()->sendSubtitle(LanguageManager::translate($subtitle, $translations));
        }
    }

    public function broadcastSubTitle(string $message, array $translations = [], ?int $mode = null): void
    {
        if (trim($message) === "") {
            return;
        }

        $sessions = $this->playerManager->getSessions($mode);

        foreach ($sessions as $session) {
            $session->getPlayer()->sendSubTitle(LanguageManager::translate($message, $translations));
        }
    }

    public function getPlayerManager(): ?PlayerManager
    {
        return $this->playerManager;
    }
}
