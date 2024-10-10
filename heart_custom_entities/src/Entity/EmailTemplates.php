<?php

declare(strict_types=1);

namespace Drupal\heart_custom_entities\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\heart_custom_entities\EmailTemplatesInterface;

/**
 * Defines the emailtemplates entity class.
 *
 * @ContentEntityType(
 *   id = "heart_email_template",
 *   label = @Translation("EmailTemplates"),
 *   label_collection = @Translation("EmailTemplates"),
 *   label_singular = @Translation("emailtemplates"),
 *   label_plural = @Translation("emailtemplates"),
 *   label_count = @PluralTranslation(
 *     singular = "@count emailtemplates",
 *     plural = "@count emailtemplates",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\heart_custom_entities\EmailTemplatesListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\heart_custom_entities\Form\EmailTemplatesForm",
 *       "edit" = "Drupal\heart_custom_entities\Form\EmailTemplatesForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\heart_custom_entities\Routing\EmailTemplatesHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "heart_email_template",
 *   data_table = "heart_email_template_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer heart_email_template",
 *   entity_keys = {
 *     "id" = "id",
 *     "langcode" = "langcode",
 *     "label" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/heart-email-template",
 *     "add-form" = "/email-template/add",
 *     "canonical" = "/email-template/{heart_email_template}",
 *     "edit-form" = "/email-template/{heart_email_template}",
 *     "delete-form" = "/email-template/{heart_email_template}/delete",
 *     "delete-multiple-form" = "/admin/content/heart-email-template/delete-multiple",
 *   },
 *   field_ui_base_route = "entity.heart_email_template.settings",
 * )
 */
final class EmailTemplates extends ContentEntityBase implements EmailTemplatesInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function label() {
    // Check if current entity translation exists before accessing the field.
    if ($this->hasTranslation($this->language()->getId())) {
      $translated_entity = $this->getTranslation($this->language()->getId());
      return $translated_entity->get('template_name')->value;
    }

    // Fallback default lang or another logic if the translation doesn't exist.
    return $this->getUntranslated()->get('template_name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['template_name'] = BaseFieldDefinition::create('string')
      ->setTranslatable(TRUE)
      ->setLabel(t('Template Name'))
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

    $fields['email_subject'] = BaseFieldDefinition::create('string')
      ->setTranslatable(TRUE)
      ->setLabel(t('Subject Line'))
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

    $fields['email_message'] = BaseFieldDefinition::create('text_long')
      ->setTranslatable(TRUE)
      ->setLabel(t('Email Message'))
      ->setDisplayOptions('form', [
        'type' => 'textarea',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['trigger_action'] = BaseFieldDefinition::create('entity_reference')
      ->setTranslatable(TRUE)
      ->setLabel(t('Trigger Action'))
      ->setSetting('target_type', 'taxonomy_term')
      ->setSetting('handler_settings', ['target_bundles' => ['trigger_action' => 'trigger_action']])
      ->setDisplayOptions('form', [
        // Use select list for single or multiple selections.
        'type' => 'options_select',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['from_name'] = BaseFieldDefinition::create('string')
      ->setTranslatable(TRUE)
      ->setLabel(t('From Name'))
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

    $fields['from_email'] = BaseFieldDefinition::create('string')
      ->setTranslatable(TRUE)
      ->setLabel(t('From Email'))
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

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setTranslatable(TRUE)
      ->setLabel(t('Authored on'));

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setTranslatable(TRUE)
      ->setLabel(t('Active?'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 7,
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
        'weight' => 7,
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
