<?php

declare(strict_types=1);

namespace Drupal\heart_custom_entities;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the access control handler for the heart diocese data entity type.
 *
 * phpcs:disable Drupal.Arrays.Array.LongLineDeclaration
 *
 * @see https://www.drupal.org/project/coder/issues/3185082
 */
final class HeartDioceseDataAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account): AccessResult {
    return match($operation) {
      'view' => AccessResult::allowedIfHasPermissions($account, ['view heart_diocese_data', 'administer heart_diocese_data'], 'OR'),
      'update' => AccessResult::allowedIfHasPermissions($account, ['edit heart_diocese_data', 'administer heart_diocese_data'], 'OR'),
      'delete' => AccessResult::allowedIfHasPermissions($account, ['delete heart_diocese_data', 'administer heart_diocese_data'], 'OR'),
      default => AccessResult::neutral(),
    };
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL): AccessResult {
    return AccessResult::allowedIfHasPermissions($account, ['create heart_diocese_data', 'administer heart_diocese_data'], 'OR');
  }

}
