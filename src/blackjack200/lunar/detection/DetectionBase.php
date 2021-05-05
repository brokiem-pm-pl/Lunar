<?php
declare(strict_types=1);

namespace blackjack200\lunar\detection;


use blackjack200\lunar\configuration\DetectionConfiguration;
use blackjack200\lunar\configuration\Punishment;
use blackjack200\lunar\libs\CortexPE\DiscordWebhookAPI\Embed;
use blackjack200\lunar\libs\CortexPE\DiscordWebhookAPI\Message;
use blackjack200\lunar\Lunar;
use blackjack200\lunar\user\User;
use blackjack200\lunar\utils\Objects;
use brokiem\leafcore\api\LeafAPI;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\scheduler\ClosureTask;

abstract class DetectionBase implements Detection {
    protected float $preVL = 0;
    protected float $VL = 0;
	/** @var User */
	private $user;
	/** @var mixed */
	private $configuration;
	private string $name;
	private string $fmt;

	/**
     * @param DetectionConfiguration $data
     * @phpstan-ignore-next-line
     */
    public function __construct(User $user, string $name, string $fmt, ?string $webhookFmt, $data) {
        $this->user = $user;
        $this->name = $name;
        $this->fmt = $fmt;
        $this->configuration = $data;
    }

    /** @param numeric $VL */
    public function addVL($VL, ?string $message = null): void {
        $this->VL += $VL;
        if ($message !== null) {
            $this->alert($message);
        }
    }

    public function alert(string $message): void {
        foreach ($this->getUser()->getPlayer()->getServer()->getOnlinePlayers() as $onlinePlayer) {
            if ($onlinePlayer->hasPermission("lunar.alert.notify")) {
                $onlinePlayer->sendMessage(" §e[ALERT]§7 [{$this->user->getPlayer()->getName()}]: $this->name ($this->VL/{$this->getConfiguration()->getMaxVL()}) [$message]");
            }
        }
    }

	final protected function format(string $fmt, string $message, bool $prefix = true) : string {
        $cfg = $this->getConfiguration();
        return sprintf('%s%s', $prefix ? Lunar::getInstance()->getPrefix() . ' ' : '',
            Objects::replace($fmt, '[%s]', [
                    'MSG' => $message,
                    'DETECTION_NAME' => $this->name,
                    'PLAYER_NAME' => $this->user->getPlayer()->getName(),
                    'MAX_VL' => $cfg->getMaxVL(),
                    'VL' => $this->VL,
                    'PRE_VL' => $this->preVL,
                    'PUNISHMENT' => $cfg->getPunishment(),
                    'PUNISHMENT_STRING' => Punishment::toString($cfg->getPunishment())
                ]
            )
        );
    }

	final public function getConfiguration() : DetectionConfiguration { return $this->configuration; }

	public function fail(string $message) : void {
        if ((int)$this->VL === 2 or (int)$this->VL === 5 or (int)$this->VL === 15) {
            $embed = new Embed();
            $embed->setTitle("Anti-Cheat Alert");
            $desc = "Player: ``" . $this->getUser()->getPlayer()->getName() . "``\n";
            $desc .= "Violations: ``" . $this->VL . "``\n";
            $desc .= "Detection name: ``" . $this->name . "``\n";
            $desc .= "Alert message: ``" . $message . "``\n";

            $embed->setDescription($desc);
            $embed->setColor(0xFFFF00);
            $embed->setTimestamp(new \DateTime("now"));

            $msg = new Message();
            $msg->addEmbed($embed);

            if (($webhook = Lunar::getInstance()->getWebhook()) !== null) {
                $webhook->send($msg);
            }
        }

        foreach ($this->getUser()->getPlayer()->getServer()->getOnlinePlayers() as $onlinePlayer) {
            if ($onlinePlayer->hasPermission("lunar.alert.notify")) {
                $onlinePlayer->sendMessage(" §c[FAIL]§7 [{$this->user->getPlayer()->getName()}]: $this->name ($this->VL/{$this->getConfiguration()->getMaxVL()})");
            }
        }

        Lunar::getInstance()->getScheduler()->scheduleTask(new ClosureTask(function() use ($message): void {
            $this->failImpl($message);
        }));
    }

	final protected function failImpl(string $message) : void {
        switch ($this->getConfiguration()->getPunishment()) {
            case Punishment::BAN():
            case Punishment::KICK():
            case Punishment::WARN():
                $this->kick($message);
                break;
        }
    }

    public function log(string $message, string $code = "null"): void {
        $fmt = sprintf('[%s] Code: %s, Name: %s, Detection: %s, Message: %s', time(), $code, $this->getUser()->getPlayer()->getName(), $this->name, $message);
        Lunar::getInstance()->getLogger()->info($fmt);
        Lunar::getInstance()->getDetectionLogger()->write($fmt);
    }

    final public function getUser(): User { return $this->user; }

    public function generateCode(int $length = 5): string {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[mt_rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

	public function kick(string $message) : void {
        $code = $this->generateCode();
        $player = $this->getUser()->getPlayer();
        $type = $this->name;

        $this->log($message, $code);

        $embed = new Embed();
        $embed->setTitle("Anti-Cheat Punishment");
        $desc = "Player: ``" . $player->getName() . "``\n";
        $desc .= "Type: ``" . $type . "``\n";
        $desc .= "Code: ``" . $code . "``\n";
        $desc .= "Detection name: ``" . $this->name . "``\n";
        $desc .= "Message kicked: ``" . $this->format($this->fmt, $message, false) . "``\n";

        $embed->setDescription($desc);
        $embed->setColor(0xFF0000);
        $embed->setTimestamp(new \DateTime("now"));

        $msg = new Message();
        $msg->addEmbed($embed);

        if (($webhook = Lunar::getInstance()->getWebhook()) !== null) {
            $webhook->send($msg);
        }

        /** @phpstan-ignore-next-line */
        LeafAPI::kickPlayer($player->getName(), "0 " . Lunar::getInstance()->getPrefix() . "§l> §rKicked (code=" . $code . ")\nContact staff with a screenshot of this message if this issue persists");
    }

	public function alertTitle(string $message) : void {
		$this->getUser()->getPlayer()->sendTitle('§g', $this->format($this->fmt, $message), 2, 3, 5);
	}

	public function reset() : void {
		$this->VL = 0;
		$this->preVL = 0;
	}

	public function overflowVL() : bool {
		$cfg = $this->getConfiguration();
		return $cfg->hasMaxVL() && $this->VL >= $cfg->getMaxVL();
    }

    final public function getName(): string { return $this->name; }

    public function handleClient(DataPacket $packet): void { }

    public function handleServer(DataPacket $packet): void { }

    public function debug(string $message): void { }

    /**
     * @param mixed $data
     */
    public function check(...$data): void { }

    public function finalize(): void {

    }

    public function revertMovement(): void {
        if ($this->configuration->isSuppress()) {
            $user = $this->user;
            $pos = $user->getMovementInfo()->locationHistory->pop();
			if ($pos !== null) {
				$player = $user->getPlayer();
				$player->teleport($pos, $player->yaw);
			}
		}
	}
}