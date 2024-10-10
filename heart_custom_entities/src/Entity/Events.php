<?php

declare(strict_types=1);

namespace Drupal\heart_custom_entities\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\heart_custom_entities\EventsInterface;

/**
 * Defines the events entity class.
 *
 * @ContentEntityType(
 *   id = "event",
 *   label = @Translation("Events"),
 *   label_collection = @Translation("Eventss"),
 *   label_singular = @Translation("events"),
 *   label_plural = @Translation("eventss"),
 *   label_count = @PluralTranslation(
 *     singular = "@count eventss",
 *     plural = "@count eventss",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\heart_custom_entities\EventsListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\heart_custom_entities\Form\EventsForm",
 *       "edit" = "Drupal\heart_custom_entities\Form\EventsForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\heart_custom_entities\Routing\EventsHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "event",
 *   data_table = "event_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer event",
 *   entity_keys = {
 *     "id" = "id",
 *     "langcode" = "langcode",
 *     "label" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/event",
 *     "add-form" = "/event/add",
 *     "canonical" = "/event/{event}",
 *     "edit-form" = "/event/{event}",
 *     "delete-form" = "/event/{event}/delete",
 *     "delete-multiple-form" = "/admin/content/event/delete-multiple",
 *   },
 *   field_ui_base_route = "entity.event.settings",
 * )
 */
final class Events extends ContentEntityBase implements EventsInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function label() {
    // Check if current entity translation exists before accessing the field.
    if ($this->hasTranslation($this->language()->getId())) {
      $translated_entity = $this->getTranslation($this->language()->getId());
      return $translated_entity->get('event_title')->value;
    }

