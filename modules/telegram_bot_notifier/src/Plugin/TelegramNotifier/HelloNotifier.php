<?php

namespace Drupal\telegram_bot_notifier\Plugin\TelegramNotifier;

use Drupal\telegram_bot_notifier\TelegramNotifierBase;

/**
 * Send all "Hello".
 *
 * @TelegramNotifier(
 *   id = "hello_notifier",
 *   label = "Привіт",
 *   description = "Надсилаю привіт",
 * )
 */
class HelloNotifier extends TelegramNotifierBase {

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    $this->sendAllMessage([
      'text' => 'Hello',
    ]);
  }

}
