<?php

namespace Drupal\telegram_bot\Entity;

/**
 * Interface for the Telegram user entity.
 */
interface TelegramUserInterface {

  public const DEFAULT_ROLE = 'anonymous_telegram_user';

  /**
   * Returns the user ID.
   *
   * @return int
   *   The user ID.
   */
  public function id();

  /**
   * Returns the user ID.
   *
   * @return int
   *   The user ID.
   */
  public function getChatId() : int;

  /**
   * Returns the username or empty string if user doesn't have one.
   *
   * @return string
   *   The username.
   */
  public function getUsername() : string;

  /**
   * Returns the first name or empty string if user doesn't have one.
   *
   * @return string
   *   The username.
   */
  public function getFirstName() : string;

  /**
   * Returns the last name or empty string if user doesn't have one.
   *
   * @return string
   *   The username.
   */
  public function getLastName() : string;

  /**
   * Returns a list of roles.
   *
   * @return array
   *   List of role IDs.
   */
  public function getRoles(): array;

  /**
   * Checks whether a user has a certain permission.
   *
   * @param string $permission
   *   The permission string to check.
   *
   * @return bool
   *   TRUE if the user has the permission, FALSE otherwise.
   */
  public function hasPermission(string $permission): bool;

}
