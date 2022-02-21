<?php

namespace Drupal\telegram_bot\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;

/**
 * Defines TelegramUser entity.
 *
 * @ContentEntityType(
 *   id = "telegram_user",
 *   label = @Translation("Telegram user"),
 *   base_table = "telegram_users",
 *   admin_permission = "administer telegram_users",
 *   entity_keys = {
 *    "id" = "id",
 *    "label" = "username",
 *   },
 *   handlers = {
 *     "list_builder" = "Drupal\telegram_bot\TelegramUserListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider",
 *     },
 *     "form" = {
 *       "default" = "Drupal\telegram_bot\Form\TelegramUserForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm"
 *      },
 *   },
 *   links = {
 *     "canonical" = "/admin/people/telegram/users/{telegram_user}",
 *     "edit-form" = "/admin/people/telegram/users/{telegram_user}/edit",
 *     "delete-form" = "/admin/people/telegram/users/{telegram_user}/delete",
 *     "collection" = "/admin/people/telegram/users",
 *   },
 * )
 */
class TelegramUser extends ContentEntityBase implements TelegramUserInterface {

  /**
   * Loads Telegram user entity by chat id.
   *
   * @return \Drupal\telegram_bot\Entity\TelegramUserInterface|bool
   *   Telegram user entity or false if user doesn't exist.
   */
  public static function loadByChatId(int $chat_id) {
    $entity_type_repository = \Drupal::service('entity_type.repository');
    $entity_type_manager = \Drupal::entityTypeManager();
    $storage = $entity_type_manager->getStorage($entity_type_repository->getEntityTypeFromClass(static::class));
    $test = $storage->loadByProperties([
      'chat_id' => $chat_id,
    ]);
    return reset($test);
  }

  /**
   * {@inheritdoc}
   */
  public function getChatId(): int {
    return $this->get('chat_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getUsername(): string {
    return $this->get('username')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getFirstName(): string {
    return $this->get('first_name')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getLastName(): string {
    return $this->get('last_name')->value ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function getRoles(): array {
    $roles = [];

    foreach ($this->get('roles') as $role) {
      if ($role->target_id) {
        $roles[] = $role->target_id;
      }
    }

    return $roles;
  }

  /**
   * {@inheritdoc}
   */
  public function hasPermission(string $permission): bool {
    return $this->getRoleStorage()->isPermissionInRoles($permission, $this->getRoles());
  }

  /**
   * Returns the role storage object.
   *
   * @return \Drupal\user\RoleStorageInterface
   *   The role storage object.
   */
  protected function getRoleStorage() {
    return \Drupal::entityTypeManager()->getStorage('user_role');
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // @todo find a way to use bigint as an entity id.
    // https://www.drupal.org/project/drupal/issues/2680571
    $fields['chat_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Telegram User ID'))
      ->setSettings([
        'size' => 'big',
      ])
      ->addConstraint('UniqueField');

    $fields['roles'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Roles'))
      ->setCardinality(BaseFieldDefinition::CARDINALITY_UNLIMITED)
      ->setDefaultValue('anonymous_telegram_user')
      ->setDescription(t('The roles the user has.'))
      ->setSetting('target_type', 'user_role');

    $fields['username'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Username'));

    $fields['first_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('First name'));

    $fields['last_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Last name'));

    return $fields;
  }

}
