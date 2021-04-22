<?php

namespace blackjack200\lunar;

use blackjack200\lunar\command\DetectionListCommand;
use blackjack200\lunar\detection\combat\Slapper;
use blackjack200\lunar\listener\DefaultListener;
use blackjack200\lunar\task\ProcessorSecondTrigger;
use blackjack200\lunar\task\ProcessorTickTrigger;
use libbot\BotFactory;
use libbot\BotInfo;
use pocketmine\entity\Entity;
use pocketmine\plugin\PluginBase;
use Throwable;

class Lunar extends PluginBase {
	/** @var self */
	private static $instance;
	/** @var string */
	private $prefix;
	/** @var string */
	private $format;
	/** @var DetectionLogger */
	private $detectionLogger;
	/** @var string */
	private $webHookFormat;

	public static function getInstance() : Lunar { return self::$instance; }

	public function getDetectionLogger() : DetectionLogger { return $this->detectionLogger; }

	public function getPrefix() : string { return $this->prefix; }

	public function getFormat() : string { return $this->format; }

	public function getWebHookFormat() : string { return $this->webHookFormat; }

	public function onEnable() : void {
		self::$instance = $this;
		if (version_compare('7.4.0', PHP_VERSION) > 0) {
			$this->getLogger()->error('Required PHP Version >= 7.4.0');
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return;
		}
		$this->getServer()->getPluginManager()->registerEvents(new DefaultListener(), $this);
		$this->saveResource('config.yml', $this->getConfig()->get('Replace'));
		$this->reloadConfig();
		$this->prefix = $this->getConfig()->get('Prefix', true);
		$this->format = $this->getConfig()->get('Format', true);
		$this->webHookFormat = $this->getConfig()->get('WebHookFormat');
		Entity::registerEntity(Slapper::class, true, ['lunar_slapper']);
		try {
			DetectionRegistry::initConfig();
		} catch (Throwable $e) {
			$this->getLogger()->logException($e);
			$this->getServer()->getPluginManager()->disablePlugin($this);
			return;
		}
		$this->getScheduler()->scheduleRepeatingTask(new ProcessorTickTrigger(), 1);
		$this->getScheduler()->scheduleRepeatingTask(new ProcessorSecondTrigger(), 20);

		$command = new DetectionListCommand();
		$this->getServer()->getCommandMap()->register($command->getName(), $command);
		$this->detectionLogger = new DetectionLogger($this->getDataFolder() . 'detections.log');
		$this->detectionLogger->start();

		$url = $this->getConfig()->get('Discord', true);
		if ($url !== '_') {
			$info = new BotInfo();
			$info->url = $url;
			GlobalBot::set(BotFactory::create('discord', $info));
			GlobalBot::start();
		}
	}

	public function onDisable() : void {
		$this->detectionLogger->shutdown();
		GlobalBot::set(null);
	}
}
