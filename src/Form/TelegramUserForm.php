<?php

namespace Drupal\telegram_bot\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\RoleInterface;

/**
 * Form for telegram user entity.
 */
class TelegramUserForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    $roles = array_map(['\Drupal\Component\Utility\Html', 'escape'], user_role_names(TRUE));
    $current_roles = $this->entity->getRoles();
    $form['roles'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Roles'),
      '#default_value' => $current_roles,
      '#options' => $roles,
    ];
    $form['roles'][RoleInterface::AUTHENTICATED_ID] = [
      '#access' => FALSE,
    ];
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function buildEntity(array $form, FormStateInterface $form_state) {
    if (is_string(key($form_state->getValue('roles')))) {
      $form_state->setValue('roles', array_keys(array_filter($form_state->getValue('roles'))));
    }
    return parent::buildEntity($form, $form_state);
  }

}
