<?php

namespace Drupal\telegram_bot;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of telegram user entities.
 */
class TelegramUserListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header = [
      'username' => [
        'data' => $this->t('Username'),
        'field' => 'name',
        'specifier' => 'name',
      ],
      'first_name' => [
        'data' => $this->t('First name'),
        'field' => 'first_name',
        'specifier' => 'first_name',
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'last_name' => [
        'data' => $this->t('Last name'),
        'field' => 'first_name',
        'specifier' => 'first_name',
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'roles' => [
        'data' => $this->t('Roles'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
      'chat_id' => [
        'data' => $this->t('Chat ID'),
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ],
    ];
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /** @var \Drupal\telegram_bot\Entity\TelegramUserInterface $entity */
    if ($entity->getUsername() !== '') {
      // Link to open a user telegram profile.
      $url = Url::fromUri('https://t.me/' . $entity->getUsername());
      $row['username']['data'] = [
        '#type' => 'link',
        '#title' => $entity->getUsername(),
        '#url' => $url,
        '#attributes' => [
          'target' => '_blank',
        ],
      ];
    }
    else {
      $row['username'] = '';
    }
    $row['first_name'] = $entity->getFirstName();
    $row['last_name'] = $entity->getLastName();
    $row['roles']['data'] = [
      '#theme' => 'item_list',
      '#items' => $entity->getRoles(),
    ];
    $row['chat_id'] = $entity->getChatId();
    return $row + parent::buildRow($entity);
  }

}
