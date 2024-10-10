<?php

declare(strict_types=1);

namespace Drupal\heart_custom_entities\Entity;

use CommerceGuys\Addressing\AddressFormat\AddressField;
use CommerceGuys\Addressing\AddressFormat\FieldOverride;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\heart_custom_entities\UserProfileDataInterface;

/**
 * Defines the user profile data entity class.
 *
 * @ContentEntityType(
 *   id = "user_profile_data",
 *   label = @Translation("User Profile Data"),
 *   label_collection = @Translation("User Profile Datas"),
 *   label_singular = @Translation("user profile data"),
 *   label_plural = @Translation("user profile datas"),
 *   label_count = @PluralTranslation(
 *     singular = "@count user profile datas",
 *     plural = "@count user profile datas",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\heart_custom_entities\UserProfileDataListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler",
 *     "form" = {
 *       "add" = "Drupal\heart_custom_entities\Form\UserProfileDataForm",
 *       "edit" = "Drupal\heart_custom_entities\Form\UserProfileDataForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\heart_custom_entities\Routing\UserProfileDataHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "user_profile_data",
 *   data_table = "user_profile_data_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer user_profile_data",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "collection" = "/admin/content/user-profile-data",
 *     "add-form" = "/user-profile-data/add",
 *     "canonical" = "/user-profile-data/{user_profile_data}",
 *     "edit-form" = "/user-profile-data/{user_profile_data}",
 *     "delete-form" = "/user-profile-data/{user_profile_data}/delete",
 *     "delete-multiple-form" = "/admin/content/user-profile-data/delete-multiple",
 *   },
 * )
 */
final class UserProfileData extends ContentEntityBase implements UserProfileDataInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_data'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User'))
      ->setDescription(t('Reference to a user entity.'))
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'entity_reference_label',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
      ])
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['first_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('First name'))
      ->setRequired(TRUE)
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

    $fields['last_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Last name'))
      ->setRequired(TRUE)
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

    $fields['phone'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Phone'))
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
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

    $fields['recovery_email_field'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Recovery Email Address'))
      ->setDescription(t('Backup Email Address.'))
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

    $fields['school_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('School/Church Name'))
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

    $fields['city'] = BaseFieldDefinition::create('string')
      ->setLabel(t('City'))
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

    $fields['job_title'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Job Title'))
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

    $fields['sub_role'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Sub Role'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler_settings', ['target_bundles' => ['sub_roles' => 'sub_roles']])
      ->setSetting('handler', 'default')
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDefaultValue(5)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'taxonomy_term_reference_plain',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_buttons',
        'weight' => 0,
      ])
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['user_diocese_field'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Diocese'))
      ->setDescription(t('Reference to a diocese entity.'))
      ->setSetting('target_type', 'heart_diocese_data')
      ->setSetting('handler', 'default')
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'entity_reference_label',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
      ])
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['user_parish_field'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Parish'))
      ->setDescription(t('Reference to a parish entity.'))
      ->setSetting('target_type', 'heart_parish_data')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'entity_reference_label',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 0,
      ])
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['user_profile_address'] = BaseFieldDefinition::create('address')
      ->setLabel(t('Address'))
      ->setDescription(t('Set user profile address.'))
      ->setCardinality(1)
      ->setRequired(TRUE)
      ->setSetting('field_overrides', [
        AddressField::GIVEN_NAME => ['override' => FieldOverride::HIDDEN],
        AddressField::ADDITIONAL_NAME => ['override' => FieldOverride::HIDDEN],
        AddressField::FAMILY_NAME => ['override' => FieldOverride::HIDDEN],
        AddressField::ORGANIZATION => ['override' => FieldOverride::HIDDEN],
        AddressField::ADDRESS_LINE3 => ['override' => FieldOverride::HIDDEN],
        AddressField::LOCALITY => ['override' => FieldOverride::HIDDEN],
      ])
      ->setDisplayOptions('form', [
        'type' => 'address_default',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setTranslatable(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'));

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', 'Enabled');

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the user profile data was last edited.'));

    return $fields;
  }

}
