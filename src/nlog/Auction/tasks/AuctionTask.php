<?php

namespace nlog\Auction\tasks;

use nlog\Auction\Auction;
use onebone\economyapi\EconomyAPI;
use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\scheduler\PluginTask;

class AuctionTask extends PluginTask {

	/** @var Item */
	public $item;
	/** @var string */
	public $seller;
	/** @var string */
	public $customer;
	/** @var int */
	public $price;
	/** @var int */
	public $timeLeft;
	/** @var Auction */
	protected $owner;

	public function __construct(Auction $owner, Item $item, string $seller, string $customer, int $price, int $timeLeft = 34) {
		parent::__construct($owner);
		$this->item = clone $item;
		$this->seller = $seller;
		$this->customer = $customer;
		$this->price = $price;
		$this->timeLeft = $timeLeft <= 10 ? $timeLeft : $timeLeft - 4;
	}

	public function onRun(int $currentTick, bool $isUnloading = false) {
		if ($isUnloading) {
			$this->owner->saveOwnerItem($this->seller, $this->item);
			$this->owner->AuctionCommand->taskHandler->cancel();
			$this->owner->AuctionCommand->taskHandler = null;
			return;
		}
		$customer = $this->owner->getServer()->getPlayerExact($this->customer);
		$seller = $this->owner->getServer()->getPlayerExact($this->seller);
		if ($this->customer === "") {
			$this->owner->getServer()->broadcastMessage(Auction::$prefix . "구매자가 없어 경매가 종료되었습니다.");
			if ($seller instanceof Player) {
				$seller->getInventory()->addItem($this->item);
			} else {
				$this->owner->saveOwnerItem($this->seller, $this->item);
			}
			$this->owner->AuctionCommand->taskHandler = null;
			return;
		}
		if (EconomyAPI::getInstance()->myMoney($this->customer) < $this->price) {
			$this->owner->getServer()->broadcastMessage(Auction::$prefix . "구매자의 돈이 부족하여 경매가 종료되었습니다.");
			if ($seller instanceof Player) {
				$seller->getInventory()->addItem($this->item);
			} else {
				$this->owner->saveOwnerItem($this->seller, $this->item);
			}
			$this->owner->AuctionCommand->taskHandler = null;
			return;
		}
		if (!$customer instanceof Player) {
			$this->owner->saveCustomerItem($this->customer, $this->item);
		} else {
			$customer->getInventory()->addItem($this->item);
		}
		$this->owner->getServer()->broadcastMessage(Auction::$prefix . "{$this->customer}님에게 {$this->price}원으로 낙찰되었습니다.");
		EconomyAPI::getInstance()->reduceMoney($this->customer, $this->price);
		EconomyAPI::getInstance()->addMoney($this->seller, $this->price);
		$this->owner->AuctionCommand->taskHandler = null;
	}

}