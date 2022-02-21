<?php

namespace Drupal\telegram_bot_notifier;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\telegram_bot\Service\Telegram;
use Illuminate\Support\Str;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Telegram\Bot\Api;

/**
 * Base class to create telegram notifier plugin.
 *
 * @method mixed sendAllMessage($use_sendMessage_parameters)       Send all a message. You can use all the sendMessage() parameters except chat_id.
 * @method mixed sendAllPhoto($use_sendPhoto_parameters)           Send all a Photo. You can use all the sendPhoto() parameters except chat_id.
 * @method mixed sendAllAudio($use_sendAudio_parameters)           Send all an Audio message. You can use all the sendAudio() parameters except chat_id.
 * @method mixed sendAllVideo($use_sendVideo_parameters)           Send all a Video. You can use all the sendVideo() parameters except chat_id.
 * @method mixed sendAllVoice($use_sendVoice_parameters)           Send all a Voice message. You can use all the sendVoice() parameters except chat_id.
 * @method mixed sendAllDocument($use_sendDocument_parameters)     Send all a Document. You can use all the sendDocument() parameters except chat_id.
 * @method mixed sendAllSticker($use_sendSticker_parameters)       Send all a Sticker. You can use all the sendSticker() parameters except chat_id.
 * @method mixed sendAllLocation($use_sendLocation_parameters)     Send all a Location. You can use all the sendLocation() parameters except chat_id.
 * @method mixed sendAllChatAction($use_sendChatAction_parameters) Send all a Chat Action. You can use all the sendChatAction() parameters except chat_id.
 */
abstract class TelegramNotifierBase implements TelegramNotifierInterface, ContainerFactoryPluginInterface {

  /**
   * Telegram\Bot\Api object.
   *
   * @var \Drupal\telegram_bot\Service\Telegram
   */
  protected $telegram;

  /**
   * Stores configuration for the notifier.
   *
   * @var array
   */
  public $configuration;

  /**
   * Constructs command.
   */
  public function __construct(
    Array $configuration,
    Telegram $telegram
  ) {
    $this->configuration = $configuration;
    $this->telegram = $telegram;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(
    ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition
  ) {
    return new static(
      $configuration,
      $container->get('telegram'),
    );
  }

  /**
   * Returns an array of subscribers chat_id's.
   */
  public function getSubscribers() {
    return $this->configuration['subscribers'];
  }

  /**
   * Magic Method to handle all sendAll Methods.
   */
  public function __call($method, $arguments) {
    if (!Str::startsWith($method, 'sendAll')) {
      throw new \BadMethodCallException("Method [$method] does not exist.");
    }
    $reply_name = Str::studly(substr($method, 7));
    $methodName = 'send' . $reply_name;

    if (!method_exists(Api::class, $methodName)) {
      throw new \BadMethodCallException("Method [$method] does not exist.");
    }

    if (!$this->getSubscribers()) {
      return;
    }
    foreach ($this->getSubscribers() as $subscriber) {
      $params = array_merge(['chat_id' => $subscriber['chat_id']], $arguments[0]);
      $this->telegram->$methodName($params);
    }
  }

}
