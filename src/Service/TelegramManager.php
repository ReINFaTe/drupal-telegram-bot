<?php

namespace Drupal\telegram_bot\Service;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\State\State;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Route;
use Telegram\Bot\Exceptions\TelegramSDKException;

/**
 * Control incoming updates from the telegram.
 */
class TelegramManager {

  /**
   * Drupal\telegram_bot\Service\Telegram service.
   *
   * @var \Drupal\telegram_bot\Service\Telegram
   */
  protected $telegram;

  /**
   * Telegram commands manager.
   *
   * @var \Drupal\telegram_bot\Service\TelegramCommandsManager
   */
  protected $commandsManager;

  /**
   * State system using a key value store.
   *
   * @var \Drupal\Core\State\State
   */
  protected $stateStore;

  /**
   * Logger channel "telegram_bot".
   *
   * @var \Psr\Log\LoggerInterface
   */
  private $logger;

  /**
   * Constructs Telegram object.
   */
  public function __construct(
    Telegram $telegram,
    TelegramCommandsManager $commands_manager,
    State $state,
    LoggerInterface $logger
  ) {
    $this->telegram = $telegram;
    $this->commandsManager = $commands_manager;
    $this->stateStore = $state;
    $this->logger = $logger;
  }

  /**
   * Get and process new updates from the telegram through getUpdates method.
   *
   * @link https://core.telegram.org/bots/api#getupdates.
   *
   * @throws \Telegram\Bot\Exceptions\TelegramSDKException
   */
  public function getUpdates(): void {
    $response = $this->telegram->getUpdates(['timeout' => 60]);
    if ($response) {
      /** @var \Telegram\Bot\Objects\Update $update */
      $highestId = -1;
      foreach ($response as $update) {
        try {
          $this->commandsManager->processCommand($update);
        }
        catch (PluginException | \JsonException | TelegramSDKException $e) {
          $this->logger->error($e->getMessage());
        }
        $highestId = $update->updateId;
      }
      if ($highestId !== -1) {
        $this->telegram->markUpdateAsRead($highestId);
      }
    }
  }

  /**
   * Get and process new updates from the telegram API through webhook.
   */
  public function getWebhookUpdate(): void {
    $response = $this->telegram->getWebhookUpdate();
    if ($response) {
      try {
        $this->commandsManager->processCommand($response);
      }
      catch (PluginException | \JsonException | TelegramSDKException $e) {
        $this->logger->error($e->getMessage());
      }
    }
  }

  /**
   * Register webhook drupal route.
   *
   * @see \Drupal\telegram_bot\Form\TelegramSettingsForm::setWebhook()
   */
  public function webhookRoute(): array {
    $routes = [];
    $token = $this->stateStore->get('telegram_bot_token');
    $routes['telegram.webhook'] = new Route(
      '/telegram/webhook/' . $token,
      [
        '_controller' => '\Drupal\telegram_bot\Controller\Telegram:webhook',
      ],
      [
        '_permission'  => 'access content',
      ],
      [],
      NULL,
      [],
      [
        'POST',
      ],
    );
    return $routes;
  }

}
