<?php


namespace blackjack200\lunar\configure;


class Punishment {
	private function __construct() {
	}

	public static function parsePunishment(string $dirty) : int {
		switch (mb_strtolower($dirty)) {
			case 'ban':
				return self::BAN();
			case 'kick':
				return self::KICK();
			case 'warn':
			case 'warning':
				return self::WARN();
			case 'ignore':
			case 'none':
				return self::IGNORE();
		}
		return self::KICK();
	}

	public static function BAN() : int {
		return 1;
	}

	public static function KICK() : int {
		return 0;
	}

	public static function WARN() : int {
		return 2;
	}

	public static function IGNORE() : int {
		return 3;
	}
}