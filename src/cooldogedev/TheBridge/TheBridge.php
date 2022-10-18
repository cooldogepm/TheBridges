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

namespace cooldogedev\TheBridge;

use cooldogedev\libSQL\ConnectionPool;
use cooldogedev\TheBridge\block\EndPortalBlock;
use cooldogedev\TheBridge\command\TheBridgeCommand;
use cooldogedev\TheBridge\game\Game;
use cooldogedev\TheBridge\game\GameManager;
use cooldogedev\TheBridge\query\QueryManager;
use cooldogedev\TheBridge\session\SessionManager;
use cooldogedev\TheBridge\task\BackgroundTaskPool;
use cooldogedev\TheBridge\task\directory\AsyncDirectoryDelete;
use cooldogedev\TheBridge\utility\ConfigurationsValidator;
use cooldogedev\TheBridge\utility\message\LanguageManager;
use CortexPE\Commando\PacketHooker;
use pocketmine\block\BlockFactory;
use pocketmine\item\StringToItemParser;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\SingletonTrait;

final class TheBridge extends PluginBase
{
    use SingletonTrait {
        setInstance as protected;
        reset as protected;
    }

    protected ConnectionPool $connectionPool;
    protected GameManager $gameManager;
    protected SessionManager $sessionManager;

    public function getSessionManager(): SessionManager
    {
        return $this->sessionManager;
    }

    public function getGameManager(): GameManager
    {
        return $this->gameManager;
    }

    public function getConnectionPool(): ConnectionPool
    {
        return $this->connectionPool;
    }

    protected function onLoad(): void
    {
        @mkdir($this->getDataFolder() . "maps");

        foreach ($this->getResources() as $resource) {
            $this->saveResource($resource->getFilename());
        }

        TheBridge::setInstance($this);
    }

    protected function onEnable(): void
    {
        if (!PacketHooker::isRegistered()) {
            PacketHooker::register($this);
        }

        $this->connectionPool = new ConnectionPool($this, $this->getConfig()->get("database"));
        $this->gameManager = new GameManager($this);
        $this->sessionManager = new SessionManager($this);

        LanguageManager::init($this, $this->getConfig()->get("language"));
        QueryManager::setIsMySQL($this->getConfig()->get("database")["provider"] === ConnectionPool::DATA_PROVIDER_MYSQL);
        ConfigurationsValidator::validate($this);

        $this->getServer()->getPluginManager()->registerEvents(new EventListener($this), $this);

        $this->getServer()->getCommandMap()->register("thebridge", new TheBridgeCommand($this));

        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(fn() => $this->gameManager->handleTick()), 20);
        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(fn() => $this->gameManager->handleMovementTick()), 1);

        BackgroundTaskPool::getInstance()->submitTask(new AsyncDirectoryDelete(glob($this->getServer()->getDataPath() . "worlds" . DIRECTORY_SEPARATOR . Game::GAME_WORLD_IDENTIFIER . "*")));

        $this->connectionPool->submit(QueryManager::getTableCreationQuery());

        $block = new EndPortalBlock();
        BlockFactory::getInstance()->register($block, true);
        StringToItemParser::getInstance()->override($block->getName(), fn () => $block->asItem());
    }
}
