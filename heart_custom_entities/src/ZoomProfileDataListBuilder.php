<?php

declare(strict_types=1);

namespace Drupal\heart_custom_entities;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;

/**
 * Provides a list controller for the zoom profile data entity type.
 */
final class ZoomProfileDataListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['id'] = $this->t('ID');
    $header['status'] = $this->t('Status');
    $header['created'] = $this->t('Created');
    $header['changed'] = $this->t('Updated');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    /** @var \Drupal\heart_custom_entities\ZoomProfileDataInterface $entity */
    $row['id'] = $entity->id();
    $row['status'] = $entity->get('status')->value ? $this->t('Enabled') : $this->t('Disabled');

    $row['created']['data'] = $entity->get('created')->view(['label' => 'hidden']);
    $row['changed']['data'] = $entity->get('changed')->view(['label' => 'hidden']);
    return $row + parent::buildRow($entity);
  }

}
