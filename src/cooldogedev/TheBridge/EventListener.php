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

use cooldogedev\TheBridge\constant\ItemConstants;
use cooldogedev\TheBridge\game\Game;
use cooldogedev\TheBridge\game\handler\EndHandler;
use cooldogedev\TheBridge\game\handler\GraceHandler;
use cooldogedev\TheBridge\game\handler\PreStartHandler;
use cooldogedev\TheBridge\game\handler\RoundHandler;
use cooldogedev\TheBridge\utility\message\KnownMessages;
use cooldogedev\TheBridge\utility\message\LanguageManager;
use cooldogedev\TheBridge\utility\message\TranslationKeys;
use Exception;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockBurnEvent;
use pocketmine\event\block\BlockFormEvent;
use pocketmine\event\block\BlockGrowEvent;
use pocketmine\event\block\BlockMeltEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockSpreadEvent;
use pocketmine\event\block\LeavesDecayEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityItemPickupEvent;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\entity\EntityTrampleFarmlandEvent;
use pocketmine\event\entity\ProjectileHitBlockEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\VanillaItems;
use pocketmine\player\Player;
use pocketmine\scheduler\CancelTaskException;
use pocketmine\scheduler\ClosureTask;

final class EventListener implements Listener
{
    protected TheBridge $plugin;

    public function __construct(TheBridge $plugin)
    {
        $this->plugin = $plugin;
    }

    /**
     * @ignoreCanceled true
     */
    public function onPlayerItemUse(PlayerItemUseEvent $event): void
    {
        $player = $event->getPlayer();
        $item = $event->getItem();
        $session = $this->getPlugin()->getSessionManager()->getSession($player->getUniqueId()->getBytes());
        $game = $session->getGame();

        if ($game === null) {
            return;
        }

        try {
            $actionType = $item->getNamedTag()->getInt(ItemConstants::ITEM_TYPE_IDENTIFIER);
        } catch (Exception) {
            return;
        }

        if ($actionType == ItemConstants::ITEM_QUIT_GAME) {
            $game->getPlayerManager()->removeFromGame($session);
        }
    }

    public function getPlugin(): TheBridge
    {
        return $this->plugin;
    }

    /**
     * @ignoreCanceled true
     */
    public function onPlayerItemHeld(PlayerItemHeldEvent $event): void
    {
        $player = $event->getPlayer();
        $item = $event->getItem();
        $session = $this->getPlugin()->getSessionManager()->getSession($player->getUniqueId()->getBytes());
        $game = $session->getGame();

        if ($game === null) {
            return;
        }

        try {
            $actionType = $item->getNamedTag()->getInt(ItemConstants::ITEM_TYPE_IDENTIFIER);
        } catch (Exception) {
            return;
        }

        switch ($actionType) {
            case ItemConstants::ITEM_BACK_TO_LOBBY:
                $game->getPlayerManager()->removeFromGame($session);
                break;
            case ItemConstants::ITEM_PLAY_AGAIN:
                $this->plugin->getGameManager()->queueToGame([$player->getName()]);
                break;
        }
    }

    /**
     * @ignoreCanceled true
     * @priority MONITOR
     */
    public function onPlayerDropItem(PlayerDropItemEvent $event): void
    {
        $player = $event->getPlayer();
        $session = $this->getPlugin()->getSessionManager()->getSession($player->getUniqueId()->getBytes());

        if ($session->inGame()) {
            $event->cancel();
        }
    }

    /**
     * @ignoreCanceled true
     * @priority MONITOR
     */
    public function onPlayerChat(PlayerChatEvent $event): void
    {
        $player = $event->getPlayer();
        $session = $this->getPlugin()->getSessionManager()->getSession($player->getUniqueId()->getBytes());
        $game = $session->getGame();

        if ($session->getSetupHandler() !== null) {
            $session->getSetupHandler()->handleMessage($session, strtolower($event->getMessage())) && $event->cancel();
            return;
        }

        if ($game === null) {
            return;
        }

        $translations = [
            TranslationKeys::PLAYER => $player->getDisplayName(),
            TranslationKeys::MESSAGE => $event->getMessage(),
        ];

        $event->setFormat(
            $game->getHandler() instanceof EndHandler ?
                LanguageManager::translate(LanguageManager::getMessage(KnownMessages::TOPIC_CHAT, KnownMessages::CHAT_END), $translations)
                :
                LanguageManager::translate(LanguageManager::getMessage(KnownMessages::TOPIC_CHAT, KnownMessages::CHAT_MATCH), $translations)
        );
    }

    /**
     * @ignoreCanceled true
     * @priority MONITOR
     */
    public function onInventoryTransaction(InventoryTransactionEvent $event): void
    {
        $player = $event->getTransaction()->getSource();
        $session = $this->getPlugin()->getSessionManager()->getSession($player->getUniqueId()->getBytes());
        $game = $session->getGame();

        if ($game !== null && !$game->getHandler() instanceof RoundHandler && !$game->getHandler() instanceof GraceHandler) {
            $event->cancel();
        }
    }

