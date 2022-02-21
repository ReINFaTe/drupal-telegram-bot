<?php

namespace Drupal\telegram_bot\Service;

use Telegram\Bot\Objects\Message;
use Drupal\telegram_bot\Annotation\TelegramCommand as CommandAnnotation;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Plugin\DefaultPluginManager;
use Drupal\telegram_bot\Entity\TelegramUser;
use Drupal\telegram_bot\TelegramCommandInterface;
use Drupal\user\Entity\Role;
use Telegram\Bot\Objects\Update;

/**
 * Plugin manager for the telegram commands.
 *
 * @see \Drupal\telegram_bot\Annotation\TelegramCommand
 * @see \Drupal\telegram_bot\TelegramCommandInterface
 * @see \Drupal\telegram_bot\TelegramCommandBase
 * @see plugin_api
 */
class TelegramCommandsManager extends DefaultPluginManager {

  /**
   * Drupal\telegram_bot\Service\Telegram Service.
   *
   * @var \Drupal\telegram_bot\Service\Telegram
   */
  protected $telegram;

  /**
   * Service for storing and retrieving user states.
   *
   * @var \Drupal\telegram_bot\Service\TelegramUserStatus
   */
  protected $telegramStateStore;

  /**
   * Constructs a TelegramCommandsManager object.
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
   * @param \Drupal\telegram_bot\Service\TelegramUserStatus $telegram_state_store
   *   Service for storing and retrieving telegram user status.
   */
  public function __construct(
    \Traversable $namespaces,
    CacheBackendInterface $cache_backend,
    ModuleHandlerInterface $module_handler,
    Telegram $telegram,
    TelegramUserStatus $telegram_state_store
  ) {
    parent::__construct(
      'Plugin/TelegramCommand',
      $namespaces,
      $module_handler,
      TelegramCommandInterface::class,
      CommandAnnotation::class
    );
    $this->alterInfo('telegram_command');
    $this->setCacheBackend($cache_backend, 'telegram_commands_plugins');
    $this->telegram = $telegram;
    $this->telegramStateStore = $telegram_state_store;
  }

  /**
   * Process and execute commands or callback query in the update.
   *
   * @var \Telegram\Bot\Objects\Update $update
   *  Update object to process.
   *
   * @throws \Telegram\Bot\Exceptions\TelegramSDKException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   * @throws \JsonException
   */
  public function processCommand(Update $update): void {
    $message = $update->getMessage();
    $user_status = $this->telegramStateStore->getStatus($message->chat->id);
    if ($callback = $update->callbackQuery) {
      $data = json_decode($callback->data, TRUE, 512, JSON_THROW_ON_ERROR);
      if ($data['command']) {
        $data['callback'] = $callback;
        $this->executeCommand($data['command'], $update, $data);
      }
    }

    elseif ($command = $this->getCommand($message)) {
      $this->executeCommand($command, $update);
    }

    elseif ($user_status['command']) {
      $this->executeCommand($user_status['command'], $update, $user_status['state']);
    }
  }

  /**
   * Execute the command in the message.
   *
   * @var string $command
   *  Plugin id of the command to execute.
   * @var \Telegram\Bot\Objects\Update $update
   *  Update object to process.
   * @var mixed $state
   *  State of the user if any.
   *
   * @throws \Telegram\Bot\Exceptions\TelegramSDKException
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   */
  public function executeCommand(string $command, Update $update, $state = []): void {
    if ($this->hasDefinition($command)) {
      $plugin = $this->getDefinition($command);
      $chat_id = $update->getMessage()->chat->id;

      if (self::checkPermission($plugin, $chat_id)) {
        /** @var \Drupal\telegram_bot\TelegramCommandInterface $plugin_instance */
        $plugin_instance = $this->createInstance($command, [
          'update' => $update,
          'state' => $state,
        ]);
        $plugin_instance->execute();
        $updated_state = $plugin_instance->getConfiguration()['state'];
        if ($updated_state) {
          $updated_state = serialize($updated_state);
          $this->telegramStateStore->saveStatus($chat_id, $command, $updated_state);
        }
        else {
          $this->telegramStateStore->removeStatus($chat_id);
        }
      }

      else {
        $this->telegram->sendMessage([
          'text' => 'У вас немає дозволу на виконання цієї команди',
          'chat_id' => $update->getMessage()->chat->id,
        ]);
      }
    }

    else {
      $this->telegram->sendMessage([
        'text' => 'Команди не існує',
        'chat_id' => $update->getMessage()->chat->id,
      ]);
    }
  }

  /**
   * Check if this user has permission to this plugin.
   *
   * @var array $plugin
   *  Plugin definition.
   * @var int $chat_id
   *  User's telegram chat_id.
   *
   * @return bool
   *   TRUE if a user has a permission, FALSE otherwise.
   */
  public static function checkPermission(array $plugin, int $chat_id) : bool {
    if (!$plugin['permission']) {
      return TRUE;
    }

    $user = TelegramUser::loadByChatId($chat_id);
    if (!$user) {
      $anon = Role::load('anonymous_telegram_user');
      if (!$anon) {
        return FALSE;
      }
      return $anon->hasPermission($plugin['permission']);
    }

    return $user->hasPermission($plugin['permission']);
  }

  /**
   * Returns a string with the first command from the $message object.
   *
   * @var \Telegram\Bot\Objects\Message $message
   *  Message object to search in.
   *
   * @return string|bool
   *   A string containing command, FALSE if there isn't any command.
   */
  protected function getCommand(Message $message) {
    $entities = $message->entities;
    if (!$entities) {
      return FALSE;
    }
    foreach ($entities as $entity) {
      if ($entity['type'] === 'bot_command') {
        $text = $message->get('text');
        $command = substr($text, $entity['offset'] + 1, $entity['length']);
        return trim($command);
      }
    }
    return FALSE;
  }

}
