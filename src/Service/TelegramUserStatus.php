<?php

namespace Drupal\telegram_bot\Service;

use Drupal\Core\Database\Connection;

/**
 * Service to get and save current status(used command, etc) into database.
 */
class TelegramUserStatus {

  /**
   * Database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs TelegramUserStatus object.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * Save user status.
   */
  public function saveStatus($chat_id, $command, $state): void {
    $this->database->merge('telegram_user_status')
      ->key('chat_id', $chat_id)
      ->fields([
        'command' => $command,
        'state' => $state,
      ])
      ->execute();
  }

  /**
   * Get a state for a given user.
   *
   * @param int $chat_id
   *   Chat id of the user to get state from.
   *
   * @return array|bool
   *   State of the user or FALSE.
   */
  public function getStatus(int $chat_id) {
    $status = $this->database->select('telegram_user_status')
      ->fields('telegram_user_status')
      ->condition('chat_id', $chat_id)
      ->execute()
      ->fetchAssoc();
    if ($status) {
      $status['state'] = unserialize($status['state']);
    }
    return $status;
  }

  /**
   * Removes a state for a given user.
   *
   * @param int $chat_id
   *   Chat id of the user to get state from.
   */
  public function removeStatus(int $chat_id) : void {
    $this->database->delete('telegram_user_status')
      ->condition('chat_id', $chat_id)
      ->execute();
  }

}
