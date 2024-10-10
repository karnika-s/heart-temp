<?php

declare(strict_types=1);

namespace Drupal\heart_custom_forms\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a Heart Custom Forms form.
 */
final class UserClassesForm extends FormBase {
  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current path.
   *
   * @var \Drupal\Core\Path\CurrentPathStack
   */
  protected $currentPath;

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The route match.
   *
   * @var Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The UserRefData Service.
   *
   * @var Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The date and time.
   *
   * @var Drupal\Core\Datetime\DrupalDateTime
   */
  protected $dateTime;

  /**
   * Request Stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * Constructs an EventForm object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param Drupal\Core\Database\Connection $database
   *   The Database connection Service.
   * @param Drupal\Core\Datetime\DrupalDateTime $date_time
   *   The date and time.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The current requeststack.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    CurrentPathStack $current_path,
    AccountInterface $current_user,
    RouteMatchInterface $route_match,
    MessengerInterface $messenger,
    Connection $database,
    TimeInterface $date_time,
    RequestStack $requestStack,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentPath = $current_path;
    $this->currentUser = $current_user;
    $this->routeMatch = $route_match;
    $this->database = $database;
    $this->messenger = $messenger;
    $this->dateTime = $date_time;
    $this->requestStack = $requestStack;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
          $container->get('entity_type.manager'),
          $container->get('path.current'),
          $container->get('current_user'),
          $container->get('current_route_match'),
          $container->get('messenger'),
          $container->get('database'),
          $container->get('datetime.time'),
          $container->get('request_stack')
      );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'user_classes_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $route_match = $this->routeMatch;
    $id = $route_match->getParameter('id');
    // Create table header.
    $header = [
      'class' => $this->t('Class'),
      'class_id' => $this->t('CLASS ID'),
      'teacher' => $this->t('Teacher'),
      'operations' => $this->t('Operations'),
    ];
    $rows = [];
    if ($id != NULL) {
      //Load user profile data to get user field values.
      $user_profile = $this->entityTypeManager->getStorage('user_profile_data')->load($id);
      // Load heart class data to get learner and facilitator.
      $custom_entity = $this->entityTypeManager->getStorage('heart_class');
      $query = $custom_entity->getQuery();
      $orGroup = $query->orConditionGroup()
        ->condition('invite_facilitator', $id)
        ->condition('class_learner', $id);
      $query->condition($orGroup)
        ->accessCheck(FALSE);
      $entity_ids = $query->execute();
      if (!empty($entity_ids)) {
        $entity_ids = array_values($entity_ids);
        foreach ($entity_ids as $entity_id) {
          $class = $this->entityTypeManager->getStorage('heart_class')->load($entity_id);
          $teachers = $class->get('invite_facilitator')->target_id;
          if ($teachers != NULL) {
            $user = $this->entityTypeManager->getStorage('user_profile_data')->load($entity_id);
            $name = $user->first_name->value . ' ' . $user->last_name->value;
          }
          else {
            $name = 'N/A';
          }
          // Prepare table rows data.
          $rows[] = [
            'class' => $class->label->value,
            'class_id' => $class->class_identifier->value,
            'teacher' => $name,
            'operations' => [
              'data' => [
                '#type' => 'markup',
                '#markup' => "<a href='/drop/class-user/" . $class->id() . "/" . $id . "' class='use-ajax' data-dialog-options='{\"width\":300, \"title\":\"Drop\"}' data-dialog-type='modal'>drop</a>",
              ],
            ],
          ];
        }
      }
    }
    $form['school_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('School/Church Name'),
      '#default_value' => $user_profile->school_name->value ?? '',
      '#disabled' => true
    ];
    $form['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No Classes found'),
    ];
    $form['actions'] = [
      '#type' => 'actions',
      'cancel' => [
        '#type' => 'submit',
        '#value' => 'cancel changes',
        '#attributes' => [
          'class' => [
            'btn btn-secondary',
          ],
        ],
        '#submit' => ['::cancelForm'],
        '#limit_validation_errors' => [],
      ],
    ];

    return $form;
  }

  /**
   * Custom submit handler for the cancel button.
   */
  public function cancelForm(array &$form, FormStateInterface $form_state) {
    // Redirect back to the referring page.
    $url = Url::fromUri('internal:/manage-account#all-users-tab');
    $form_state->setRedirectUrl($url);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {

  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {

  }

}
