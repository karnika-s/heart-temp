<?php

declare(strict_types=1);

namespace Drupal\heart_custom_entities\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\heart_custom_entities\ZoomProfileDataInterface;

/**
 * Defines the zoom profile data entity class.
 *
 * @ContentEntityType(
 *   id = "zoom_profile_data",
 *   label = @Translation("Zoom Profile Data"),
 *   label_collection = @Translation("Zoom Profile Datas"),
 *   label_singular = @Translation("zoom profile data"),
 *   label_plural = @Translation("zoom profile datas"),
 *   label_count = @PluralTranslation(
 *     singular = "@count zoom profile datas",
 *     plural = "@count zoom profile datas",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\heart_custom_entities\ZoomProfileDataListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "translation" = "Drupal\content_translation\ContentTranslationHandler",
 *     "form" = {
 *       "add" = "Drupal\heart_custom_entities\Form\ZoomProfileDataForm",
 *       "edit" = "Drupal\heart_custom_entities\Form\ZoomProfileDataForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\heart_custom_entities\Routing\ZoomProfileDataHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "zoom_profile_data",
 *   data_table = "zoom_profile_data_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer zoom_profile_data",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "id",
 *     "uuid" = "uuid",
 *     "langcode" = "langcode",
 *   },
 *   links = {
 *     "collection" = "/admin/content/zoom-profile-data",
 *     "add-form" = "/zoom-profile-data/add",
 *     "canonical" = "/zoom-profile-data/{zoom_profile_data}",
 *     "edit-form" = "/zoom-profile-data/{zoom_profile_data}",
 *     "delete-form" = "/zoom-profile-data/{zoom_profile_data}/delete",
 *     "delete-multiple-form" = "/admin/content/zoom-profile-data/delete-multiple",
 *   },
 *   field_ui_base_route = "entity.zoom_profile_data.settings",
 * )
 */
final class ZoomProfileData extends ContentEntityBase implements ZoomProfileDataInterface {

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
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setTranslatable(TRUE);

    $fields['profile_image'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Profile Image'))
      ->setSettings([
        'file_extensions' => 'png jpg jpeg',
        'max_filesize' => '5MB',
      ])
      ->setDisplayOptions('form', [
        'type' => 'image_image',
        'weight' => 1,
        'settings' => [
          'progress_indicator' => 'throbber',
          'preview_image_style' => 'medium',
          'preview_image_width' => NULL,
          'preview_image_height' => NULL,
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'image',
        'label' => 'above',
        'weight' => 1,
        'settings' => [
          'image_style' => 'medium',
          'image_link' => '',
        ],
      ])
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['publisher'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Publisher'))
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

    $fields['department'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Department'))
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
      ->setDisplayConfigurable('view', TRUE);

    $fields['phone'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Phone'))
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

    $fields['language'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Language'))
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

    $fields['timezone'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Time Zone'))
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

    $fields['date_format'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Date Format'))
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

    $fields['time_format'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Time Format'))
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

    $fields['meeting_url'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Personal Meeting URL'))
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

    $fields['meeting_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Personal Meeting ID'))
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

    $fields['host_key'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Host Key'))
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

    $fields['sign_in_email'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Sign-In Email'))
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

    $fields['sign_in_password'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Sign-In Password'))
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

    $fields['authentication'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Two-Factor Authentication'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'label' => 'above',
        'weight' => 1,
      ])
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'));

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Status'))
      ->setDefaultValue(TRUE)
      ->setSetting('on_label', 'Enabled');

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the zoom profile data was last edited.'));

    return $fields;

  }

}