    /**
     * @ignoreCanceled true
     */
    public function onPlayerLogin(PlayerLoginEvent $event): void
    {
        $player = $event->getPlayer();
        $this->getPlugin()->getSessionManager()->createSession($player);
    }

    public function onPlayerJoin(PlayerJoinEvent $event): void
    {
        $player = $event->getPlayer();
        $session = $this->getPlugin()->getSessionManager()->getSession($player->getUniqueId()->getBytes());

        if ($this->getPlugin()->getConfig()->get("behaviour")["queue-on-login"] && $session) {
            $this->getPlugin()->getGameManager()->queueToGame([$player->getName()]);
        }
    }

    public function onPlayerQuit(PlayerQuitEvent $event): void
    {
        $player = $event->getPlayer();
        $session = $this->getPlugin()->getSessionManager()->getSession($player->getUniqueId()->getBytes());
        $session->save();

        $game = $session->getGame();
        $game?->getPlayerManager()->removeFromGame($session);

        $this->getPlugin()->getSessionManager()->removeSession($player->getUniqueId()->getBytes());
    }

    /**
     * @priority MONITOR
     */
    public function onPlayerBreak(BlockBreakEvent $event): void
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $session = $this->getPlugin()->getSessionManager()->getSession($player->getUniqueId()->getBytes());
        $game = $session->getGame();

        if ($session->getSetupHandler() !== null) {
            $session->getSetupHandler()->handleBlockBreak($session, $event->getBlock()->getPosition());
            $event->cancel();

            return;
        }

        if ($game === null) {
            return;
        }

        if ($game->getHandler() instanceof RoundHandler && $game->getBlockStorage()->removeBlock($block->getPosition())) {
            $event->setDrops([]);
            return;
        }

