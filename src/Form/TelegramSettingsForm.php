<?php

namespace Drupal\telegram_bot\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Telegram\Bot\Exceptions\TelegramSDKException;

/**
 * Form for telegram bot token.
 */
class TelegramSettingsForm extends FormBase {

  /**
   * Drupal\Core\State\ definition.
   *
   * @var \Drupal\Core\State\State
   */
  protected $state;


  /**
   * Drupal\telegram_bot\Service\Telegram service.
   *
   * @var \Drupal\telegram_bot\Service\Telegram
   */
  protected $telegram;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): TelegramSettingsForm {
    $instance = parent::create($container);
    $instance->setStringTranslation($container->get('string_translation'));
    $instance->setMessenger($container->get('messenger'));
    $instance->state = $container->get('state');
    $instance->requestStack = $container->get('request_stack');
    $instance->telegram = $container->get('telegram');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'telegram_bot_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $token = $this->state->get('telegram_bot_token');
    $bot = $this->telegram->getMe();
    $status = $bot ? $this->t("Під'єднано") : $this->t('Недоступний');
    $form['status'] = [
      '#type' => 'details',
      '#title' => $this->t("Статус бота: %status", ['%status' => $status]),
      '#open' => FALSE,
    ];
    if ($bot) {
      $form['status']['info'] = [
        '#theme' => 'telegram_bot_info',
        '#id' => $bot->id,
        '#firstname' => $bot->firstName,
        '#lastname' => $bot->lastName,
        '#username' => $bot->username,
      ];
    }
    $form['token'] = [
      '#type' => 'textfield',
      '#title' => $this->t("Telegram bot token"),
      '#placeholder' => $this->t("Token"),
      '#required' => TRUE,
      '#default_value' => $token,
      '#attributes' => [
        'autocomplete' => 'off',
      ],
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t("Зберегти"),
      '#submit' => ['::submitForm'],
      '#button_type' => 'primary',
    ];

    $form['addition'] = [
      '#type' => 'details',
      '#title' => $this->t('Додатково'),
      '#open' => FALSE,
    ];

    $form['addition']['use_webhook'] = [
      '#type' => 'submit',
      '#value' => $this->t('Поставити webhook'),
      '#submit' => ['::setWebhook'],
      '#disabled' => !$token,
    ];

    $form['addition']['delete_webhook'] = [
      '#type' => 'submit',
      '#value' => $this->t('Видалити webhook'),
      '#submit' => ['::deleteWebhook'],
      '#disabled' => !$token,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $token = $form_state->getValue('token');
    $this->state->set('telegram_bot_token', $token);
    $this->messenger->addStatus('New bot token saved');
  }

  /**
   * Set the webhook.
   *
   * @see \Drupal\telegram_bot\Service\TelegramManager::webhookRoute()
   */
  public function setWebhook(array &$form, FormStateInterface $form_state): void {
    $token = $form_state->getValue('token');
    $host = $this->requestStack->getCurrentRequest()->getHost();
    try {
      $this->telegram->setWebhook([
        'url' => "https://$host/telegram/webhook/$token",
        'allowed_updates' => ['message', 'callback_query'],
      ]);
      $this->messenger->addStatus($this->t('Успішно'));
    }
    catch (TelegramSDKException $e) {
      $this->messenger->addError($e->getMessage());
    }
  }

  /**
   * Delete the webhook.
   */
  public function deleteWebhook(array &$form, FormStateInterface $form_state): void {
    try {
      $this->telegram->deleteWebhook();
      $this->messenger->addStatus($this->t('Успішно'));
    }
    catch (TelegramSDKException $e) {
      $this->messenger->addError($e->getMessage());
    }
  }

}
