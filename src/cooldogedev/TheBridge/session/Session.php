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

namespace cooldogedev\TheBridge\session;

use cooldogedev\libSQL\context\ClosureContext;
use cooldogedev\TheBridge\constant\DatabaseConstants;
use cooldogedev\TheBridge\game\Game;
use cooldogedev\TheBridge\query\QueryManager;
use cooldogedev\TheBridge\session\data\SessionCache;
use cooldogedev\TheBridge\session\data\SessionData;
use cooldogedev\TheBridge\session\scoreboard\ScoreboardManager;
use cooldogedev\TheBridge\session\setup\ISetupHandler;
use cooldogedev\TheBridge\TheBridge;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\Armor;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\VanillaEnchantments;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;

final class Session
{
    public const PLAYER_STATE_DEFAULT = -1;
    public const PLAYER_STATE_PLAYING = 0;

    use SessionCache, SessionData;

    protected ScoreboardManager $scoreboardManager;
    protected ?ISetupHandler $setupHandler = null;

    protected bool $loading = true;

    public function __construct(protected TheBridge $plugin, protected Player $player)
    {
        $this->scoreboardManager = new ScoreboardManager($this->player);

        $this->plugin->getConnectionPool()->submit(QueryManager::getPlayerRetrieveQuery($player->getXuid()), DatabaseConstants::TABLE_THEBRIDGE_PLAYERS, ClosureContext::create(
            function (?array $result): void {
                if ($result !== null) {
                    $this->wins = $result["wins"];
                    $this->winStreak = $result["win_streak"];
                    $this->losses = $result["losses"];
                    $this->deaths = $result["deaths"];
                    $this->kills = $result["kills"];
                }

                $this->unregistered = $result === null;
                $this->loading = false;
            }
        ));
    }

    public function getId(): string
    {
        return $this->player->getUniqueId()->getBytes();
    }

    public function sendItems(): void
    {
        $this->resetPlayer();

        $armour = [
            VanillaItems::LEATHER_CAP(),
            VanillaItems::LEATHER_TUNIC(),
            VanillaItems::LEATHER_PANTS(),
            VanillaItems::LEATHER_BOOTS(),
        ];

        $pickaxe = VanillaItems::DIAMOND_PICKAXE();
        $pickaxe->addEnchantment(new EnchantmentInstance(VanillaEnchantments::EFFICIENCY(), 1));

        $hotbar = [
            VanillaItems::IRON_SWORD(),
            VanillaItems::BOW(),
            $pickaxe,
            VanillaBlocks::STAINED_CLAY()->setColor($this->team->getDyeColor())->asItem()->setCount(64),
            VanillaBlocks::STAINED_CLAY()->setColor($this->team->getDyeColor())->asItem()->setCount(64),
            VanillaItems::GOLDEN_APPLE()->setCount(8),
            8 => VanillaItems::ARROW(),
        ];

        /** @var Armor $item */
        foreach ($armour as $item) {
            $item->setCustomColor($this->team->getDyeColor()->getRgbValue());
        }

        $this->player->getArmorInventory()->setContents($armour);
        $this->player->getInventory()->setContents($hotbar);
    }

    public function resetPlayer(): void
    {
        $this->player->getInventory()->clearAll();
        $this->player->getArmorInventory()->clearAll();
        $this->player->getCursorInventory()->clearAll();
        $this->player->getOffHandInventory()->clearAll();
        $this->player->getEffects()->clear();

        $this->player->setHealth($this->player->getMaxHealth());
        $this->player->getHungerManager()->setFood($this->player->getMaxHealth());
        $this->player->getEffects()->clear();
    }

    public function getSetupHandler(): ?ISetupHandler
    {
        return $this->setupHandler;
    }

    public function setSetupHandler(?ISetupHandler $setupHandler): void
    {
        $this->setupHandler = $setupHandler;
    }

    public function getPlugin(): TheBridge
    {
        return $this->plugin;
    }

    public function isLoading(): bool
    {
        return $this->loading;
    }

    public function getGame(): ?Game
    {
        return $this->game;
    }

    public function save(): void
    {
        if (!$this->unregistered && $this->updated) {
            $this->plugin->getConnectionPool()->submit(QueryManager::getPlayerUpdateQuery($this->player->getXuid(), $this->toArray()), DatabaseConstants::TABLE_THEBRIDGE_PLAYERS);
        }

        if ($this->isUnregistered()) {
            $this->plugin->getConnectionPool()->submit(QueryManager::getPlayerCreateQuery($this->player->getXuid(), $this->player->getName(), $this->toArray()), DatabaseConstants::TABLE_THEBRIDGE_PLAYERS);
        }
    }

    public function getScoreboardManager(): ScoreboardManager
    {
        return $this->scoreboardManager;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }
}
