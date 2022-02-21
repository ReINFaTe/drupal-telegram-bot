<?php

namespace Drupal\telegram_bot\Controller;

use Drupal\Core\Controller\ControllerBase;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Controller for the getUpdates loop and webhook endpoint.
 */
class Telegram extends ControllerBase {

  /**
   * Drupal\telegram_bot\Service\Telegram service.
   *
   * @var \Drupal\telegram_bot\Service\Telegram
   */
  protected $telegram;

  /**
   * Drupal\telegram_bot\Service\TelegramManager service.
   *
   * @var \Drupal\telegram_bot\Service\TelegramManager
   */
  protected $telegramManager;

  /**
   * Symfony\Component\HttpFoundation\RequestStack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): Telegram {
    $instance = parent::create($container);
    $instance->telegramManager = $container->get('telegram.manager');
    $instance->telegram = $container->get('telegram');
    $instance->request = $container->get('request_stack');
    return $instance;
  }

  /**
   * Get telegram bot updates with the getUpdates method and loop to itself.
   *
   * @throws \Telegram\Bot\Exceptions\TelegramSDKException
   */
  public function getUpdates(): Response {
    $this->telegram->getUpdates();
    $host = $this->request->getCurrentRequest()->getHost();
    $url = $host . '/telegram/getUpdates';
    $curl = curl_init();

    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_TIMEOUT, 1);
    curl_setopt($curl, CURLOPT_HEADER, 0);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, FALSE);
    curl_setopt($curl, CURLOPT_FORBID_REUSE, TRUE);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 1);
    curl_setopt($curl, CURLOPT_DNS_CACHE_TIMEOUT, 10);
    curl_setopt($curl, CURLOPT_FRESH_CONNECT, TRUE);

    curl_exec($curl);

    curl_close($curl);
    return new Response();
  }

  /**
   * Get telegram bot updates through webhook.
   */
  public function webhook(): Response {
    // Ip check not working correctly locally.
    // But only telegram should be able to send post to webhook.
    // @todo Implement ip check.
    // $request = $this->request;
    // $ip = $request->getCurrentRequest()->getClientIp();
    // If (IpUtils::checkIp($ip, ['149.154.160.0/20', '91.108.4.0/22'])) {
    // $this->telegram->getWebhookUpdate();
    // }
    $this->telegramManager->getWebhookUpdate();
    return new Response();
  }

}
