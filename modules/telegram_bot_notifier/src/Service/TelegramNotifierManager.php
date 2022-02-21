<?php

namespace Drupal\telegram_bot_notifier\Service;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\telegram_bot\Service\Telegram;
use Drupal\telegram_bot\Service\TelegramCommandsManager;
use Drupal\telegram_bot_notifier\Annotation\TelegramNotifier;
use Drupal\telegram_bot_notifier\TelegramNotifierInterface;

/**
 * Announces and process sending telegram notifications.
 *
 * @see \Drupal\telegram_bot_notifier\Annotation\TelegramNotifier
 * @see \Drupal\telegram_bot_notifier\TelegramNotifierInterface
 * @see \Drupal\telegram_bot_notifier\TelegramNotifierBase
 * @see plugin_api
 */
class TelegramNotifierManager extends DefaultPluginManager {

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Drupal\telegram_bot\Service\Telegram service.
   *
   * @var \Drupal\telegram_bot\Service\Telegram
   */
  protected $telegram;

  /**
   * Constructs a TelegramNotifierManager object.
   *
   * @param \Traversable $namespaces
   *   An object that implements \Traversable which contains the root paths
   *   keyed by the corresponding namespace to look for plugin implementations.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   Cache backend instance to use.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler to invoke the alter hook with.
   * @param \Drupal\telegram_bot\Service\Telegram $telegram
   *   Connection to the telegram API.
   * @param \Drupal\Core\Database\Connection $database
   *   Connection to the database.
   */
  public function __construct(\Traversable $namespaces,
                              CacheBackendInterface $cache_backend,
                              ModuleHandlerInterface $module_handler,
                              Telegram $telegram,
                              Connection $database) {
    parent::__construct(
      'Plugin/TelegramNotifier',
      $namespaces,
      $module_handler,
      TelegramNotifierInterface::class,
      TelegramNotifier::class
    );
    $this->alterInfo('telegram_notifier');
    $this->setCacheBackend($cache_backend, 'telegram_notifier_plugins');
    $this->database = $database;

    $this->telegram = $telegram;
  }

  /**
   * Send a notification.
   *
   * @var string $notifier
   *   Plugin id of the notifier plugin to use.
   * @var mixed $variables
   *   Variable(s) specific to that notifier.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function notify(string $notifier, $variables): void {
    if ($this->hasDefinition($notifier)) {
      $plugin = $this->createInstance($notifier, [
        'subscribers' => $this->getSubscribers($notifier),
        'variables' => $variables,
      ]);
      $plugin->execute();
    }
  }

  /**
   * Get chat id's of users subscribed to that notifier.
   *
   * @param string $notifier
   *   Plugin id of the notifier to get subscribers from.
   *
   * @return mixed
   *   Associative array of chat id's.
   */
  public function getSubscribers($notifier) {
    return $this->database->select('telegram_subscribers')
      ->fields('telegram_subscribers', ['chat_id'])
      ->condition('topic', $notifier)
      ->execute()
      ->fetchAll(\PDO::FETCH_ASSOC);
  }

  /**
   * Subscribe user to notifier.
   *
   * @var int $chat_id
   *   Chat id of the user to subscribe.
   * @var string $notifier
   *   Plugin id of the notifier plugin to use.
   *
   * @return string
   *   Result of subscribing.
   *   'noPermission' if user doesn't have permissions to be subscribed.
   *   'subscribed' if user already subscribed.
   *   'success' if user successfully subscribed.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function subscribeUserTo(int $chat_id, string $notifier): string {
    $plugin = $this->getDefinition($notifier);
    if (!TelegramCommandsManager::checkPermission($plugin, $chat_id)) {
      return 'noPermission';
    }
    try {
      $this->database->insert('telegram_subscribers')
        ->fields([
          'chat_id' => $chat_id,
          'topic' => $notifier,
        ])
        ->execute();
      return 'success';
    }
    catch (\Exception $e) {
      return 'subscribed';
    }

  }

}
