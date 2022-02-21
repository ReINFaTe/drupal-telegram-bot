<?php

namespace Drupal\telegram_bot\Plugin\TelegramCommand;

use Drupal\telegram_bot\Entity\TelegramUser;
use Drupal\telegram_bot\TelegramCommandBase;

/**
 * Command to register telegram user.
 *
 * @TelegramCommand(
 *   id = "register",
 *   description = @Translation("Реєстрація"),
 * )
 */
class RegisterCommand extends TelegramCommandBase {

  /**
   * {@inheritdoc}
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function execute() : void {
    $chat = $this->getUpdate()->getChat();
    $tgUser = TelegramUser::create([
      'chat_id' => $chat->id,
      'username' => $chat->username,
      'first_name' => $chat->firstName,
      'last_name' => $chat->lastName,
    ]);
    $violations = $tgUser->validate();
    if (in_array('chat_id', $violations->getFieldNames(), TRUE)) {
      $this->replyWithMessage(['text' => 'Ви уже зареєстровані']);
    }
    elseif ($violations->count() > 0) {
      $this->replyWithMessage(['text' => 'Реєстрація невдала']);
    }
    else {
      $text = 'Реєстрація успішна' . PHP_EOL .
        'Зверніться до адміністратора для видачі дозволів';
      $this->replyWithMessage(['text' => $text]);
      $tgUser->save();
    }
  }

}