    // Fallback default lang or another logic if the translation doesn't exist.
    return $this->getUntranslated()->get('event_title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);
    $fields['event_title'] = BaseFieldDefinition::create('string')
      ->setTranslatable(TRUE)
      ->setLabel(t('Webinar Event Title'))
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

    $fields['presenter'] = BaseFieldDefinition::create('string')
      ->setTranslatable(TRUE)
      ->setLabel(t('Presenter'))
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

    $fields['type'] = BaseFieldDefinition::create('entity_reference')
      ->setTranslatable(TRUE)
      ->setLabel(t('Type'))
      // Make the field required.
      ->setRequired(TRUE)
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler_settings', ['target_bundles' => ['webinar' => 'webinar']])
      ->setDisplayOptions('form', [
        // Use checkboxes for multiple selections.
        'type' => 'options_buttons',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['seasonal_webinar'] = BaseFieldDefinition::create('boolean')
      ->setTranslatable(TRUE)
      ->setLabel(t('Seasonal Webinar'))
      ->setDefaultValue(FALSE)
      ->setDisplayOptions('form', [
        'type' => 'checkbox',
        'weight' => 4,
        'settings' => [
          'on_label' => 'Published',
          'off_label' => 'Unpublished',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['serial_number'] = BaseFieldDefinition::create('string')
      ->setTranslatable(TRUE)
      ->setLabel(t('Serial Number'))
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

    $fields['price'] = BaseFieldDefinition::create('string')
      ->setTranslatable(TRUE)
      ->setLabel(t('Price'))
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

    $fields['nav_item_number'] = BaseFieldDefinition::create('string')
      ->setTranslatable(TRUE)
      ->setLabel(t('Nav Item Number'))
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

    $fields['presenter_bio'] = BaseFieldDefinition::create('text_long')
      ->setTranslatable(TRUE)
      ->setLabel(t('Presenter Bio'))
      ->setDisplayOptions('form', [
        'type' => 'textarea',
        'weight' => 8,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setTranslatable(TRUE)
      ->setLabel(t('Description/Overview'))
      ->setDisplayOptions('form', [
        'type' => 'textarea',
        'weight' => 9,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['thumbnail'] = BaseFieldDefinition::create('image')
      ->setTranslatable(TRUE)
      ->setLabel(t('Thumbnail'))
      ->setSettings([
        'file_extensions' => 'png jpeg gif',
        'max_filesize' => '5MB',
      ])
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
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
        'weight' => 9,
        'settings' => [
          'image_style' => 'medium',
          'image_link' => '',
        ],
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['visible_start_date'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Visible Start Date'))
      ->setTranslatable(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 11,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['visible_end_date'] = BaseFieldDefinition::create('timestamp')
      ->setTranslatable(TRUE)
      ->setLabel(t('Visible End Date'))
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 12,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['webinar_event_date'] = BaseFieldDefinition::create('timestamp')
      ->setTranslatable(TRUE)
      ->setLabel(t('Webinar Event Date'))
      ->setDisplayOptions('form', [
        'type' => 'datetime_timestamp',
        'weight' => 13,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);
    $fields['timezone'] = BaseFieldDefinition::create('string')
      ->setTranslatable(TRUE)
      ->setLabel(t('Time Zone'))
      ->setDisplayOptions('form', [
        'type' => 'string',
        'weight' => 14,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string_textfield',
        'label' => 'above',
        'weight' => 14,
      ])
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);
    $fields['certificate'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Certificate for Completion'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        // Changed widget type to 'options_buttons'.
        'type' => 'options_buttons',
        'weight' => 15,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE)
      ->setSettings([
        // Label for TRUE value.
        'on_label' => 'Yes',
        // Label for FALSE value.
        'off_label' => 'No',
      ]);

    $fields['certificate_completion'] = BaseFieldDefinition::create('string')
      ->setTranslatable(TRUE)
      ->setLabel(t('% of time in attendance'))
      ->setDisplayOptions('form', [
        'type' => 'string',
        'weight' => 16,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string_textfield',
        'label' => 'above',
        'weight' => 16,
      ])
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);

    $fields['publish'] = BaseFieldDefinition::create('list_string')
      ->setTranslatable(TRUE)
      ->setLabel(t('Who will this video Publish to?'))
      ->setSetting('allowed_values', [
        'all' => 'Publish to All',
        'attendees' => 'Publish to Attendees',
        'unpublish' => 'Unpublish',
      ])
      // Make the field required.
      ->setRequired(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_buttons',
        'weight' => 17,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['category'] = BaseFieldDefinition::create('entity_reference')
      ->setTranslatable(TRUE)
      ->setLabel(t('Categories'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler_settings', ['target_bundles' => ['event_category' => 'event_category']])
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('form', [
        // Use checkboxes for multiple selections.
        'type' => 'options_buttons',
        'weight' => 18,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['keywords'] = BaseFieldDefinition::create('entity_reference')
      ->setTranslatable(TRUE)
      ->setLabel(t('Keywords'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler_settings', ['target_bundles' => ['rl_keywords' => 'rl_keywords']])
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('form', [
        // Use checkboxes for multiple selections.
        'type' => 'options_buttons',
        'weight' => 19,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['attach_file'] = BaseFieldDefinition::create('file')
      ->setTranslatable(TRUE)
      ->setLabel(t('Attach File'))
      ->setDisplayOptions('form', [
        'type' => 'file_generic',
        'weight' => 20,
      ])
      ->setSettings([
        'file_extensions' => 'pdf txt doc',
        // Adjust the maximum file size as needed.
        'max_filesize' => '5MB',
      ])
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['zoomlink'] = BaseFieldDefinition::create('string')
      ->setTranslatable(TRUE)
      ->setLabel(t('Zoom Link'))
      ->setDisplayOptions('form', [
        'type' => 'string',
        'weight' => 21,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'string_textfield',
        'label' => 'above',
        'weight' => 21,
      ])
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDisplayConfigurable('view', TRUE);
    
    $fields['heart_webinar_reference'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Heart Webinar Reference'))
      ->setDescription(t('Reference to a heart webinar entity.'))
      ->setSetting('target_type', 'heart_zoom_webinars')
      ->setSetting('handler', 'default')
      // ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'entity_reference_label',
        'weight' => 21,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 21,
      ])
      ->setTranslatable(TRUE)
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
        'weight' => 22,
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
