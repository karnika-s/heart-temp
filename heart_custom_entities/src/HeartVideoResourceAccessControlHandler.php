<?php

declare(strict_types=1);

namespace Drupal\heart_custom_entities;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the heart video resource entity type.
 *
 * phpcs:disable Drupal.Arrays.Array.LongLineDeclaration
 *
 * @see https://www.drupal.org/project/coder/issues/3185082
 */
final class HeartVideoResourceAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    return match($operation) {
      'view' => AccessResult::allowedIfHasPermissions($account, ['view heart_video_resource', 'administer heart_video_resource'], 'OR'),
      'update' => AccessResult::allowedIfHasPermissions($account, ['edit heart_video_resource', 'administer heart_video_resource'], 'OR'),
      'delete' => AccessResult::allowedIfHasPermissions($account, ['delete heart_video_resource', 'administer heart_video_resource'], 'OR'),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, ['create heart_video_resource', 'administer heart_video_resource'], 'OR');
  }

}
