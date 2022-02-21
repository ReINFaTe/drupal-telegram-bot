<?php

namespace Drupal\telegram_bot\Service;

use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Session\AccountProxy;
use Drupal\Core\State\State;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Translation\TranslatorTrait;
use Telegram\Bot\Api;
use Telegram\Bot\Exceptions\TelegramSDKException;

/**
 * Adapter service to the telegram API.
 *
 * @mixin \Telegram\Bot\Api
 */
class Telegram {

  use StringTranslationTrait;

  /**
   * Telegram\Bot\Api object.
   *
   * @var \Telegram\Bot\Api
   */
  protected $api;

  /**
   * State system using a key value store.
   *
   * @var \Drupal\Core\State\State
   */
  protected $stateStore;

  /**
   * Drupal messenger Service.
   *
   * @var \Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountProxy
   */
  protected $currentUser;

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
    State $state,
    Messenger $messenger,
    AccountProxy $current_user,
    TranslationInterface $string_translation,
    LoggerInterface $logger
  ) {
    $this->stateStore = $state;
    $this->messenger = $messenger;
    $this->currentUser = $current_user;
    $this->setStringTranslation($string_translation);
    $this->logger = $logger;
  }

  /**
   * Return \Telegram\Bot\Api object.
   */
  public function getConnection(): bool {
    if (!isset($this->api)) {
      $token = $this->stateStore->get('telegram_bot_token');
      try {
        $this->api = new Api($token);
        $this->getMe();
        return TRUE;
      }
      catch (TelegramSDKException $e) {
        $this->logger->error($e->getMessage());
        return FALSE;
      }
    }
    return TRUE;
  }

  /**
   * Magic method to call \Telegram\Bot\Api methods.
   */
  public function __call($name, $arguments) {
    if ($arguments) {
      $arguments = $arguments[0];
    }
    if ($this->getConnection()) {
      if (method_exists($this->api, $name)) {
        try {
          return call_user_func([$this->api, $name], $arguments);
        }
        catch (\Exception $e) {
          $this->logger->error($e->getMessage());
          return FALSE;
        }
      }
      throw new \BadMethodCallException("Method [$name] does not exist.");
    }
  }

}
