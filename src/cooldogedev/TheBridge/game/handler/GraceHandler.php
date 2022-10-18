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
use cooldogedev\TheBridge\session\Session;
use cooldogedev\TheBridge\utility\message\KnownMessages;
use cooldogedev\TheBridge\utility\message\LanguageManager;
use cooldogedev\TheBridge\utility\message\TranslationKeys;
use pocketmine\player\GameMode;
use pocketmine\utils\TextFormat;

/**
 * This handler is responsible for handling the grace phase of the game after every round.
 */
final class GraceHandler extends IHandler
{
    protected int $timeLeft;
    protected bool $sentTitle = false;

    public function __construct(Game $game, ?Session $scorer = null)
    {
        parent::__construct($game);

        $this->timeLeft = $this->game->getData()->getGraceDuration();

        $scorer === null && $game->broadcastTitle(TextFormat::colorize(TextFormat::RESET), stay: $this->timeLeft * 20);

        foreach ($game->getTeamManager()->getTeams() as $team) {
            $team->buildCage();
        }

        foreach ($game->getPlayerManager()->getSessions() as $session) {
            $session->getPlayer()->teleport($session->getTeam()->getSpawn());

            $session->sendItems();

            $session->getPlayer()->setGamemode(GameMode::ADVENTURE());
        }
    }

    public function handleTicking(): void
    {
        $this->handleScoreboardUpdates();

        if ($this->timeLeft > 0) {
            if (!$this->sentTitle) {
                $scorer = $this->game->getPlayerManager()->getLastScorer();

                $scorer !== null && $this->game->broadcastTitle(LanguageManager::getMessage(KnownMessages::TOPIC_GRACE, KnownMessages::GRACE_TITLE), translations: [
                    TranslationKeys::PLAYER => $scorer->getPlayer()->getDisplayName(),
                ], stay: $this->timeLeft * 20);

                $this->sentTitle = true;
            }

            $translations = [
                TranslationKeys::COUNTDOWN => $this->timeLeft,
            ];
            $this->game->broadcastSubTitle(LanguageManager::getMessage(KnownMessages::TOPIC_GRACE, KnownMessages::GRACE_SUBTITLE), $translations);
            $this->game->broadcastMessage(LanguageManager::getMessage(KnownMessages::TOPIC_GRACE, KnownMessages::GRACE_MESSAGE), $translations);

            $this->timeLeft--;
        } else {
            $this->game->setHandler(new RoundHandler($this->game));
        }
    }

    public function handleScoreboardUpdates(): void
    {
        if ($this->timeLeft < 1) {
            return;
        }

        foreach ($this->game->getPlayerManager()->getSessions() as $session) {
            if (!$session->getPlayer()->isOnline()) {
                continue;
            }

            $translations = [
                TranslationKeys::TIME_LEFT => $this->game->getTimeLeft(),
                TranslationKeys::COUNTDOWN => $this->timeLeft,
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

    public function getScoreboardBody(): array
    {
        $scoreboardData = LanguageManager::getArray(KnownMessages::TOPIC_SCOREBOARD, KnownMessages::SCOREBOARD_BODY);

        return $scoreboardData["match"];
    }
}
