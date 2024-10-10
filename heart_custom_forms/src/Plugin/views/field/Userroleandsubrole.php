<?php

declare(strict_types=1);

namespace Drupal\heart_custom_forms\Plugin\views\field;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides UserRoleandSubRole field handler.
 *
 * @ViewsField("heart_custom_forms_userroleandsubrole")
 *
 * @DCG
 * The plugin needs to be assigned to a specific table column through
 * hook_views_data() or hook_views_data_alter().
 * Put the following code to heart_custom_forms.views.inc file.
 * @code
 * function foo_views_data_alter(array &$data): void {
 *   $data['node']['foo_example']['field'] = [
 *     'title' => t('Example'),
 *     'help' => t('Custom example field.'),
 *     'id' => 'foo_example',
 *   ];
 * }
 * @endcode
 */
final class Userroleandsubrole extends FieldPluginBase {

  /**
   * Constructs a new Userroleandsubrole instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly Connection $connection,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new self(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('database'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query(): void {
    // For non-existent columns (i.e. computed fields) this method must be
    // empty.
  }

  /**
   * {@inheritdoc}
   *
   * Function to get user role and sub role.
   */
  public function render(ResultRow $values): string|MarkupInterface {
    $userMainroles = $values->users_field_data_user_profile_data_field_data__user__roles_r;
    $subRoles = $values->_entity->get('sub_role')->getValue();
    if (!empty($subRoles)) {
      foreach ($subRoles as $subrole) {
        // Load sub role taxonomy corresponding to the target IDs.
        $subroleterm = $this->entityTypeManager->getStorage('taxonomy_term')->load($subrole['target_id']);
        if ($subroleterm->parent->target_id == "0") {
          $usesubrole[] = $subroleterm->name->value;
        }
      }
      if ($userMainroles != '') {
        // Convert user role name into uppercase.
        $userMainroles = explode(',', ucwords($userMainroles));
      }
      // Remove duplicates role.
      $mergedChars = array_unique(array_merge($userMainroles, $usesubrole));
      $userrole = implode(',', $mergedChars);
    }
    else {
      $userrole = $userMainroles ?? 'N/A';
    }
    return $userrole;
  }

}
