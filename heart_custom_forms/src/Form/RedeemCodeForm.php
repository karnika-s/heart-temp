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
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Heart Custom Forms form.
 */
final class RedeemCodeForm extends FormBase {
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
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    CurrentPathStack $current_path,
    AccountInterface $current_user,
    RouteMatchInterface $route_match,
    MessengerInterface $messenger,
    Connection $database,
    TimeInterface $date_time,
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentPath = $current_path;
    $this->currentUser = $current_user;
    $this->routeMatch = $route_match;
    $this->database = $database;
    $this->messenger = $messenger;
    $this->dateTime = $date_time;
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
      );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'heart_custom_forms_redeem_code';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $form['form_inline'] = [
      '#prefix' => '<div class="form-inline redeem_activation_code">',
      '#suffix' => '</div>',
    ];
    $form['form_inline']['activation_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Redeem an Activation Code:'),
    ];

    $form['form_inline']['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('submit'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {

    // Get the activation code.
    $activation_code = $form_state->getValue('activation_code');
    $custom_entity = $this->entityTypeManager->getStorage('heart_access_code');
    $custom_entity = $custom_entity->loadByProperties(['label' => $activation_code]);

    if (empty($custom_entity)) {
      $form_state->setErrorByName(
        'activation_code',
        $this->t('Incorrect activation code.'),
      );
    }
    if (!empty($custom_entity)) {
      $custom_entity = reset($custom_entity);
      if ($custom_entity->consumed->value == '1') {
        $form_state->setErrorByName(
          'activation_code',
          $this->t('This activation code is already consumed.'),
        );
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {

    // Get the activation code.
    $activation_code = $form_state->getValue('activation_code');

    // Get currunt user id.
    $uid = $this->currentUser->id();

    $custom_entity = $this->entityTypeManager->getStorage('heart_access_code');
    $custom_entity = $custom_entity->loadByProperties(['label' => $activation_code]);
    if (!empty($custom_entity)) {
      $accessCodeEntity = reset($custom_entity);
      // Create the route parameters.
      $route_parameters = [
        'access_code' => $activation_code,
      ];

      // Redirect to the route using setRedirect.
      $form_state->setRedirect('heart_custom_forms.user_registration', [], [
        'absolute' => TRUE,
        'query' => $route_parameters,
      ]);
    }
  }

}
