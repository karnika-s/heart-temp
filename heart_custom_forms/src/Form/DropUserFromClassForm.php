<?php

declare(strict_types=1);

namespace Drupal\heart_custom_forms\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
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

/**
 * Provides a Heart Custom Forms form.
 */
final class DropUserFromClassForm extends FormBase {
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
    return 'drop_user_from_class_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $class_id = $this->routeMatch->getParameter('class_id');
    $user_id = $this->routeMatch->getParameter('user_id');
    if ($class_id != NULL && $user_id != NULL) {
      $form['markup'] = [
        '#type' => 'markup',
        '#markup' => '<div class="mb-3">
            <p>Are you sure you want to drop this class?</p>
          </div>',
      ];
      $form['class_id'] = [
        '#type' => 'hidden',
        '#default_value' => $class_id,
      ];
      $form['user_id'] = [
        '#type' => 'hidden',
        '#default_value' => $user_id,
      ];

      $form['form_inline']['actions'] = [
        '#type' => 'actions',
        'submit' => [
          '#type' => 'submit',
          '#value' => $this->t('submit'),
        ],
      ];

      $form['delete_button'] = [
        '#type' => 'button',
        '#value' => $this->t('Cancel'),
        '#button_type' => 'primary',
        '#ajax' => [
          'callback' => [$this, 'removemodalCallback'],
        ],
        '#suffix' => '</div>',
      ];
    }
    return $form;
  }

  /**
   * Submit callback for remove modal popup.
   */
  public function removemodalCallback(array $form, FormStateInterface $form_state) {
    $command = new CloseModalDialogCommand();
    $response = new AjaxResponse();
    $response->addCommand($command);
    return $response;
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
    $values = $form_state->getValues();
    if (!empty($values)) {
      // Load heart class entity.
      $classnode = $this->entityTypeManager->getStorage('heart_class')->load($values['class_id']);
      // Remove user from field.
      if ($classnode->hasField('invite_facilitator')) {
        $items = $classnode->get('invite_facilitator');
        for ($i = 0; $i < $items->count(); $i++) {
          if ($items->get($i)->target_id == $values['user_id']) {
            $items->removeItem($i);
            $i--;
          }
        }
        $classnode->save();
      }
      // Remove user from field.
      if ($classnode->hasField('class_learner')) {
        $items = $classnode->get('class_learner');
        for ($i = 0; $i < $items->count(); $i++) {
          if ($items->get($i)->target_id == $values['user_id']) {
            $items->removeItem($i);
            $i--;
          }
        }
        $classnode->save();
      }
    }
    // Redirect to the refering page.
    $url = Url::fromUri('internal:/manage-account#all-users-tab');
    $form_state->setRedirectUrl($url);
    $this->messenger()->addMessage($this->t("Successfully drop class."));
  }

}
