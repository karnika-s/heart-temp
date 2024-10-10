<?php

declare(strict_types=1);

namespace Drupal\heart_custom_entities\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\heart_custom_entities\ClassInvitationInterface;
use Drupal\user\EntityOwnerTrait;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the class invitation entity class.
 *
 * @ContentEntityType(
 *   id = "class_invitation",
 *   label = @Translation("Class Invitation"),
 *   label_collection = @Translation("Class Invitations"),
 *   label_singular = @Translation("class invitation"),
 *   label_plural = @Translation("class invitations"),
 *   label_count = @PluralTranslation(
 *     singular = "@count class invitations",
 *     plural = "@count class invitations",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\heart_custom_entities\ClassInvitationListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\heart_custom_entities\Form\ClassInvitationForm",
 *       "edit" = "Drupal\heart_custom_entities\Form\ClassInvitationForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\heart_custom_entities\Routing\ClassInvitationHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "class_invitation",
 *   data_table = "class_invitation_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer class_invitation",
 *   entity_keys = {
 *     "id" = "id",
 *     "langcode" = "langcode",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/class-invitation",
 *     "add-form" = "/class-invitation/add",
 *     "canonical" = "/class-invitation/{class_invitation}",
 *     "edit-form" = "/class-invitation/{class_invitation}",
 *     "delete-form" = "/class-invitation/{class_invitation}/delete",
 *     "delete-multiple-form" = "/admin/content/class-invitation/delete-multiple",
 *   },
 * )
 */
final class ClassInvitation extends ContentEntityBase implements ClassInvitationInterface {

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void {
    parent::preSave($storage);
    if (!$this->getOwnerId()) {
      // If no owner has been set explicitly, make the anonymous user the owner.
      $this->setOwnerId(0);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setTranslatable(TRUE)
      ->setLabel(t('Label'))
      ->setRequired(TRUE)
      ->setSetting('max_length', 255)
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'string',
        'weight' => -5,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['class_reference'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Class Reference'))
      ->setDescription(t('Reference to a heart class entity.'))
      ->setSetting('target_type', 'heart_class')
      ->setSetting('handler', 'default')
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'entity_reference_label',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => -5,
      ])
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['invitation_type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Invitation Type'))
      ->setRequired(TRUE)
      ->setSettings([
        'allowed_values' => [
          'student' => 'Student',
          'teacher' => 'Teacher',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'list_default',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_buttons',
        'weight' => -5,
      ])
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['invited_user'] = BaseFieldDefinition::create('entity_reference')
      ->setTranslatable(TRUE)
      ->setLabel(t('Invited user'))
      ->setSetting('target_type', 'user_profile_data')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => -5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['invitation_status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Invitation Status'))
      ->setRequired(TRUE)
      ->setSettings([
        'allowed_values' => [
          'accepted' => 'Accepted',
          'pending' => 'Pending',
          'rejected' => 'Rejected',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'list_default',
        'weight' => -5,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_buttons',
        'weight' => -5,
      ])
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);


    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', 'Enabled')
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'settings' => [
          'display_label' => FALSE,
        ],
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'label' => 'above',
        'weight' => 0,
        'settings' => [
          'format' => 'enabled-disabled',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setTranslatable(TRUE)
      ->setLabel(t('Author'))
      ->setSetting('target_type', 'user')
      ->setDefaultValueCallback(self::class . '::getDefaultEntityOwner')
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => 60,
          'placeholder' => '',
        ],
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the class invitation was created.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 20,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the class invitation was last edited.'));

    return $fields;
  }

}
