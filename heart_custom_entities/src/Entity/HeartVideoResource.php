<?php

declare(strict_types=1);

namespace Drupal\heart_custom_entities\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\heart_custom_entities\HeartVideoResourceInterface;
use Drupal\user\EntityOwnerTrait;

/**
 * Defines the heart video resource entity class.
 *
 * @ContentEntityType(
 *   id = "heart_video_resource",
 *   label = @Translation("Heart video resource"),
 *   label_collection = @Translation("Heart video resources"),
 *   label_singular = @Translation("heart video resource"),
 *   label_plural = @Translation("heart video resources"),
 *   label_count = @PluralTranslation(
 *     singular = "@count heart video resources",
 *     plural = "@count heart video resources",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\heart_custom_entities\HeartVideoResourceListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "access" = "Drupal\heart_custom_entities\HeartVideoResourceAccessControlHandler",
 *     "form" = {
 *       "add" = "Drupal\heart_custom_entities\Form\HeartVideoResourceForm",
 *       "edit" = "Drupal\heart_custom_entities\Form\HeartVideoResourceForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "heart_video_resource",
 *   data_table = "heart_video_resource_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer heart_video_resource",
 *   entity_keys = {
 *     "id" = "id",
 *     "langcode" = "langcode",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *     "owner" = "uid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/heart-video-resource",
 *     "add-form" = "/heart-video-resource/add",
 *     "canonical" = "/heart-video-resource/{heart_video_resource}",
 *     "edit-form" = "/heart-video-resource/{heart_video_resource}/edit",
 *     "delete-form" = "/heart-video-resource/{heart_video_resource}/delete",
 *     "delete-multiple-form" = "/admin/content/heart-video-resource/delete-multiple",
 *   },
 *   field_ui_base_route = "entity.heart_video_resource.settings",
 * )
 */
final class HeartVideoResource extends ContentEntityBase implements HeartVideoResourceInterface
{

  use EntityChangedTrait;
  use EntityOwnerTrait;

