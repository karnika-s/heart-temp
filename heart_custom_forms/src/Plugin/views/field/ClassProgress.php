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
use IMSGlobal\LTI\ToolProvider;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;

require_once('../vendor/autoload.php');

/**
 * Provides AccessClass field handler.
 *
 * @ViewsField("heart_custom_forms_class_progress")
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
final class ClassProgress extends FieldPluginBase {

  /**
   * Constructs a new ClassProgress instance.
   */

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The configuration object.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  private ConfigFactoryInterface $configFactory;

  /**
   * @var AccountProxy
   */
  protected $currentUser;

  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    private readonly EntityTypeManagerInterface $entityTypeManager,
    private readonly Connection $connection,
    RequestStack $requestStack,
    ConfigFactoryInterface $config_factory,
    AccountProxyInterface $currentUser,
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->requestStack = $requestStack;
    $this->configFactory = $config_factory;
    $this->currentUser = $currentUser;
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
      $container->get('request_stack'),
      $container->get('config.factory'),
      $container->get('current_user'),
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
   */
  public function render(ResultRow $values) {

    $class_id = \Drupal::request()->get('id');
    $uid = $values->_relationship_entities['user_data']->id();
    if($class_id != null){
      $heart_class = $this->entityTypeManager->getStorage('heart_class')->load($class_id);
    }else{
      $output = t('NA');
    }

    if (!empty($heart_class)) {
      $course_id_moodle = $heart_class->course_field->referencedEntities()[0]->heart_course_reference->referencedEntities()[0]->moodle_course_id->getString();
      $heart_course_progress = $this->entityTypeManager->getStorage('heart_progress_tracker')->loadByProperties(['bundle' => 'class_tracker', 'field_user_ref' => $uid, 'field_course_id' => $course_id_moodle]);

      $percentage = reset($heart_course_progress);

      if($percentage) {
        $percentage = $percentage->field_percent_completion->getString();
        $output .= '
        <div class="progress">
            <div class="progress-bar class-progress-bar" data-classProgress="' . $percentage . '" role="progressbar" style="width: ' . $percentage . '%;" aria-valuenow="' . $percentage . '" aria-valuemin="0" aria-valuemax="100">' . $percentage . '% of 100%</div>
        </div>';
      } else {
        $output = t('NA');
      }
    }

    return [
      '#markup' => $output,
      '#cache' => [
        'max-age' => 0,
      ],
    ];
  }
}
