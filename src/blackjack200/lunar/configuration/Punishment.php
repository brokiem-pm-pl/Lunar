<?php
declare(strict_types=1);

namespace blackjack200\lunar\configuration;


final class Punishment {
	private function __construct() { }

	public static function BAN() : int { return 2; }

	public static function KICK() : int { return 3; }

	public static function WARN() : int { return 4; }

	public static function IGNORE() : int { return 5; }

	public static function SUPPRESS() : int { return 1; }

	public static function toString(int $p) : string {
		switch ($p) {
			case self::BAN():
				return 'ban';
			case self::KICK():
				return 'kick';
			case self::WARN():
				return 'alert';
			case self::IGNORE():
				return 'ignore';
			case self::SUPPRESS():
				return 'suppress';
		}
		return 'unknown';
	}
}