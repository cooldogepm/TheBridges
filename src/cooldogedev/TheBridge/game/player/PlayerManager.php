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

namespace cooldogedev\TheBridge\game\player;

use cooldogedev\TheBridge\constant\ItemConstants;
use cooldogedev\TheBridge\game\Game;
use cooldogedev\TheBridge\game\handler\EndHandler;
use cooldogedev\TheBridge\session\Session;
use cooldogedev\TheBridge\utility\message\KnownMessages;
use cooldogedev\TheBridge\utility\message\LanguageManager;
use cooldogedev\TheBridge\utility\message\TranslationKeys;
use pocketmine\item\VanillaItems;
use pocketmine\player\GameMode;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;

final class PlayerManager
{
    protected array $sessions = [];
    protected ?Session $lastScorer = null;

    public function __construct(protected Game $game)
    {
    }

    public function getSession(string $uuid): ?Session
    {
        return $this->sessions[$uuid] ?? null;
    }

    public function getEmptySlots(): int
    {
        return $this->game->getData()->getMaxPlayers() - count($this->sessions);
    }

    public function getLastScorer(): ?Session
    {
        return $this->lastScorer;
    }

    public function setLastScorer(?Session $lastScorer): void
    {
        $this->lastScorer = $lastScorer;
    }

    public function removeFromGame(?Session $session, bool $left = true): bool
    {
        if (
            $session === null ||
            !$session->inGame() ||
            $session->getGame()->getData()->getId() !== $this->game->getData()->getId()
        ) {
            return false;
        }

        $session->resetPlayer();
        $session->getPlayer()->setHealth($session->getPlayer()->getMaxHealth());
        $session->getPlayer()->getHungerManager()->setFood($session->getPlayer()->getHungerManager()->getMaxFood());

        $session->getTeam()->removePlayer($session);

        $session->getScoreboardManager()->reset();
        $session->setGame(null);
        $session->setGameKills(0);
        $session->setGoals(0);
        $session->setTeam(null);
        $session->setState(Session::PLAYER_STATE_DEFAULT);
        $session->getPlayer()->getHungerManager()->setEnabled(false);

        $session->getPlayer()->setDisplayName($session->getPrevDisplayName());
        $session->getPlayer()->setNameTag($session->getPrevNametag());
        $session->setPrevDisplayName(null);
        $session->setPrevNameTag(null);
        $session->setLastDamageTime(null);

        unset($this->sessions[$session->getPlayer()->getUniqueId()->getBytes()]);

        if ($left) {
            $session->getPlayer()->teleport($this->game->getPlugin()->getServer()->getWorldManager()->getDefaultWorld()->getSafeSpawn());
            $session->getPlayer()->setGamemode($this->game->getPlugin()->getServer()->getGamemode());
        }

        if ($left && !$this->game->getHandler() instanceof EndHandler) {
            $this->game->broadcastMessage(LanguageManager::getMessage(KnownMessages::TOPIC_PLAYER, KnownMessages::PLAYER_QUIT), [
                TranslationKeys::PLAYER => $session->getPlayer()->getDisplayName(),
                TranslationKeys::PLAYERS_COUNT => count($this->getSessions()),
                TranslationKeys::PLAYERS_MAX => $this->game->getData()->getMaxPlayers()
            ]);
        }

        return true;
    }

    /**
     * @param int|null $state
     * @return Session[]
     */
    public function getSessions(?int $state = null): array
    {
        if ($state === null) {
            return $this->sessions;
        }

        $sessions = [];
        foreach ($this->sessions as $session) {
            if ($session->getState() !== $state) {
                continue;
            }
            $sessions[] = $session;
        }
        return $sessions;
    }

    public function addToGame(Session $session, bool $fromQueue = false): bool
    {
        if (!$this->game->isFree($fromQueue) || $session->inGame()) {
            return false;
        }

        if ($this->game->isLoading()) {
            $this->game->getQueueManager()->add($session->getPlayer()->getName(), []);
            $session->setQueued(true);
            return true;
        }

        $session->resetPlayer();

        $quitItem = VanillaItems::RED_BED()->setCustomName(LanguageManager::getMessage(KnownMessages::TOPIC_ITEMS, KnownMessages::ITEMS_QUIT_GAME));
        $quitItem->getNamedTag()->setInt(ItemConstants::ITEM_TYPE_IDENTIFIER, ItemConstants::ITEM_QUIT_GAME);

        $session->getPlayer()->getInventory()->setItem(8, $quitItem);

        $session->getPlayer()->getHungerManager()->setEnabled(false);

        $session->getPlayer()->setGamemode(GameMode::ADVENTURE());

        $session->getScoreboardManager()->setActive(true);
        $session->getScoreboardManager()->setTitle(LanguageManager::getMessage(KnownMessages::TOPIC_SCOREBOARD, KnownMessages::SCOREBOARD_TITLE));

        $team = $this->game->getTeamManager()->getRandomTeam();
        $team->addPlayer($session);

        $session->setState(Session::PLAYER_STATE_PLAYING);
        $session->setQueued(false);
        $session->setGame($this->game);

        $session->setPrevDisplayName($session->getPlayer()->getDisplayName());
        $session->setPrevNameTag($session->getPlayer()->getNameTag());
        $session->getPlayer()->setDisplayName(TextFormat::colorize($team->getColor() . $session->getPlayer()->getDisplayName()));
        $session->getPlayer()->setNameTag(TextFormat::colorize($team->getColor() . $session->getPlayer()->getDisplayName()));

        $session->getPlayer()->teleport(Position::fromObject($this->game->getLobby()->getSafeSpawn()->add(0.5, 0, 0.5), $this->game->getLobby()));

        $this->sessions[$session->getPlayer()->getUniqueId()->getBytes()] = $session;
        $this->game->broadcastMessage(LanguageManager::getMessage(KnownMessages::TOPIC_PLAYER, KnownMessages::PLAYER_JOIN), [
            TranslationKeys::PLAYER => $session->getPlayer()->getDisplayName(),
            TranslationKeys::PLAYERS_COUNT => count($this->getSessions()),
            TranslationKeys::PLAYERS_MAX => $this->game->getData()->getMaxPlayers()
        ]);

        return true;
    }
}
