<?php

namespace Drupal\telegram_bot;

/**
 * Interface for the telegramCommand plugin.
 *
 * Execute() is called when user sends to a bot message "/{command_id}" or
 * Callback query received with "command" => {command_id}.
 *
 * If you need to store state between requests, you can write array to
 * $this->configuration['state'], if the array isn't empty when the next
 * message from this user would be received that doesn't include
 * "/{command_id}" the previous command will be called.
 *
 * @see \Drupal\telegram_bot\Annotation\TelegramCommand
 * @see \Drupal\telegram_bot\TelegramCommandBase
 */
interface TelegramCommandInterface {

  /**
   * Execute the command.
   */
  public function execute() : void;

  /**
   * Configuration array.
   */
  public function getConfiguration() : array;

}
