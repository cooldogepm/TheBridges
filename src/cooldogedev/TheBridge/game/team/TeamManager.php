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

namespace cooldogedev\TheBridge\game\team;

use cooldogedev\TheBridge\game\data\TeamData;
use cooldogedev\TheBridge\game\Game;
use pocketmine\math\Vector3;

final class TeamManager
{
    /** @var Team[] */
    protected ?array $teams = [];

    public function __construct(protected Game $game)
    {
    }

    public function checkGoals(): bool
    {
        $goals = 0;

        foreach ($this->teams as $team) {
            if ($team->getGoals() > $goals) {
                $goals = $team->getGoals();
            }
        }

        return $goals >= 5;
    }

    public function getEnemy(Team $team): ?Team
    {
        return $team->getType() === TeamData::TEAM_TYPE_BLUE ? $this->getTeam(TeamData::TEAM_TYPE_RED) : $this->getTeam(TeamData::TEAM_TYPE_BLUE);
    }

    public function getTeam(string $type): ?Team
    {
        return $this->teams[$type] ?? null;
    }

    public function getRandomTeam(): ?Team
    {
        $availableTeams = [];

        foreach ($this->teams as $team) {
            if ($team->isFull()) {
                continue;
            }

            $availableTeams[] = $team;
        }

        if (count($availableTeams) === 0) {
            return null;
        }

        shuffle($availableTeams);

        usort($availableTeams, fn(Team $a, Team $b) => count($a->getSessions()) <=> count($b->getSessions()));

        return $availableTeams[0];
    }

    public function canStart(): bool
    {
        $canStart = true;

        foreach ($this->teams as $team) {
            if ($team->isEmpty() || $this->game->getData()->getMinPlayersPerTeam() > count($team->getSessions())) {
                $canStart = false;

                break;
            }
        }

        return $canStart;
    }

    public function addTeam(string $type, TeamData $data): void
    {
        $this->teams[$type] = new Team($type, $this->game, $data);
    }

    public function getTeamByPosition(Vector3 $vec3): ?Team
    {
        foreach ($this->teams as $team) {
            if ($team->isWithin($vec3)) {
                return $team;
            }
        }

        return null;
    }

    public function getTeams(bool $checkEmpty = false): ?array
    {
        if ($checkEmpty) {
            $teams = [];

            foreach ($this->teams as $id => $team) {
                if (!$team->isEmpty()) {
                    $teams[$id] = $team;
                }
            }

            return $teams;
        }

        return $this->teams;
    }
}
