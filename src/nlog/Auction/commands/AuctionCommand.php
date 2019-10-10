<?php

namespace nlog\Auction\commands;

use nlog\Auction\Auction;
use nlog\Auction\ItemInfo;
use nlog\Auction\tasks\AuctionTask;
use onebone\economyapi\EconomyAPI;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\Player;
use pocketmine\scheduler\TaskHandler;

class AuctionCommand extends PluginCommand {

	/** @var TaskHandler|null */
	public $taskHandler = null;

	public function __construct(Auction $owner) {
		parent::__construct("경매", $owner);
		$this->setDescription("경매 명령어");
		$this->taskHandler = null;
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
		if (!$sender instanceof Player) {
			$sender->sendMessage(Auction::$prefix . "인게임에서 실행해주세요.");
			return true;
		}
		if (!isset($args[0])) {
			$sender->sendMessage(Auction::$prefix . "/경매 시작 <수량> <최소 가격>");
			$sender->sendMessage(Auction::$prefix . "/경매 입찰 <가격>");
			return true;
		}
		if ($args[0] === "시작") {
			if ($this->taskHandler !== null) {
				$sender->sendMessage(Auction::$prefix . "이미 경매가 시작되었습니다.");
				return true;
			}
			if (!isset($args[2])) {
				$sender->sendMessage(Auction::$prefix . "/경매 시작 <수량> <최소 가격>");
				return true;
			}
			if (!is_numeric($args[1]) || !is_numeric($args[2])) {
				$sender->sendMessage(Auction::$prefix . "수량과 최소 가격은 정수로 입력하세요.");
				return true;
			}
			$count = intval($args[1]);
			$price = intval($args[2]);
			if ($count < 1) {
				$sender->sendMessage(Auction::$prefix . "수량은 0보다 커야합니다.");
				return true;
			}
			if ($price < 1000) {
				$sender->sendMessage(Auction::$prefix . "최소가격은 1000원 이상이여야 합니다.");
				return true;
			}
			$item = clone $sender->getInventory()->getItemInHand();
			if ($item->getId() === 0) {
				$sender->sendMessage(Auction::$prefix . "손에든 아이템이 없습니다.");
				return true;
			}
			if (isset(Auction::$banItemList[$item->getId() . ":" . $item->getMeta()])) {
				$sender->sendMessage(Auction::$prefix . "해당 아이템을 경매하실 수 없습니다..");
				return true;
			}
			$name = ItemInfo::getItemName($item->getId(), $item->getMeta());
			if ($name === $item->getId() . ":" . $item->getMeta() && !$item->hasCustomName()) {
				$sender->sendMessage(Auction::$prefix . "이름이 없는 아이템은 경매할 수 없습니다.");
				return true;
			}
			$item->setCount($count);
			if (!$sender->getInventory()->contains($item)) {
				$sender->sendMessage(Auction::$prefix . "아이템이 부족합니다.");
				return true;
			}
			$this->taskHandler = $this->getPlugin()->getScheduler()->scheduleDelayedTask(new AuctionTask($this->getPlugin(), $item, $sender->getName(), "", $price), 34 * 20);
			$sender->getInventory()->removeItem($item);
			$this->getPlugin()->getServer()->broadcastMessage(Auction::$prefix . "{$sender->getName()}님이 {$name} {$item->getCount()}개를 최저가 {$price}원으로 경매를 시작했습니다.");
			$this->getPlugin()->getServer()->broadcastMessage(Auction::$prefix . "/경매 입찰 <가격> 으로 경매에 참여하세요.");
		} elseif ($args[0] === "입찰") {
			if ($this->taskHandler === null) {
				$sender->sendMessage(Auction::$prefix . "진행중인 경매가 없습니다.");
				return true;
			}
			if ($this->taskHandler->getTask()->seller === $sender->getName()) {
				$sender->sendMessage(Auction::$prefix . "당신은 판매자이기 때문에 입찰할 수 없습니다.");
				return true;
			}
			if ($this->taskHandler->getTask()->customer === $sender->getName()) {
				$sender->sendMessage(Auction::$prefix . "당신은 이미 경매에 참여했습니다.");
				return true;
			}
			if (!isset($args[1])) {
				$sender->sendMessage(Auction::$prefix . "/경매 입찰 <가격>");
				return true;
			}
			if (!is_numeric($args[1])) {
				$sender->sendMessage(Auction::$prefix . "입찰가는 정수로 입력하세요.");
				return true;
			}
			$money = intval($args[1]);
			if ($money < 1) {
				$sender->sendMessage(Auction::$prefix . "입찰가는 0보다 커야합니다.");
				return true;
			}
			if ($money > EconomyAPI::getInstance()->myMoney($sender)) {
				$sender->sendMessage(Auction::$prefix . "돈이 부족합니다.");
				return true;
			}
			if ($this->taskHandler->getTask()->price >= $money) {
				$sender->sendMessage(Auction::$prefix . "입찰가보다 돈을 더 입력해야합니다.");
				return true;
			}
			if ($money % 1000 !== 0) {
				$sender->sendMessage(Auction::$prefix . "입찰가는 1000원의 배수만 입력할 수 있습니다.");
				return true;
			}
			$name = $this->taskHandler->getTask()->seller;
			$timeLeft = $this->taskHandler->getTask()->timeLeft;
			$item = clone $this->taskHandler->getTask()->item;
			$this->taskHandler->cancel();
			$this->taskHandler = $this->getPlugin()->getScheduler()->scheduleDelayedTask(new AuctionTask($this->getPlugin(), $item, $name, $sender->getName(), $money, $timeLeft), $timeLeft * 20);
			$this->getPlugin()->getServer()->broadcastMessage(Auction::$prefix . "{$sender->getName()}님이 {$money}원으로 입찰하였습니다.");
			$this->getPlugin()->getServer()->broadcastMessage(Auction::$prefix . "입찰할 사람이 없을 시 {$timeLeft}초 후 입찰이 종료됩니다.");
		} else {
			$sender->sendMessage(Auction::$prefix . "/경매 시작 <수량> <최소 가격>");
			$sender->sendMessage(Auction::$prefix . "/경매 입찰 <가격>");
		}
		return true;
	}

}