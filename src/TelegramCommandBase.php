<?php

namespace Drupal\telegram_bot;

use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\telegram_bot\Service\Telegram;
use Illuminate\Support\Str;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Telegram\Bot\Api;
use Telegram\Bot\Objects\Update;

/**
 * Base class for the telegram commands.
 *
 * @method mixed replyWithMessage($use_sendMessage_parameters)       Reply Chat with a message. You can use all the sendMessage() parameters except chat_id.
 * @method mixed replyWithPhoto($use_sendPhoto_parameters)           Reply Chat with a Photo. You can use all the sendPhoto() parameters except chat_id.
 * @method mixed replyWithAudio($use_sendAudio_parameters)           Reply Chat with an Audio message. You can use all the sendAudio() parameters except chat_id.
 * @method mixed replyWithVideo($use_sendVideo_parameters)           Reply Chat with a Video. You can use all the sendVideo() parameters except chat_id.
 * @method mixed replyWithVoice($use_sendVoice_parameters)           Reply Chat with a Voice message. You can use all the sendVoice() parameters except chat_id.
 * @method mixed replyWithDocument($use_sendDocument_parameters)     Reply Chat with a Document. You can use all the sendDocument() parameters except chat_id.
 * @method mixed replyWithSticker($use_sendSticker_parameters)       Reply Chat with a Sticker. You can use all the sendSticker() parameters except chat_id.
 * @method mixed replyWithLocation($use_sendLocation_parameters)     Reply Chat with a Location. You can use all the sendLocation() parameters except chat_id.
 * @method mixed replyWithChatAction($use_sendChatAction_parameters) Reply Chat with a Chat Action. You can use all the sendChatAction() parameters except chat_id.
 */
abstract class TelegramCommandBase implements TelegramCommandInterface, ContainerFactoryPluginInterface {

  /**
   * Telegram api service.
   *
   * @var \Drupal\telegram_bot\Service\Telegram
   */
  protected $telegram;

  /**
   * Stores configuration for the command.
   *
   * @var array
   */
  protected $configuration;

  /**
   * Constructs command.
   *
   * @var array $configuration
   *  An associative array containing:
   *  - update: \Telegram\Bot\Objects\Update object.
   *  - state: array. State of the user if any.
   * @var \Drupal\telegram_bot\Service\Telegram $telegram
   *  Telegram service object to interact with the api.
   */
  public function __construct(Array $configuration, Telegram $telegram) {
    $this->configuration = $configuration;
    $this->update = $configuration['update'];
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
   * Holds an Update object.
   *
   * @var \Telegram\Bot\Objects\Update
   */
  protected $update;

  /**
   * Returns Update object.
   *
   * @return \Telegram\Bot\Objects\Update
   *   Update object.
   */
  public function getUpdate(): Update {
    return $this->update;
  }

  /**
   * {@inheritdoc}
   */
  public function getConfiguration(): array {
    return $this->configuration;
  }

  /**
   * Magic Method to handle all ReplyWith Methods.
   */
  public function __call($method, $arguments) {
    if (!Str::startsWith($method, 'replyWith')) {
      throw new \BadMethodCallException("Method [$method] does not exist.");
    }
    $reply_name = Str::studly(substr($method, 9));
    $methodName = 'send' . $reply_name;

    if (!method_exists(Api::class, $methodName)) {
      throw new \BadMethodCallException("Method [$method] does not exist.");
    }

    if (!$this->update->getChat()->has('id')) {
      throw new \BadMethodCallException("No chat available for reply with [$method].");
    }

    $params = array_merge(['chat_id' => $this->update->getChat()->id], $arguments[0]);

    return call_user_func([$this->telegram, $methodName], $params);
  }

}
