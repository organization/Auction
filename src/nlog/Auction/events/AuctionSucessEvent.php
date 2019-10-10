<?php

namespace nlog\Auction\events;

use pocketmine\event\Event;
use nlog\Auction\Auction;
use pocketmine\item\Item;
use pocketmine\Player;

/**
 * @deprecated
 * @author NLOG
 *
 */
class AuctionSucessEvent extends Event{
	
	/** @var Auction */
	private $owner;
	
	/** @var Item */
	private $item;
	
	/** @var Player */
	private $seller;
	
	/** @var Player */
	private $customer;
	
	/** @var int */
	private $price;
	
	public function __construct(Auction $owner, Item $item, Player $seller, Player $customer, int $price) {
		$this->owner = $owner;
		$this->item = clone $item;
		$this->seller = $seller;
		$this->customer = $customer;
		$this->price = $price;
	}
	
	public function getPlugin(): Auction{
		return $this->owner;
	}
	
	public function getItem(): Item{
		return $this->item;
	}
	
	public function getSeller(): Player{
		return $this->seller;
	}
	
	public function getCustomer(): Player{
		return $this->customer;
	}
	
	public function getPrice(): int{
		return $this->price;
	}
	
}