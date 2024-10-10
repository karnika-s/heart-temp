<?php

declare(strict_types=1);

namespace Drupal\heart_custom_entities\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Field\FieldStorageDefinitionInterface;
use Drupal\heart_custom_entities\CourseProductInterface;

/**
 * Defines the course product entity class.
 *
 * @ContentEntityType(
 *   id = "course_product",
 *   label = @Translation("Course Product"),
 *   label_collection = @Translation("Course Products"),
 *   label_singular = @Translation("course product"),
 *   label_plural = @Translation("course products"),
 *   label_count = @PluralTranslation(
 *     singular = "@count course products",
 *     plural = "@count course products",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\heart_custom_entities\CourseProductListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\heart_custom_entities\Form\CourseProductForm",
 *       "edit" = "Drupal\heart_custom_entities\Form\CourseProductForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\heart_custom_entities\Routing\CourseProductHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "course_product",
 *   data_table = "course_product_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer course_product",
 *   entity_keys = {
 *     "id" = "id",
 *     "langcode" = "langcode",
 *     "label" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/course-product",
 *     "add-form" = "/course-product/add",
 *     "canonical" = "/course-product/{course_product}",
 *     "edit-form" = "/course-product/{course_product}",
 *     "delete-form" = "/course-product/{course_product}/delete",
 *     "delete-multiple-form" = "/admin/content/course-product/delete-multiple",
 *   },
 *   field_ui_base_route = "entity.course_product.settings",
 * )
 */
final class CourseProduct extends ContentEntityBase implements CourseProductInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function label() {
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
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);
    $fields['product_title'] = BaseFieldDefinition::create('string')
      ->setTranslatable(TRUE)
      ->setLabel(t('Title of Product'))
      ->setDisplayOptions('form', [
        'type' => 'string',
        'weight' => 1,
      ])
      ->setRequired(TRUE)
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

    $fields['heart_course_reference'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Heart Course Reference'))
      ->setDescription(t('Reference to a heart course entity.'))
      ->setSetting('target_type', 'heart_course')
      ->setSetting('handler', 'default')
      // ->setRequired(TRUE)
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

    $fields['thumbnail_image'] = BaseFieldDefinition::create('image')
      ->setLabel(t('Thumbnail Image'))
      ->setSettings([
        'file_extensions' => 'png jpg jpeg',
        'max_filesize' => '5MB',
      ])
      ->setDisplayOptions('form', [
        'type' => 'image_image',
        'weight' => 2,
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
        'weight' => 2,
        'settings' => [
          'image_style' => 'medium',
          'image_link' => '',
        ],
      ])
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['module_type'] = BaseFieldDefinition::create('entity_reference')
      ->setTranslatable(TRUE)
      ->setLabel(t('Module Type'))
      // Make the field required.
      ->setRequired(TRUE)
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler_settings', ['target_bundles' => ['module_type' => 'module_type']])
      ->setDisplayOptions('form', [
        // Use checkboxes for multiple selections.
        'type' => 'options_buttons',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['module_bundle'] = BaseFieldDefinition::create('entity_reference')
      ->setTranslatable(TRUE)
      ->setLabel(t('Module Bundle'))
      // Make the field required.
      ->setRequired(TRUE)
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler_settings', ['target_bundles' => ['module_bundle' => 'module_bundle']])
      ->setDisplayOptions('form', [
        // Use checkboxes for multiple selections.
        'type' => 'options_buttons',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['description'] = BaseFieldDefinition::create('text_long')
      ->setTranslatable(TRUE)
      ->setLabel(t('Description'))
      ->setDisplayOptions('form', [
        'type' => 'textarea',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['price'] = BaseFieldDefinition::create('string')
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

    $fields['isbn'] = BaseFieldDefinition::create('string')
      ->setTranslatable(TRUE)
      ->setLabel(t('ISBN'))
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

    $fields['keywords'] = BaseFieldDefinition::create('entity_reference')
      ->setTranslatable(TRUE)
      ->setLabel(t('Keywords'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler_settings', ['target_bundles' => ['rl_keywords' => 'rl_keywords']])
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED)
      ->setDisplayOptions('form', [
        // Use checkboxes for multiple selections.
        'type' => 'options_buttons',
        'weight' => 7,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setTranslatable(TRUE)
      ->setLabel(t('Authored on'));

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setTranslatable(TRUE)
      ->setLabel(t('Published'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 8,
        'settings' => [
          'on_label' => t('Yes'),
          'off_label' => t('No'),
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayOptions('view', [
        'type' => 'boolean',
        'label' => 'above',
        'weight' => 8,
        'settings' => [
          'format' => 'enabled-disabled',
        ],
      ]);

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setTranslatable(TRUE)
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the user profile data was last edited.'));

    return $fields;
  }

}
