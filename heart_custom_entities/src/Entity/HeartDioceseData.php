<?php

declare(strict_types=1);

namespace Drupal\heart_custom_entities\Entity;

use CommerceGuys\Addressing\AddressFormat\AddressField;
use CommerceGuys\Addressing\AddressFormat\FieldOverride;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\heart_custom_entities\HeartDioceseDataInterface;
use Drupal\user\EntityOwnerTrait;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the heart diocese data entity class.
 *
 * @ContentEntityType(
 *   id = "heart_diocese_data",
 *   label = @Translation("Heart Diocese data"),
 *   label_collection = @Translation("Heart Diocese datas"),
 *   label_singular = @Translation("heart diocese data"),
 *   label_plural = @Translation("heart diocese datas"),
 *   label_count = @PluralTranslation(
 *     singular = "@count heart diocese datas",
 *     plural = "@count heart diocese datas",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\heart_custom_entities\HeartDioceseDataListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\heart_custom_entities\HeartDioceseDataAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\heart_custom_entities\Form\HeartDioceseDataForm",
 *       "edit" = "Drupal\heart_custom_entities\Form\HeartDioceseDataForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "heart_diocese_data",
 *   data_table = "heart_diocese_data_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer heart_diocese_data",
 *   entity_keys = {
 *     "id" = "id",
 *     "langcode" = "langcode",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/heart-diocese-data",
 *     "add-form" = "/heart-diocese-data/add",
 *     "canonical" = "/heart-diocese-data/{heart_diocese_data}",
 *     "edit-form" = "/heart-diocese-data/{heart_diocese_data}/edit",
 *     "delete-form" = "/heart-diocese-data/{heart_diocese_data}/delete",
 *     "delete-multiple-form" = "/admin/content/heart-diocese-data/delete-multiple",
 *   },
 *   field_ui_base_route = "entity.heart_diocese_data.settings",
 * )
 */
final class HeartDioceseData extends ContentEntityBase implements HeartDioceseDataInterface {

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
      ->setLabel(t('Diocese Name'))
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

    $fields['diocese_address'] = BaseFieldDefinition::create('address')
      ->setLabel(t('Address'))
      ->setDescription(t('Set Diocese address.'))
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setSetting('field_overrides', [
        AddressField::GIVEN_NAME => ['override' => FieldOverride::HIDDEN],
        AddressField::ADDITIONAL_NAME => ['override' => FieldOverride::HIDDEN],
        AddressField::FAMILY_NAME => ['override' => FieldOverride::HIDDEN],
        AddressField::ORGANIZATION => ['override' => FieldOverride::HIDDEN],
        AddressField::ADDRESS_LINE3 => ['override' => FieldOverride::HIDDEN],
      ])
      ->setDisplayOptions('form', [
        'type' => 'address_default',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setTranslatable(TRUE);

    $fields['diocese_website'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Diocese Website'))
      ->setSettings([
        'max_length' => 255,
        // Add other settings as needed.
      ])
      ->setCardinality(1)
      ->setSetting('display_label', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string',
        'weight' => 0,
        'settings' => [
          'display_label' => TRUE,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', FALSE);

      $fields['diocese_admins'] = BaseFieldDefinition::create('entity_reference')
      ->setTranslatable(TRUE)
      ->setLabel(t('Diocese admins'))
      ->setSetting('target_type', 'user_profile_data')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
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
      ->setDescription(t('The time that the heart diocese data was created.'))
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
      ->setDescription(t('The time that the heart diocese data was last edited.'));

    return $fields;
  }

}
