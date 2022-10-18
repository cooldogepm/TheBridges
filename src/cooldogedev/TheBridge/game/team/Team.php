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
use cooldogedev\TheBridge\session\Session;
use cooldogedev\TheBridge\utility\CageBuilder;
use cooldogedev\TheBridge\utility\message\LanguageManager;
use cooldogedev\TheBridge\utility\Utils;
use pocketmine\block\Block;
use pocketmine\block\utils\DyeColor;
use pocketmine\block\VanillaBlocks;
use pocketmine\entity\Location;
use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat;

final class Team
{
    protected Location $spawn;
    protected Vector3 $min;
    protected Vector3 $max;
    protected int $goals;
    /** @var Session[] */
    protected array $sessions;

    public function __construct(protected string $type, protected Game $game, TeamData $data)
    {
        $this->min = Vector3::minComponents(Utils::parseVec3($data->getMin()), Utils::parseVec3($data->getMax()))->floor();
        $this->max = Vector3::maxComponents(Utils::parseVec3($data->getMin()), Utils::parseVec3($data->getMax()))->floor();

        $spawn = Utils::parseLoc($data->getSpawn());
        $this->spawn = Location::fromObject($spawn->add(0.5, 0, 0.5), $this->game->getWorld(), $spawn->yaw, $spawn->pitch);

        $this->goals = 0;
        $this->sessions = [];
    }

    public function getMin(): Vector3
    {
        return $this->min;
    }

    public function getMax(): Vector3
    {
        return $this->max;
    }

    public function getSpawn(): Location
    {
        return $this->spawn;
    }

    public function isFull(): bool
    {
        return count($this->sessions) >= $this->game->getData()->getPlayersPerTeam();
    }

    public function isEmpty(): bool
    {
        return count($this->sessions) === 0;
    }

    /**
     * @return Session[]
     */
    public function getSessions(): array
    {
        return $this->sessions;
    }

    public function getPrefix(bool $uppercase = false): string
    {
        return $this->getColor() . ($uppercase ? strtoupper($this->type) : $this->type);
    }

    public function getColor(): string
    {
        return $this->type === TeamData::TEAM_TYPE_BLUE ? TextFormat::BLUE : TextFormat::RED;
    }

    public function getGoals(): int
    {
        return $this->goals;
    }

    public function getScore(): string
    {
        $char = $this->getColor() . "[" . substr($this->type, 0, 1) . "]";
        $scoreBar = str_repeat($this->getColor() . "█", $this->goals);
        $scoreBar .= str_repeat(TextFormat::GRAY . "█", 5 - $this->goals);

        return TextFormat::BOLD . $char . " " . $scoreBar;
    }

    public function addGoal(): void
    {
        $this->goals++;
    }

    public function addPlayer(Session $session): bool
    {
        if (count($this->sessions) >= $this->game->getData()->getPlayersPerTeam() || $this->inTeam($session) || $session->inTeam()) {
            return false;
        }

        $session->setTeam($this);
        $this->sessions[$session->getId()] = $session;

        return true;
    }

    public function inTeam(Session $session): bool
    {
        return isset($this->sessions[$session->getId()]);
    }

    public function broadcastMessage(string $message, array $translations = []): void
    {
        if (trim(TextFormat::clean($message)) === "") {
            return;
        }

        foreach ($this->sessions as $session) {
            $session->getPlayer()->sendMessage(LanguageManager::translate($message, $translations));
        }
    }

    public function broadcastSubTitle(string $message, array $translations = []): void
    {
        if (trim(TextFormat::clean($message)) === "") {
            return;
        }

        foreach ($this->sessions as $session) {
            $session->getPlayer()->sendSubTitle(LanguageManager::translate($message, $translations));
        }
    }

    public function broadcastTitle(string $message, string $subtitle = "", array $translations = [], int $fadeIn = 5, int $stay = 20, int $fadeOut = 5): void
    {
        if (trim(TextFormat::clean($message)) === "") {
            return;
        }

        foreach ($this->sessions as $session) {
            $session->getPlayer()->sendTitle(LanguageManager::translate($message, $translations), fadeIn: $fadeIn, stay: $stay, fadeOut: $fadeOut);

            trim(TextFormat::clean($subtitle)) !== "" && $session->getPlayer()->sendSubtitle(LanguageManager::translate($subtitle, $translations));
        }
    }

    public function removePlayer(Session $session): bool
    {
        if (!$this->inTeam($session)) {
            return false;
        }

        unset($this->sessions[$session->getId()]);

        return true;
    }

    public function buildCage(?Block $block = null): void
    {
        // Set the block to something else if it is null
        $block = $block !== null ? $block : VanillaBlocks::STAINED_GLASS()->setColor($this->getDyeColor());

        $positions = CageBuilder::build($this->spawn, 6, 4);

        /**
         * Set the blocks synchronously, setting them in another thread means we don't have an ETA
         * for the chunks to be set therefore causing the players to fall, immobilizing them fixes it.
         *
         * TODO: Find a workaround for this.
         */
        foreach ($positions as $position) {
            $this->game->getWorld()->setBlock($position, $block);
        }

//        $chunks = [];
//        foreach ($positions as $idx => $position) {
//            $x = $position->x >> Chunk::COORD_BIT_SIZE;
//            $z = $position->z >> Chunk::COORD_BIT_SIZE;
//
//            $chunks[World::chunkHash($x, $z)] = $this->game->getWorld()->loadChunk($x, $z);
//
//            $positions[$idx] = Utils::stringifyVec3($position);
//        }
//
//        $task = new AsyncSetBlocks($chunks, $block !== null ? $block->getFullId(), $positions);
//        $task->setClosure(function (AsyncSetBlocks $task): void {
//            $chunks = array_map(fn(string $chunk) => FastChunkSerializer::deserializeTerrain($chunk), igbinary_unserialize($task->getResult()));
//
//            foreach ($chunks as $hash => $chunk) {
//                World::getXZ($hash, $x, $z);
//
//                $this->game->getWorld()->setChunk($x, $z, $chunk);
//            }
//        });
//
//        BackgroundTaskPool::getInstance()->submitTask($task);
    }

    public function getDyeColor(): DyeColor
    {
        return $this->type === TeamData::TEAM_TYPE_BLUE ? DyeColor::BLUE() : DyeColor::RED();
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function isWithin(Vector3 $vec3): bool
    {
        return $vec3->x >= $this->min->x && $vec3->x <= $this->max->x && $vec3->y >= $this->min->y && $vec3->y <= $this->max->y && $vec3->z >= $this->min->z && $vec3->z <= $this->max->z;
    }
}
