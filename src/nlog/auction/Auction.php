<?php

namespace nlog\auction;

use nlog\Auction\commands\AuctionCommand;
use nlog\Auction\tasks\AuctionTask;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\TaskHandler;
use pocketmine\utils\Config;

class Auction extends PluginBase implements Listener {

	/** @var string */
	public static $prefix = "§b§l[경매] §r§7";

	/** @var array */
	public static $banItemList = [ //TODO: NBT Support
			"433:0" => "", //코러스 (레오코인)
			ItemIds::GOLDEN_APPLE . ":0" => ""
	];

	/** @var AuctionCommand */
	public $AuctionCommand;

	/** @var array */
	private $ownerItem;

	/** @var array */
	private $customerItem;

	public function onEnable() {
		@mkdir($this->getDataFolder());
		$this->ownerItem = (new Config($this->getDataFolder() . "ownerItem.json", Config::JSON))->getAll();
		$this->customerItem = (new Config($this->getDataFolder() . "customerItem.json", Config::JSON))->getAll();
		$this->AuctionCommand = new AuctionCommand($this);
		$this->getServer()->getCommandMap()->register("auction", $this->AuctionCommand);
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onDisable() {
		if (
				$this->AuctionCommand->taskHandler instanceof TaskHandler
				&& $this->AuctionCommand->taskHandler->getTask() instanceof AuctionTask
		) {
			$this->AuctionCommand->taskHandler->getTask()->onRun(0, true);
		}
		$this->save();
	}

	public function save() {
		$c = new Config($this->getDataFolder() . "ownerItem.json", Config::JSON);
		$c->setAll($this->ownerItem);
		$c->save();

		$c = new Config($this->getDataFolder() . "customerItem.json", Config::JSON);
		$c->setAll($this->customerItem);
		$c->save();
	}

	public function saveOwnerItem(string $seller, Item $item) {
		if (!isset($this->ownerItem[$seller])) {
			$this->ownerItem[$seller] = [];
		}
		$this->ownerItem[$seller][] = $item->jsonSerialize();
		$this->save();
	}

	public function saveCustomerItem(string $customer, Item $item) {
		if (!isset($this->customerItem[$customer])) {
			$this->customerItem[$customer] = [];
		}
		$this->customerItem[$customer][] = $item->jsonSerialize();
		$this->save();
	}

	public function onJoin(PlayerJoinEvent $ev) {
		if (isset($this->ownerItem[$ev->getPlayer()->getName()])) {
			$ev->getPlayer()->sendMessage(self::$prefix . "입찰되지 않아 아이템이 돌려드립니다.");
			foreach ($this->ownerItem[$ev->getPlayer()->getName()] as $json) {
				$item = Item::jsonDeserialize($json);
				$ev->getPlayer()->getInventory()->addItem($item);
				$name = ItemInfo::getItemName($item->getId(), $item->getMeta());
				$ev->getPlayer()->sendMessage(self::$prefix . "{$name} {$item->getCount()}개를 받았습니다.");
			}
			unset($this->ownerItem[$ev->getPlayer()->getName()]);
			$this->save();
		}
		if (isset($this->customerItem[$ev->getPlayer()->getName()])) {
			$ev->getPlayer()->sendMessage(self::$prefix . "입찰된 아이템을 드립니다.");
			foreach ($this->customerItem[$ev->getPlayer()->getName()] as $json) {
				$item = Item::jsonDeserialize($json);
				$ev->getPlayer()->getInventory()->addItem($item);
				$name = ItemInfo::getItemName($item->getId(), $item->getMeta());
				$ev->getPlayer()->sendMessage(self::$prefix . "{$name} {$item->getCount()}개를 받았습니다.");
			}
			unset($this->customerItem[$ev->getPlayer()->getName()]);
			$this->save();
		}
	}

}

