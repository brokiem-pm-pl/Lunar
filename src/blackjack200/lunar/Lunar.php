<?php

namespace blackjack200\lunar;

use blackjack200\lunar\command\DetectionListCommand;
use blackjack200\lunar\detection\combat\Slapper;
use blackjack200\lunar\libs\CortexPE\DiscordWebhookAPI\Webhook;
use blackjack200\lunar\listener\DefaultListener;
use blackjack200\lunar\task\ProcessorSecondTrigger;
use blackjack200\lunar\task\ProcessorTickTrigger;
use pocketmine\entity\Entity;
use pocketmine\plugin\PluginBase;
use Throwable;

class Lunar extends PluginBase {

    private static Lunar $instance;

    private string $prefix;

    private string $format;

    private DetectionLogger $detectionLogger;

    private ?string $webhookFormat = null;

    private ?Webhook $webhook;

    public static function getInstance(): Lunar { return self::$instance; }

    public function getDetectionLogger(): DetectionLogger { return $this->detectionLogger; }

    public function getPrefix(): string { return $this->prefix; }

    public function getFormat(): string { return $this->format; }

    public function getWebhookFormat(): ?string { return $this->webhookFormat; }

    public function onLoad(): void {
        if (is_file($this->getDataFolder() . "config.yml")) {
            @unlink($this->getDataFolder() . "config.yml");
        }

        self::$instance = $this;
    }

    public function onEnable(): void {
        $config = $this->getConfig();
        $this->saveResource('config.yml', $config->get('Replace'));
        $this->reloadConfig();

        $this->prefix = $config->get('Prefix', true);
        $this->format = $config->get('Format', true);

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

        $this->getServer()->getPluginManager()->registerEvents(new DefaultListener(), $this);

        $this->webhook = $this->getConfig()->get("webhook-url", null) !== null ? new Webhook($this->getConfig()->get("webhook-url")) : null;
    }

    public function onDisable(): void {
        $this->detectionLogger->shutdown();
    }

    public function getWebhook(): ?Webhook {
        return $this->webhook;
    }
}
