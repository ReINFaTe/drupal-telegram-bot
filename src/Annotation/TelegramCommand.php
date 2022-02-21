<?php

namespace Drupal\telegram_bot\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an TelegramCommand annotation object.
 *
 * Plugin Namespace: Plugin\TelegramCommand.
 *
 * @Annotation
 */
class TelegramCommand extends Plugin {

  /**
   * The Command plugin ID.
   *
   * ID is used by users to call commands.
   *
   * @var string
   */
  public $id;

  /**
   * A short description of the command.
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;

  /**
   * A permission that user should have to use this command.
   *
   * @var string
   */
  public $permission = '';

}
