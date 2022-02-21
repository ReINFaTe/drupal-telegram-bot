<?php

namespace Drupal\telegram_bot_notifier\Annotation;

use Drupal\Component\Annotation\Plugin;

/**
 * Defines an TelegramCommand annotation object.
 *
 * Plugin Namespace: Plugin\TelegramNotifier.
 *
 * @Annotation
 */
class TelegramNotifier extends Plugin {

  /**
   * The Notifier plugin ID.
   *
   * ID is used by users to call commands.
   *
   * @var string
   */
  public $id;

  /**
   * Label of the notifier.
   *
   * @var string
   */
  public $label;

  /**
   * A short description of the Notifier.
   *
   * @var \Drupal\Core\Annotation\Translation
   */
  public $description;

  /**
   * A permission that user should have to subscribe to this notifier.
   *
   * @var string
   */
  public $permission = '';

}
