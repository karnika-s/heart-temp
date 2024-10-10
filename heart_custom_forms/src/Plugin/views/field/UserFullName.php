<?php

declare(strict_types=1);

namespace Drupal\heart_custom_forms\Plugin\views\field;

use Drupal\Component\Render\MarkupInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides UserFullName field handler.
 *
 * @ViewsField("heart_custom_forms_user_full_name")
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
final class UserFullName extends FieldPluginBase {

  /**
   * Constructs a new UserFullName instance.
   */

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly Connection $connection,
    RequestStack $requestStack
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->requestStack = $requestStack;
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
      $container->get('request_stack')
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
   * Function to get user first name and last name.
   */
  public function render(ResultRow $values): string|MarkupInterface {
    $author_id = '';
    $name = '';

    if ($values->_entity) {
      $author_id = $values->_entity->get('uid')->target_id;
    }
    if ($author_id) {
      $custom_entity = $this->entityTypeManager->getStorage('user_profile_data');
      $query = $custom_entity->getQuery()
        ->condition('user_data', $author_id)
        ->accessCheck(FALSE);
      $entity_ids = $query->execute();
      if (!empty($entity_ids)) {
        $entity_id = reset($entity_ids);
        $custom_entity = $custom_entity->load($entity_id);
        if (!empty($custom_entity)) {
          $first_name = $custom_entity->first_name->value ?? '';
          $last_name = $custom_entity->last_name->value ?? '';
          $name = $first_name . ' ' . $last_name;
        }
      }
      else {
        $user = $this->entityTypeManager->getStorage('user')->load($author_id);
        $name = $user->name->value;
      }
    }
    return $name;
  }
}
