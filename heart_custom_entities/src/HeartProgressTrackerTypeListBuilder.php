<?php

declare(strict_types=1);

namespace Drupal\heart_custom_entities;

use Drupal\Core\Config\Entity\ConfigEntityListBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Url;

/**
 * Defines a class to build a listing of heart progress tracker type entities.
 *
 * @see \Drupal\heart_custom_entities\Entity\HeartProgressTrackerType
 */
final class HeartProgressTrackerTypeListBuilder extends ConfigEntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['label'] = $this->t('Label');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $row['label'] = $entity->label();
    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    $build = parent::render();

    $build['table']['#empty'] = $this->t(
      'No heart progress tracker types available. <a href=":link">Add heart progress tracker type</a>.',
      [':link' => Url::fromRoute('entity.heart_progress_tracker_type.add_form')->toString()],
    );

    return $build;
  }

}
