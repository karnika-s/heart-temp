<?php

declare(strict_types=1);

namespace Drupal\heart_custom_entities\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\heart_custom_entities\HeartUserRefDataInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the heart user ref data entity class.
 *
 * @ContentEntityType(
 *   id = "heart_user_ref_data",
 *   label = @Translation("Heart User Ref Data"),
 *   label_collection = @Translation("Heart User Ref Datas"),
 *   label_singular = @Translation("heart user ref data"),
 *   label_plural = @Translation("heart user ref datas"),
 *   label_count = @PluralTranslation(
 *     singular = "@count heart user ref datas",
 *     plural = "@count heart user ref datas",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\heart_custom_entities\HeartUserRefDataListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\heart_custom_entities\Form\HeartUserRefDataForm",
 *       "edit" = "Drupal\heart_custom_entities\Form\HeartUserRefDataForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "heart_user_ref_data",
 *   admin_permission = "administer heart_user_ref_data",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "collection" = "/admin/content/heart-user-ref-data",
 *     "add-form" = "/heart-user-ref-data/add",
 *     "canonical" = "/heart-user-ref-data/{heart_user_ref_data}",
 *     "edit-form" = "/heart-user-ref-data/{heart_user_ref_data}/edit",
 *     "delete-form" = "/heart-user-ref-data/{heart_user_ref_data}/delete",
 *     "delete-multiple-form" = "/admin/content/heart-user-ref-data/delete-multiple",
 *   },
 *   field_ui_base_route = "entity.heart_user_ref_data.settings",
 * )
 */
final class HeartUserRefData extends ContentEntityBase implements HeartUserRefDataInterface {

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

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setTranslatable(TRUE)
      ->setLabel(t('User'))
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

      $fields['ref_entity_type'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity type of Reference Entity'))
      ->setDescription(t('Entity type of Reference Entity.'))
      ->setDisplayOptions('form', [
        'type' => 'string',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string_textfield',
        'label' => 'above',
        'weight' => 1,
      ])
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('view', TRUE);

      $fields['ref_entity_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Id of Reference Entity'))
      ->setDescription(t('Id of Reference Entity.'))
      ->setDisplayOptions('form', [
        'type' => 'string',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string_textfield',
        'label' => 'above',
        'weight' => 1,
      ])
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('view', TRUE);

      $fields['ref_entity_bundle'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Entity bundle of Reference Entity'))
      ->setDescription(t('Entity bundle of Reference Entity.'))
      ->setDisplayOptions('form', [
        'type' => 'string',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string_textfield',
        'label' => 'above',
        'weight' => 1,
      ])
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setTranslatable(TRUE)
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
      ->setDescription(t('The time that the heart user ref data was created.'))
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
      ->setDescription(t('The time that the heart user ref data was last edited.'));

    return $fields;
  }

}