  /**
   * {@inheritdoc}
   */
  public function preSave(EntityStorageInterface $storage): void
  {
    parent::preSave($storage);
    if (!$this->getOwnerId()) {
      // If no owner has been set explicitly, make the anonymous user the owner.
      $this->setOwnerId(0);
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
  {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['video_src_url'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Video Source URL'))
      ->setTranslatable(TRUE)
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string',
        'label' => 'above',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['label'] = BaseFieldDefinition::create('string')
      ->setTranslatable(TRUE)
      ->setLabel(t('Title of Video'))
      ->setDisplayOptions('form', [
        'type' => 'string',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string_textfield',
        'label' => 'above',
        'weight' => 2,
      ])
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['series_number'] = BaseFieldDefinition::create('string')
      ->setTranslatable(TRUE)
      ->setLabel(t('Series Number'))
      ->setDisplayOptions('form', [
        'type' => 'string',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string_textfield',
        'label' => 'above',
        'weight' => 3,
      ])
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['presenters'] = BaseFieldDefinition::create('string')
      ->setTranslatable(TRUE)
      ->setLabel(t('Presenter(s)'))
      ->setDisplayOptions('form', [
        'type' => 'string',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string_textfield',
        'label' => 'above',
        'weight' => 4,
      ])
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setTranslatable(TRUE)
      ->setLabel(t('Description/Overview'))
      ->setDisplayOptions('form', [
        'type' => 'textarea',
        'weight' => 6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['item_cost'] = BaseFieldDefinition::create('list_string')
      ->setTranslatable(TRUE)
      ->setLabel(t('Item Cost'))
      ->setSetting('allowed_values', [
        'complimentary' => 'Complimentary',
        'priced' => 'Sells for a Price',
      ])
      // Make the field required.
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_buttons',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['video_price'] = BaseFieldDefinition::create('string')
      ->setTranslatable(TRUE)
      ->setLabel(t('Price'))
      ->setDisplayOptions('form', [
        'type' => 'string',
        'weight' => 7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string_textfield',
        'label' => 'above',
        'weight' => 7,
      ])
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['nav_item_number'] = BaseFieldDefinition::create('string')
      ->setTranslatable(TRUE)
      ->setLabel(t('Nav Item Number'))
      ->setDisplayOptions('form', [
        'type' => 'string',
        'weight' => 8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string_textfield',
        'label' => 'above',
        'weight' => 8,
      ])
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['replace_thumbnail'] = BaseFieldDefinition::create('image')
      ->setTranslatable(TRUE)
      ->setLabel(t('Replace Thumbnail'))
      ->setSettings([
        'file_extensions' => 'png jpg jpeg',
        'max_filesize' => '50MB',
      ])
      ->setDisplayOptions('form', [
        'type' => 'image_image',
        'weight' => 10,
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
        'weight' => 10,
        'settings' => [
          'image_style' => 'medium',
          'image_link' => '',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['certificate'] = BaseFieldDefinition::create('list_string')
      ->setTranslatable(TRUE)
      ->setLabel(t('Certificate for Completion'))
      ->setSetting('allowed_values', [
        'no' => 'No',
        'yes' => 'Yes',
      ])
      // Make the field required.
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_buttons',
        'weight' => 11,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['upload_document'] = BaseFieldDefinition::create('file')
      ->setTranslatable(TRUE)
      ->setLabel(t('Upload Document?'))
      ->setDisplayOptions('form', [
        'type' => 'file_generic',
        'weight' => 11,
      ])
      ->setSettings([
        'file_extensions' => 'pdf txt doc',
        // Adjust the maximum file size as needed.
        'max_filesize' => '5MB',
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['visible_start_date'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Visible Start Date'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 12,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['visible_end_date'] = BaseFieldDefinition::create('timestamp')
      ->setTranslatable(TRUE)
      ->setLabel(t('Visible End Date'))
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 13,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['video_category'] = BaseFieldDefinition::create('entity_reference')
      ->setTranslatable(TRUE)
      ->setLabel(t('Categories'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler_settings', ['target_bundles' => ['rl_categories' => 'rl_categories']])
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('form', [
        // Use checkboxes for multiple selections.
        'type' => 'options_buttons',
        'weight' => 14,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['video_keywords'] = BaseFieldDefinition::create('entity_reference')
      ->setTranslatable(TRUE)
      ->setLabel(t('Keywords'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler_settings', ['target_bundles' => ['rl_keywords' => 'rl_keywords']])
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('form', [
        // Use checkboxes for multiple selections.
        'type' => 'options_buttons',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['seasonal_webinar'] = BaseFieldDefinition::create('list_string')
      ->setTranslatable(TRUE)
      ->setLabel(t('Is this a Seasonal Webinar?'))
      ->setSetting('allowed_values', [
        'no' => 'No',
        'yes' => 'Yes',
      ])
      // Make the field required.
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_buttons',
        'weight' => 16,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['promot_to_front'] = BaseFieldDefinition::create('boolean')
      ->setTranslatable(TRUE)
      ->setLabel(t('Promot to front page'))  // Label for the checkbox field
      ->setDefaultValue(FALSE)  // Default value: unchecked (FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',  // Render as a checkbox in forms
        'weight' => 16,  // Position in the form
      ])
      ->setDisplayConfigurable('form', TRUE)  // Make configurable in the form
      ->setDisplayOptions('view', [
        'type' => 'boolean',  // Render as boolean in views
        'label' => 'above',  // Show label above the field
        'weight' => 16,  // Position in view mode
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['status'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Publish video?'))
      ->setDefaultValue(TRUE)
      ->setSetting('allowed_values', [
        'publish' => 'Publish',
        'unpublish' => 'Unpublish',
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_buttons',
        'weight' => 17,
      ])
      ->setDisplayConfigurable('form', TRUE)
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
        'weight' => 18,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'author',
        'weight' => 18,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Authored on'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the heart video resource was created.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'timestamp',
        'weight' => 19,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 19,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setTranslatable(TRUE)
      ->setDescription(t('The time that the heart video resource was last edited.'));

    return $fields;
  }
}