        $event->cancel();
    }

    public function onLeavesDecay(LeavesDecayEvent $event): void
    {
        if (str_starts_with($event->getBlock()->getPosition()->getWorld()->getFolderName(), Game::GAME_WORLD_IDENTIFIER)) {
            $event->cancel();
        }
    }

    public function onBlockBurn(BlockBurnEvent $event): void
    {
        if (str_starts_with($event->getBlock()->getPosition()->getWorld()->getFolderName(), Game::GAME_WORLD_IDENTIFIER)) {
            $event->cancel();
        }
    }

    public function onBlockMelt(BlockMeltEvent $event): void
    {
        if (str_starts_with($event->getBlock()->getPosition()->getWorld()->getFolderName(), Game::GAME_WORLD_IDENTIFIER)) {
            $event->cancel();
        }
    }

    public function onBlockGrow(BlockGrowEvent $event): void
    {
        if (str_starts_with($event->getBlock()->getPosition()->getWorld()->getFolderName(), Game::GAME_WORLD_IDENTIFIER)) {
            $event->cancel();
        }
    }

    public function onBlockForm(BlockFormEvent $event): void
    {
        if (str_starts_with($event->getBlock()->getPosition()->getWorld()->getFolderName(), Game::GAME_WORLD_IDENTIFIER)) {
            $event->cancel();
        }
    }

    public function onEntityTrampleFarmland(EntityTrampleFarmlandEvent $event): void
    {
        if (str_starts_with($event->getBlock()->getPosition()->getWorld()->getFolderName(), Game::GAME_WORLD_IDENTIFIER)) {
            $event->cancel();
        }
    }

    public function onBlockSpread(BlockSpreadEvent $event): void
    {
        if (str_starts_with($event->getBlock()->getPosition()->getWorld()->getFolderName(), Game::GAME_WORLD_IDENTIFIER)) {
            $event->cancel();
        }
    }

    /**
     * @priority MONITOR
     */
    public function onPlayerPlace(BlockPlaceEvent $event): void
    {
        $player = $event->getPlayer();
        $block = $event->getBlock();
        $session = $this->getPlugin()->getSessionManager()->getSession($player->getUniqueId()->getBytes());
        $game = $session->getGame();

        if ($game === null) {
            return;
        }

        if ($game->getHandler() instanceof RoundHandler && $game->getBlockStorage()->addBlock($block->getPosition())) {
            return;
        }

        $event->cancel();
    }

    /**
     * @ignoreCanceled
     * @priority MONITOR
     */
    public function onEntityDamage(EntityDamageEvent $event): void
    {
        $victim = $event->getEntity();

        if (!$victim instanceof Player) {
            return;
        }

        $victim = $this->getPlugin()->getSessionManager()->getSession($victim->getUniqueId()->getBytes());
        $game = $victim->getGame();

        if ($game === null) {
            return;
        }

        if ($event->getCause() === EntityDamageEvent::CAUSE_VOID && $game->getHandler() instanceof PreStartHandler) {
            $victim->getPlayer()->teleport($game->getLobby()->getSafeSpawn()->add(0.5, 0, 0.5));

            $event->cancel();

            return;
        }

        if ($event->getCause() === EntityDamageEvent::CAUSE_FALL || !$game->getHandler() instanceof RoundHandler) {
            $event->cancel();

            return;
        }

        if ($victim->getPlayer()->getHealth() > $event->getBaseDamage() && $event->getCause() !== EntityDamageEvent::CAUSE_VOID) {
            return;
        }

        $message = match ($event->getCause()) {
            EntityDamageEvent::CAUSE_VOID => KnownMessages::DEATH_VOID,
            EntityDamageEvent::CAUSE_ENTITY_ATTACK => KnownMessages::DEATH_SLAIN,
            EntityDamageEvent::CAUSE_PROJECTILE => KnownMessages::DEATH_SHOOT,
            default => KnownMessages::DEATH_DEFAULT,
        };

        $translations = [
            TranslationKeys::PLAYER => $victim->getPlayer()->getDisplayName(),
        ];

        $addKill = false;

        if (
            $event->getCause() === EntityDamageEvent::CAUSE_VOID &&
            $victim->getLastDamageTime() !== null &&
            $victim->getLastDamageTime() + 15 >= time() &&
            $victim->getLastDamager() !== null &&
            $victim->getLastDamager()->getPlayer()->isConnected()
        ) {
            $message = KnownMessages::DEATH_KNOCK;
            $translations[TranslationKeys::ATTACKER] = $victim->getLastDamager()->getPlayer()->getDisplayName();

            $addKill = true;
        }

        if ($event instanceof EntityDamageByEntityEvent && $event->getDamager() instanceof Player) {
            $translations[TranslationKeys::ATTACKER] = $event->getDamager()->getDisplayName();

            $addKill = true;
        }

        if ($addKill) {
            $victim->getLastDamager()->addGameKill();
            $victim->getLastDamager()->addKill(1);
        }

        $victim->sendItems();
        $victim->getPlayer()->teleport($victim->getTeam()->getSpawn());

        $game->broadcastMessage(LanguageManager::getMessage(KnownMessages::TOPIC_DEATH, $message), $translations);

        $victim->addDeath(1);

        $event->cancel();
    }

    public function onBowShoot(EntityShootBowEvent $event): void
    {
        $player = $event->getEntity();

        if (!$player instanceof Player) {
            return;
        }

        $session = $this->getPlugin()->getSessionManager()->getSession($player->getUniqueId()->getBytes());

        if ($session->getGame() === null) {
            return;
        }

        $duration = 5;
        $current = $duration;

        $this->getPlugin()->getScheduler()->scheduleRepeatingTask(new ClosureTask(function () use ($player, $duration, &$current): void {
            if ($player->getInventory()->contains(VanillaItems::ARROW())) {
                $player->getXpManager()->setXpProgress(0);
                $player->getXpManager()->setXpLevel(0);

                throw new CancelTaskException();
            }

            $player->getXpManager()->setXpProgress($current / $duration);
            $player->getXpManager()->setXpLevel((int)round($current));

            $current -= 0.1;

            if ($current <= 0 || $player->getInventory()->contains(VanillaItems::ARROW())) {
                $player->getXpManager()->setXpProgress(0);
                $player->getXpManager()->setXpLevel(0);
                $player->getInventory()->getItem(8)->isNull() ? $player->getInventory()->setItem(8, VanillaItems::ARROW()) : $player->getInventory()->addItem(VanillaItems::ARROW());

                throw new CancelTaskException();
            }
        }), 1);
    }

    public function onProjectileHitBlock(ProjectileHitBlockEvent $event): void
    {
        $player = $event->getEntity()->getOwningEntity();

        if (!$player instanceof Player) {
            return;
        }

        $session = $this->getPlugin()->getSessionManager()->getSession($player->getUniqueId()->getBytes());

        if (!$session->inGame()) {
            return;
        }

        $event->getEntity()->flagForDespawn();
    }

    public function onItemPickUp(EntityItemPickupEvent $event): void
    {
        $player = $event->getEntity();

        if (!$player instanceof Player) {
            return;
        }

        $session = $this->getPlugin()->getSessionManager()->getSession($player->getUniqueId()->getBytes());

        if (!$session->inGame()) {
            return;
        }

        $event->getOrigin()->flagForDespawn();
    }

    /**
     * @ignoreCanceled
     * @priority MONITOR
     */
    public function onEntityDamageByEntity(EntityDamageByEntityEvent $event): void
    {
        $victim = $event->getEntity();
        $attacker = $event->getDamager();

        if (!$victim instanceof Player || !$attacker instanceof Player) {
            return;
        }

        $victim = $this->getPlugin()->getSessionManager()->getSession($victim->getUniqueId()->getBytes());
        $attacker = $this->getPlugin()->getSessionManager()->getSession($attacker->getUniqueId()->getBytes());

        if (!$victim->inGame() || !$attacker->inGame()) {
            return;
        }

        if ($victim->getTeam()->inTeam($attacker)) {
            $event->cancel();
            return;
        }

        $victim->setLastDamager($attacker);
        $victim->setLastDamageTime(time());
    }
}
