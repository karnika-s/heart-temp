<?php

declare(strict_types=1);

namespace Drupal\heart_custom_entities\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\heart_custom_entities\PdfResourceInterface;

/**
 * Defines the pdf resource entity class.
 *
 * @ContentEntityType(
 *   id = "pdf_resource",
 *   label = @Translation("PDF Resource"),
 *   label_collection = @Translation("PDF Resource"),
 *   label_singular = @Translation("pdf resource"),
 *   label_plural = @Translation("pdf resources"),
 *   label_count = @PluralTranslation(
 *     singular = "@count pdf resource",
 *     plural = "@count pdf resources",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\heart_custom_entities\PdfResourceListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\heart_custom_entities\Form\PdfResourceForm",
 *       "edit" = "Drupal\heart_custom_entities\Form\PdfResourceForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\heart_custom_entities\Routing\PdfResourceHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "pdf_resource",
 *   data_table = "pdf_resource_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer pdf_resource",
 *   entity_keys = {
 *     "id" = "id",
 *     "langcode" = "langcode",
 *     "label" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/pdf-resource-entities",
 *     "add-form" = "/pdf-resource-entities/add",
 *     "canonical" = "/pdf-resource-entities/{pdf_resource}",
 *     "edit-form" = "/pdf-resource-entities/{pdf_resource}",
 *     "delete-form" = "/pdf-resource-entities/{pdf_resource}/delete",
 *     "delete-multiple-form" = "/admin/content/pdf-resource-entities/delete-multiple",
 *   },
 *   field_ui_base_route = "entity.pdf_resource.settings",
 * )
 */
final class PdfResource extends ContentEntityBase implements PdfResourceInterface
{

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function label()
  {
    // Check if current entity translation exists before accessing the field.
    if ($this->hasTranslation($this->language()->getId())) {
      $translated_entity = $this->getTranslation($this->language()->getId());
      return $translated_entity->get('product_title')->value;
    }

    // Fallback default lang or another logic if the translation doesn't exist.
    return $this->getUntranslated()->get('product_title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array
  {

    $fields = parent::baseFieldDefinitions($entity_type);
    $fields['product_title'] = BaseFieldDefinition::create('string')
      ->setTranslatable(TRUE)
      ->setLabel(t('Title of Product'))
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

    $fields['edition_number'] = BaseFieldDefinition::create('string')
      ->setTranslatable(TRUE)
      ->setLabel(t('Edition Number'))
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

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setTranslatable(TRUE)
      ->setLabel(t('Description/Overview'))
      ->setDisplayOptions('form', [
        'type' => 'textarea',
        'weight' => 3,
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
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['rl_price'] = BaseFieldDefinition::create('string')
      ->setTranslatable(TRUE)
      ->setLabel(t('Price'))
      ->setDisplayOptions('form', [
        'type' => 'string',
        'weight' => 5,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string_textfield',
        'label' => 'above',
        'weight' => 5,
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
        'weight' => 6,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string_textfield',
        'label' => 'above',
        'weight' => 6,
      ])
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['pages'] = BaseFieldDefinition::create('string')
      ->setTranslatable(TRUE)
      ->setLabel(t('Pages'))
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

    $fields['upload_document'] = BaseFieldDefinition::create('file')
      ->setTranslatable(TRUE)
      ->setLabel(t('Upload Document?'))
      ->setDisplayOptions('form', [
        'type' => 'file_generic',
        'weight' => 7,
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
        'weight' => 8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['visible_end_date'] = BaseFieldDefinition::create('timestamp')
      ->setTranslatable(TRUE)
      ->setLabel(t('Visible End Date'))
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['replace_thumbnail'] = BaseFieldDefinition::create('image')
      ->setTranslatable(TRUE)
      ->setLabel(t('Replace Thumbnail'))
      ->setSettings([
        'file_extensions' => 'png jpeg gif',
        'max_filesize' => '5MB',
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

    $fields['rl_category'] = BaseFieldDefinition::create('entity_reference')
      ->setTranslatable(TRUE)
      ->setLabel(t('Categories'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler_settings', ['target_bundles' => ['rl_categories' => 'rl_categories']])
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('form', [
        // Use checkboxes for multiple selections.
        'type' => 'options_buttons',
        'weight' => 11,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['rl_keywords'] = BaseFieldDefinition::create('entity_reference')
      ->setTranslatable(TRUE)
      ->setLabel(t('Keywords'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler_settings', ['target_bundles' => ['rl_keywords' => 'rl_keywords']])
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('form', [
        // Use checkboxes for multiple selections.
        'type' => 'options_buttons',
        'weight' => 12,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['promot_to_front'] = BaseFieldDefinition::create('boolean')
      ->setTranslatable(TRUE)
      ->setLabel(t('Promot to front page'))  // Label for the checkbox field
      ->setDefaultValue(FALSE)  // Default value: unchecked (FALSE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',  // Render as a checkbox in forms
        'weight' => 12,  // Position in the form
      ])
      ->setDisplayConfigurable('form', TRUE)  // Make configurable in the form
      ->setDisplayOptions('view', [
        'type' => 'boolean',  // Render as boolean in views
        'label' => 'above',  // Show label above the field
        'weight' => 12,  // Position in view mode
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setTranslatable(TRUE)
      ->setLabel(t('Authored on'));

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setTranslatable(TRUE)
      ->setLabel(t('Publish'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'checkbox',
        'weight' => 13,
        'settings' => [
          'on_label' => 'Published',
          'off_label' => 'Unpublished',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setTranslatable(TRUE)
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the user profile data was last edited.'));

    return $fields;
  }
}
