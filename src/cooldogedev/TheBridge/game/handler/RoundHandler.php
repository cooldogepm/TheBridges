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

namespace cooldogedev\TheBridge\game\handler;

use cooldogedev\TheBridge\game\Game;
use cooldogedev\TheBridge\game\team\Team;
use cooldogedev\TheBridge\session\Session;
use cooldogedev\TheBridge\utility\message\KnownMessages;
use cooldogedev\TheBridge\utility\message\LanguageManager;
use cooldogedev\TheBridge\utility\message\TranslationKeys;
use pocketmine\block\VanillaBlocks;
use pocketmine\player\GameMode;

final class RoundHandler extends IHandler
{
    public function __construct(Game $game)
    {
        parent::__construct($game);

        foreach ($game->getPlayerManager()->getSessions() as $session) {
            $session->getPlayer()->setGamemode(GameMode::SURVIVAL());
        }

        foreach ($game->getTeamManager()->getTeams() as $team) {
            $team->buildCage(VanillaBlocks::AIR());
        }

        $game->broadcastTitle(LanguageManager::getMessage(KnownMessages::TOPIC_FIGHT, KnownMessages::FIGHT_TITLE));
        $game->broadcastSubTitle(LanguageManager::getMessage(KnownMessages::TOPIC_FIGHT, KnownMessages::FIGHT_SUBTITLE));
        $game->broadcastMessage(LanguageManager::getMessage(KnownMessages::TOPIC_FIGHT, KnownMessages::FIGHT_MESSAGE));
    }

    public function handleTicking(): void
    {
        $this->handleScoreboardUpdates();

        foreach ($this->game->getPlayerManager()->getSessions() as $session) {
            $team = $this->game->getTeamManager()->getTeamByPosition($session->getPlayer()->getPosition()->floor());

            if ($team !== null) {
                $this->checkScore($session, $team);
            }
        }
    }

    public function checkScore(Session $session, Team $team): void
    {
        if ($team !== $session->getTeam()) {
            $session->addGoal();
            $session->getTeam()->addGoal();

            if ($this->game->getTeamManager()->checkGoals()) {
                $this->game->setHandler(null);
                $this->game->calculateResults();

                return;
            }

            $this->game->getPlayerManager()->setLastScorer($session);
            $this->game->setHandler(new GraceHandler($this->game, $session));
        } else {
            $session->getPlayer()->teleport($team->getSpawn());
        }
    }

    public function handleScoreboardUpdates(): void
    {
        foreach ($this->game->getPlayerManager()->getSessions() as $session) {
            if (!$session->getPlayer()->isOnline()) {
                continue;
            }

            $translations = [
                TranslationKeys::TIME_LEFT => $this->game->getTimeLeft(),
                TranslationKeys::OWN_SCORE => $session->getTeam()->getScore(),
                TranslationKeys::ENEMY_SCORE => $this->game->getTeamManager()->getEnemy($session->getTeam())->getScore(),
                TranslationKeys::KILLS => $session->getGameKills(),
                TranslationKeys::GOALS => $session->getGoals(),
                TranslationKeys::MODE => $this->game->getData()->getMode(),
            ];

            $lines = array_map(fn($line) => $line !== "" ? LanguageManager::translate($line, $translations) : $line, $this->getScoreboardBody());

            $session->getScoreboardManager()->setLines($lines);
            $session->getScoreboardManager()->onUpdate();
        }
    }

    protected function getScoreboardBody(): array
    {
        $scoreboardData = LanguageManager::getArray(KnownMessages::TOPIC_SCOREBOARD, KnownMessages::SCOREBOARD_BODY);

        return $scoreboardData["match"];
    }
}
