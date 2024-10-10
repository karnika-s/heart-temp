<?php

declare(strict_types=1);

namespace Drupal\heart_custom_entities\Entity;

use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\heart_custom_entities\CertificateInterface;

/**
 * Defines the certificate entity class.
 *
 * @ContentEntityType(
 *   id = "heart_certificate",
 *   label = @Translation("Certificate"),
 *   label_collection = @Translation("Certificates"),
 *   label_singular = @Translation("certificate"),
 *   label_plural = @Translation("certificates"),
 *   label_count = @PluralTranslation(
 *     singular = "@count certificates",
 *     plural = "@count certificates",
 *   ),
 *   handlers = {
 *     "list_builder" = "Drupal\heart_custom_entities\CertificateListBuilder",
 *     "views_data" = "Drupal\views\EntityViewsData",
 *     "form" = {
 *       "add" = "Drupal\heart_custom_entities\Form\CertificateForm",
 *       "edit" = "Drupal\heart_custom_entities\Form\CertificateForm",
 *       "delete" = "Drupal\Core\Entity\ContentEntityDeleteForm",
 *       "delete-multiple-confirm" = "Drupal\Core\Entity\Form\DeleteMultipleForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\heart_custom_entities\Routing\CertificateHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "heart_certificate",
 *   data_table = "heart_certificate_field_data",
 *   translatable = TRUE,
 *   admin_permission = "administer heart_certificate",
 *   entity_keys = {
 *     "id" = "id",
 *     "langcode" = "langcode",
 *     "label" = "id",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "collection" = "/admin/content/heart-certificate",
 *     "add-form" = "/heart-certificate/add",
 *     "canonical" = "/heart-certificate/{heart_certificate}",
 *     "edit-form" = "/heart-certificate/{heart_certificate}",
 *     "delete-form" = "/heart-certificate/{heart_certificate}/delete",
 *     "delete-multiple-form" = "/admin/content/heart-certificate/delete-multiple",
 *   },
 *   field_ui_base_route = "entity.heart_certificate.settings",
 * )
 */
final class Certificate extends ContentEntityBase implements CertificateInterface {

  use EntityChangedTrait;

  /**
   * {@inheritdoc}
   */
  public function label() {
    // Check if current entity translation exists before accessing the field.
    if ($this->hasTranslation($this->language()->getId())) {
      $translated_entity = $this->getTranslation($this->language()->getId());
      return $translated_entity->get('title')->value;
    }

    // Fallback default lang or another logic if the translation doesn't exist.
    return $this->getUntranslated()->get('title')->value;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type): array {

    $fields = parent::baseFieldDefinitions($entity_type);

    // Define the dynamic entity reference field.
    $fields['dynamic_reference'] = BaseFieldDefinition::create('dynamic_entity_reference')
      ->setLabel(t('Reference Entity'))
      ->setDescription(t('Reference entity for course,video resource and event entity.'))
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'entity_reference_label',
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'dynamic_entity_reference_autocomplete',
        'weight' => 1,
        'settings' => [
          // Leave empty for dynamic entity types.
          'target_type' => '',
        ],
      ])
      ->setSettings([
        // Leave empty for dynamic entity types.
        'target_type' => '',
      ])
      ->setDisplayConfigurable('view', TRUE)
      ->setDisplayConfigurable('form', TRUE);

    $fields['title'] = BaseFieldDefinition::create('string')
      ->setTranslatable(TRUE)
      ->setLabel(t('Certificate Title'))
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

    $fields['diocese_field'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Diocese'))
      ->setDescription(t('Reference to a diocese entity.'))
      ->setSetting('target_type', 'heart_diocese_data')
      ->setSetting('handler', 'default')
      ->setRequired(TRUE)
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'entity_reference_label',
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
      ])
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['parish_field'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Parish'))
      ->setDescription(t('Reference to a parish entity.'))
      ->setSetting('target_type', 'heart_parish_data')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'entity_reference_label',
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
      ])
      ->setTranslatable(TRUE)
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['recipient'] = BaseFieldDefinition::create('string')
      ->setTranslatable(TRUE)
      ->setLabel(t('Recipient'))
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

    $fields['recipient_email'] = BaseFieldDefinition::create('string')
      ->setTranslatable(TRUE)
      ->setLabel(t('Recipient Email'))
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

    $fields['upload_file'] = BaseFieldDefinition::create('file')
      ->setTranslatable(TRUE)
      ->setLabel(t('Upload File'))
      ->setDisplayOptions('form', [
        'type' => 'file_generic',
        'weight' => 20,
      ])
      ->setSettings([
        'file_extensions' => 'pdf txt doc',
        // Adjust the maximum file size as needed.
        'max_filesize' => '5MB',
      ])
      ->setDisplayConfigurable('form', TRUE)
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
