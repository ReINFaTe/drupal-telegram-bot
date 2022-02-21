<?php

namespace Drupal\telegram_bot\Plugin\TelegramCommand;

use Drupal\telegram_bot\Service\Telegram;
use Drupal\telegram_bot\Service\TelegramCommandsManager;
use Drupal\telegram_bot\TelegramCommandBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Telegram\Bot\Keyboard\Keyboard;

/**
 * Sends a list of all available commands.
 *
 * @TelegramCommand(
 *   id = "start",
 *   description = @Translation("Список доступних команд"),
 * )
 */
class StartCommand extends TelegramCommandBase {

  /**
   * Telegram commands manager.
   *
   * @var \Drupal\telegram_bot\Service\TelegramCommandsManager
   */
  protected $commandsManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(Array $configuration,
                              Telegram $telegram,
                              TelegramCommandsManager $commands_manager) {
    parent::__construct($configuration, $telegram);
    $this->commandsManager = $commands_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $container->get('telegram'),
      $container->get('telegram.commands.manager'),
    );
  }

  /**
   * {@inheritdoc}
   *
   * @throws \Telegram\Bot\Exceptions\TelegramSDKException
   */
  public function execute() : void {
    $commands = $this->commandsManager->getDefinitions();
    $chat_id = $this->configuration['update']->getMessage()->chat->id;
    $commands = array_filter($commands, static function ($plugin) use ($chat_id) {
      return TelegramCommandsManager::checkPermission($plugin, $chat_id);
    });

    $text = 'Commands' . PHP_EOL;
    $commands_object = [];
    foreach ($commands as $command) {
      $text .= sprintf('/%s - %s' . PHP_EOL, $command['id'], $command['description']);
      $commands_object[] = [
        'command' => $command['id'],
        'description' => $command['description'],
      ];
    }

    $this->telegram->setMyCommands([
      'commands' => $commands_object,
      'scope' => [
        'type' => 'chat',
        'chat_id' => $chat_id,
      ],
    ]);
    $this->replyWithMessage([
      'text' => $text,
      'reply_markup' => Keyboard::remove(),
    ]);
  }

}
