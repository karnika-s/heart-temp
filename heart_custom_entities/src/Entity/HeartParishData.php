<?php

declare(strict_types=1);

namespace Drupal\heart_custom_entities\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\heart_custom_entities\HeartParishDataInterface;
use Drupal\user\EntityOwnerTrait;
use CommerceGuys\Addressing\AddressFormat\AddressField;
use CommerceGuys\Addressing\AddressFormat\FieldOverride;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the heart parish data entity class.
 *
 * @ContentEntityType(
 *   id = "heart_parish_data",
 *   label = @Translation("Heart Parish Data"),
 *   label_collection = @Translation("Heart Parish Datas"),
 *   label_singular = @Translation("heart parish data"),
 *   label_plural = @Translation("heart parish datas"),
 *   label_count = @PluralTranslation(
 *     singular = "@count heart parish datas",
 *     plural = "@count heart parish datas",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\heart_custom_entities\HeartParishDataListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\heart_custom_entities\Form\HeartParishDataForm",
 *       "edit" = "Drupal\heart_custom_entities\Form\HeartParishDataForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\heart_custom_entities\Routing\HeartParishDataHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "heart_parish_data",
 *   data_table = "heart_parish_data_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer heart_parish_data",
 *   entity_keys = {
 *     "id" = "id",
 *     "langcode" = "langcode",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/heart-parish-data",
 *     "add-form" = "/heart-parish-data/add",
 *     "canonical" = "/heart-parish-data/{heart_parish_data}",
 *     "edit-form" = "/heart-parish-data/{heart_parish_data}",
 *     "delete-form" = "/heart-parish-data/{heart_parish_data}/delete",
 *     "delete-multiple-form" = "/admin/content/heart-parish-data/delete-multiple",
 *   },
 * )
 */
final class HeartParishData extends ContentEntityBase implements HeartParishDataInterface {

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
      ->setLabel(t('Parish Name'))
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

    $fields['diocese_field'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Diocese'))
      ->setDescription(t('Reference to a diocese entity.'))
      ->setSetting('target_type', 'heart_diocese_data')
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

    $fields['parish_address'] = BaseFieldDefinition::create('address')
      ->setLabel(t('Address'))
      ->setDescription(t('Set Parish address.'))
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

      $fields['parish_admins'] = BaseFieldDefinition::create('entity_reference')
      ->setTranslatable(TRUE)
      ->setLabel(t('Parish admins'))
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
      ->setDescription(t('The time that the heart parish data was created.'))
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
      ->setDescription(t('The time that the heart parish data was last edited.'));

    return $fields;
  }

}
