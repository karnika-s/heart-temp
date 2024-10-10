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
 * @ViewsField("heart_custom_forms_access_class")
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
final class AccessClass extends FieldPluginBase {

  /**
   * Constructs a new AccessClass instance.
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

    // Get moodle data from config.
    $config = $this->configFactory->get('heart_moodle_integration.settings');
    $moodle_url = $config->get('moodle_url');
    $consumer_key = $config->get('consumer_key');

    // Get course data from row to fetch launch url and secret key.
    $course_id = $values->_relationship_entities['field_course_product']->heart_course_reference->getString();
    $heart_course = $this->entityTypeManager->getStorage('heart_course')->load($course_id);

    $launch_url = $heart_course->launch_url->getString();
    $key = $consumer_key;
    $secret = $heart_course->secret_key->getString();
    $moodle_course_id = $heart_course->moodle_course_id->getString();

    $uid = $this->currentUser->id();
    $user = $this->entityTypeManager->getStorage('user')->load($uid);
    $user_name = $user->get('name')->value;
    $email = $user->get('mail')->value;
    $first_name = '';
    $last_name = '';
    $name = '';

    $base_url = $host = \Drupal::request()->getSchemeAndHttpHost();

    $user_profile_custom_entity = $this->entityTypeManager->getStorage('user_profile_data');
    $query = $user_profile_custom_entity->getQuery()
      ->condition('user_data', $uid)
      ->accessCheck(FALSE);
    $entity_ids = $query->execute();
    if (!empty($entity_ids)) {
      $entity_id = reset($entity_ids);
      $user_profile_custom_entity = $user_profile_custom_entity->load($entity_id);
      if (!empty($user_profile_custom_entity)) {
        $first_name = $user_profile_custom_entity->first_name->value ?? '';
        $last_name = $user_profile_custom_entity->last_name->value ?? '';
        $name = $first_name . ' ' . $last_name;
      }
    }
    $host = \Drupal::request()->getHost();

    $arguments = array(
      "user_id" => $uid,
      "ext_user_username" => $user_name,
      "roles" => "Learner",
      "ext_lms" => "moodle-2",
      "resource_link_id" => $moodle_course_id, // should be unique
      "resource_link_title" => "Heart LTI Consumer",
      "lis_person_name_full" => $name,
      "lis_person_name_family" => $last_name,
      "lis_person_name_given" => $first_name,
      "lis_person_contact_email_primary" => $email,
      "context_id" => $moodle_course_id, // should be unique, identifies the context that contains the link
      "context_title" => "Heart LTI Consumer",
      "context_label" => "Heart LTI Consumer",
      "context_type" => "CourseSection",
      "tool_consumer_instance_guid" => $host,
      "tool_consumer_info_version" => "1.0",
      "tool_consumer_instance_name" => "Heart Moodle Consumer",
      "tool_consumer_instance_description" => "Heart Moodle Consumer",
      "tool_consumer_info_product_family_code" => "Heart",
      "launch_presentation_document_target" => "window",
      "launch_presentation_locale" => "en-US",
      "lis_outcome_service_url" => $moodle_url . "/mod/lti/service.php",
      "lis_result_sourcedid" => bin2hex(random_bytes(16)), // This field is unique for every combination of context_id / resource_link_id / user_id
      "page" => 'lti',
      "lti_version" => 'LTI-1p0',
    );
    $output = '';
    $consumer = new ToolProvider\ToolConsumer($key);
    $consumer->secret = $secret;
    $singedParameters = $consumer->signParameters($launch_url, 'basic-lti-launch-request', 'LTI-1p0', $arguments);

    $output .= '<form class="d-none" id="ltiLaunchForm" name="ltiLaunchForm" method="POST" target="_blank" action="'.$launch_url.'">';

    foreach ($singedParameters as $k => $v ) {
      $output .= '<input type="hidden" name="'.$k.'" value="'.$v.'">';
    }

    $output .= '<button class="btn btn-primary" type="submit">Launch</button></form>';
    return [
      '#markup' => $output,
      '#cache' => [
        'max-age' => 0,
      ],
      '#allowed_tags' => ['form', 'button', 'input'],
    ];
  }
}
