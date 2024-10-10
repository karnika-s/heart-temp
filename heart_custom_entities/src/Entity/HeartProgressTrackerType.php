<?php

declare(strict_types=1);

namespace Drupal\heart_custom_entities\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBundleBase;

/**
 * Defines the Heart progress tracker type configuration entity.
 *
 * @ConfigEntityType(
 *   id = "heart_progress_tracker_type",
 *   label = @Translation("Heart progress tracker type"),
 *   label_collection = @Translation("Heart progress tracker types"),
 *   label_singular = @Translation("heart progress tracker type"),
 *   label_plural = @Translation("heart progress trackers types"),
 *   label_count = @PluralTranslation(
 *     singular = "@count heart progress trackers type",
 *     plural = "@count heart progress trackers types",
 *   ),
 *   handlers = {
 *     "form" = {
 *       "add" = "Drupal\heart_custom_entities\Form\HeartProgressTrackerTypeForm",
 *       "edit" = "Drupal\heart_custom_entities\Form\HeartProgressTrackerTypeForm",
 *       "delete" = "Drupal\Core\Entity\EntityDeleteForm",
 *     },
 *     "list_builder" = "Drupal\heart_custom_entities\HeartProgressTrackerTypeListBuilder",
 *     "route_provider" = {
 *       "html" = "Drupal\Core\Entity\Routing\AdminHtmlRouteProvider",
 *     },
 *   },
 *   admin_permission = "administer heart_progress_tracker types",
 *   bundle_of = "heart_progress_tracker",
 *   config_prefix = "heart_progress_tracker_type",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "label",
 *     "uuid" = "uuid",
 *   },
 *   links = {
 *     "add-form" = "/admin/structure/heart_progress_tracker_types/add",
 *     "edit-form" = "/admin/structure/heart_progress_tracker_types/manage/{heart_progress_tracker_type}",
 *     "delete-form" = "/admin/structure/heart_progress_tracker_types/manage/{heart_progress_tracker_type}/delete",
 *     "collection" = "/admin/structure/heart_progress_tracker_types",
 *   },
 *   config_export = {
 *     "id",
 *     "label",
 *     "uuid",
 *   },
 * )
 */
final class HeartProgressTrackerType extends ConfigEntityBundleBase {

  /**
   * The machine name of this heart progress tracker type.
   */
  protected string $id;

  /**
   * The human-readable name of the heart progress tracker type.
   */
  protected string $label;

}
