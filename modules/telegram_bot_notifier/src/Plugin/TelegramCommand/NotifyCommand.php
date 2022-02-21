<?php

namespace Drupal\telegram_bot_notifier\Plugin\TelegramCommand;

use Drupal\telegram_bot\Service\Telegram;
use Drupal\telegram_bot\TelegramCommandBase;
use Drupal\telegram_bot_notifier\Service\TelegramNotifierManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Telegram\Bot\Keyboard\Keyboard;

/**
 * Subscribe to notifications.
 *
 * @TelegramCommand(
 *   id = "notify",
 *   description = @Translation("Повідомляти про оновлення"),
 * )
 */
class NotifyCommand extends TelegramCommandBase {

  private const
    WAITING_FOR_ANSWER = 2;

  /**
   * Telegram notifier.
   *
   * @var \Drupal\telegram_bot_notifier\Service\TelegramNotifierManager
   */
  protected $notifier;

  /**
   * {@inheritdoc}
   */
  public function __construct(Array $configuration,
                              Telegram $telegram,
                              TelegramNotifierManager $notifier) {
    parent::__construct($configuration, $telegram);
    $this->notifier = $notifier;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $container->get('telegram'),
      $container->get('telegram.notifier'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function execute(): void {
    switch ($this->configuration['state']) {
      case NULL:
        $this->ask();
        break;

      case self::WAITING_FOR_ANSWER:
        $this->subscribe();
        break;
    }
  }

  /**
   * Step 1. Ask user for the topic.
   */
  private function ask(): void {
    $notifiers = $this->notifier->getDefinitions();
    $keyboard = [];
    $text = 'На що ви хочете підписатися?' . PHP_EOL . PHP_EOL;
    if (empty($notifiers)) {
      $this->replyWithMessage([
        'text' => 'Немає доступних підписок',
      ]);
      return;
    }

    $i = 1;
    foreach ($notifiers as $notifier) {
      $keyboard[] = [(string) $i];
      $text .= sprintf('%s. <b>%s</b> - %s' . PHP_EOL, $i, $notifier['label'], $notifier['description']);
      $i++;
    }
    $reply_markup = new Keyboard([
      'keyboard' => $keyboard,
      'resize_keyboard' => TRUE,
      'one_time_keyboard' => TRUE,
    ]);
    $this->replyWithMessage([
      'text' => $text,
      'reply_markup' => $reply_markup,
      'parse_mode' => 'html',
    ]);
    $this->configuration['state'] = self::WAITING_FOR_ANSWER;
  }

  /**
   * Step 2. Subscribe user to topic.
   */
  private function subscribe(): void {
    $chat_id = $this->getUpdate()->getChat()->id;
    $answer = $this->getUpdate()->getMessage()->text;
    $notifiers = $this->notifier->getDefinitions();
    $answer = array_values($notifiers)[$answer - 1]['id'];
    $plugin = $this->notifier->getDefinition($answer);
    $text = '';
    if ($this->notifier->hasDefinition($answer)) {
      $result = $this->notifier->subscribeUserTo($chat_id, $answer);
      if ($result === 'success') {
        $text = "Ви тепер підписані на <b>\"{$plugin['label']}\"</b>";
      }
      elseif ($result === 'noPermission') {
        $text = "У вас немає прав щоб підписатися на  <b>\"{$plugin['label']}\"</b>";
      }
      elseif ($result === 'subscribed') {
        $text = "Ви уже підписані на  <b>\"{$plugin['label']}\"</b>";
      }
    }
    else {
      $text = 'Не існує такої підписки';
    }
    $this->replyWithMessage([
      'text' => $text,
      'reply_markup' => Keyboard::remove(),
      'parse_mode' => 'html',
    ]);

    $this->configuration['state'] = FALSE;
  }

}
