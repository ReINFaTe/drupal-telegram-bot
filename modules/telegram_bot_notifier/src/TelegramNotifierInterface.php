<?php

namespace Drupal\telegram_bot_notifier;

/**
 * Interface for the telegram notifier plugin.
 */
interface TelegramNotifierInterface {

  /**
   * Execute the notifier.
   */
  public function execute() : void;

}
