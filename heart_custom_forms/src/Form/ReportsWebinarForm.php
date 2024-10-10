<?php

declare(strict_types=1);

namespace Drupal\heart_custom_forms\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a Heart Custom Forms form.
 */
final class ReportsWebinarForm extends FormBase {

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
   * Request Stack service.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * Protected @var message message service.
   *
   * @var message
   */
  protected $message;

  /**
   * Constructs an UserCustomFormsHelper object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The current requeststack.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The current route match.
   * @param Drupal\Core\Messenger\MessengerInterface $message
   *   The message service.
   */
  public function __construct(
        EntityTypeManagerInterface $entity_type_manager,
        CurrentPathStack $current_path,
        AccountInterface $current_user,
        RequestStack $requestStack,
        RouteMatchInterface $route_match,
        MessengerInterface $message
    ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentPath = $current_path;
    $this->currentUser = $current_user;
    $this->requestStack = $requestStack;
    $this->routeMatch = $route_match;
    $this->message = $message;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
          $container->get('entity_type.manager'),
          $container->get('path.current'),
          $container->get('current_user'),
          $container->get('request_stack'),
          $container->get('current_route_match'),
          $container->get('messenger')
      );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'reports_webinar_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#prefix'] = '<div id="manage-webinar-reports-form">';
    $form['#suffix'] = '</div>';
    $form['select_report_type'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Step 1: Select Report Type'),
    ];
    $form['select_report_type']['report_type'] = [
      '#type' => 'radios',
      '#options' => [
        'registration_report' => 'Registration Report',
        'attendee_report' => 'Attendee Report',
        'qa_report' => 'Q&A Report',
      ],
      '#ajax' => [
        'callback' => [$this, 'getSteptwoData'],
        'wrapper' => 'manage-webinar-reports-form',
        'event' => 'change',
      ],
    ];
    // Get trigger element after ajax.
    $triggering_element = $form_state->getTriggeringElement();

    // Check triggered element if triggered element is report type radio,
    // filter button, search button or
    // webinar data table radio then this condition apply
    // and the fields inside this condition will show.
    if (!empty($triggering_element) && $triggering_element['#name'] == 'report_type' && $triggering_element['#value'] != NULL ||
      isset($triggering_element['#attributes']['name']) && $triggering_element['#attributes']['name'] == 'filter_webinar' ||
       isset($triggering_element['#attributes']['name']) && $triggering_element['#attributes']['name'] == 'search_webinar' ||
      isset($triggering_element['#name']) && $triggering_element['#name'] == 'selected_item') {
      $form['choose_a_webinar'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Step 2: Choose a Webinar'),
        '#prefix' => '<div id="choose-a-webinar" class="rcl-text">',
        '#suffix' => '</div>',
      ];

      $form['choose_a_webinar']['from_date'] = [
        '#type' => 'date',
        '#title' => $this->t('From'),
      ];

      $form['choose_a_webinar']['to_date'] = [
        '#type' => 'date',
        '#title' => $this->t('To'),
      ];
      $form['choose_a_webinar']['filter_webinar_data'] = [
        '#type' => 'button',
        '#value' => $this->t('filter'),
        // Added ajax callback to get filter table data.
        '#ajax' => [
          'callback' => [$this, 'filterWebinarData'],
          'wrapper' => 'manage-webinar-reports-form',
          'event' => 'click',
          'method' => 'replace',
        ],
        '#attributes' => [
          'name' => 'filter_webinar',
        ],
      ];

      $form['choose_a_webinar']['webinar_id'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Webinar ID'),
      ];
      $form['choose_a_webinar']['search_webinar_id'] = [
        '#type' => 'button',
        '#value' => $this->t('search'),
        // Added ajax callback to get filter table data.
        '#ajax' => [
          'callback' => [$this, 'filterWebinarData'],
          'wrapper' => 'manage-webinar-reports-form',
          'event' => 'click',
          'method' => 'replace',
        ],
        '#attributes' => [
          'name' => 'search_webinar',
        ],
      ];

      $form['choose_a_webinar']['markup'] = [
        '#type' => 'markup',
        '#markup' => '<div><h3>Please Select From date And To date to get Webinars data or search with Webinar ID.</h3></div>',
        '#prefix' => '<div id="markup-for-data">',
        '#suffix' => '</div>',
      ];
    }
    // Check triggered element if triggered element is filter button,
    // search button or webinar table radio then this condition apply
    // and the fields inside this condition will show.
    if (!empty($triggering_element) && $triggering_element['#attributes']['name'] == 'filter_webinar' ||
       isset($triggering_element['#attributes']['name']) && $triggering_element['#attributes']['name'] == 'search_webinar' ||
       isset($triggering_element['#name']) && $triggering_element['#name'] == 'selected_item') {
      unset($form['choose_a_webinar']['markup']);
      // Create table header.
      $header = [
        'select' => $this->t('Select'),
        'date' => $this->t('Date'),
        'time' => $this->t('Time'),
        'topic' => $this->t('Topic'),
        'webinar_id' => $this->t('Webinar ID'),
      ];
      $form['choose_a_webinar']['data_items'] = [
        '#type' => 'table',
        '#header' => $header,
        '#prefix' => '<div id="webinar-data-table">',
        '#suffix' => '</div>',
        '#empty' => $this->t('No webinars available at the moment.'),
      ];
      // Getting formstate values after ajax call.
      $values = $form_state->getValues();
      // Attempt to get event entity ID under certain conditions.
      $custom_entity = $this->entityTypeManager->getStorage('event');
      $query = $custom_entity->getQuery();
      if ($values['from_date'] != '') {
        $query->condition('webinar_event_date', strtotime($values['from_date']), '>=');
      }
      if ($values['to_date'] != '') {
        $query->condition('webinar_event_date', strtotime($values['to_date']), '<=');
      }
      if ($values['webinar_id'] != '') {
        $query->condition('id', $values['webinar_id']);
      }
      $query->accessCheck(FALSE);
      $entity_ids = $query->execute();
      $entity_ids = array_values($entity_ids);
      if (!empty($entity_ids)) {
        foreach ($entity_ids as $entity_id) {
          $evententity = $this->entityTypeManager->getStorage('event')->load($entity_id);
          $topic = $evententity->event_title->value;
          $date = date('M j, Y', (int) $evententity->webinar_event_date->value);
          $time = date('h:i:s A', (int) $evententity->webinar_event_date->value);

          $rows[] = [
            'id' => $evententity->id(),
            'date' => $date,
            'time' => $time,
            'topic' => $topic,
            'webinar_id' => $evententity->id(),
          ];
        }
        // Prepairing table rows data.
        foreach ($rows as $row) {
          $form['choose_a_webinar']['data_items'][$row['id']]['select'] = [
            '#type' => 'radio',
            '#parents' => ['selected_item'],
            '#return_value' => $row['id'],
            '#ajax' => [
              'callback' => [$this, 'filterWebinarData'],
              'wrapper' => 'manage-webinar-reports-form',
              'event' => 'click',
            ],
          ];
          $form['choose_a_webinar']['data_items'][$row['id']]['date'] = [
            '#markup' => $row['date'],
          ];
          $form['choose_a_webinar']['data_items'][$row['id']]['time'] = [
            '#markup' => $row['time'],
          ];
          $form['choose_a_webinar']['data_items'][$row['id']]['topic'] = [
            '#markup' => $row['topic'],
          ];
          $form['choose_a_webinar']['data_items'][$row['id']]['webinar_id'] = [
            '#markup' => $row['webinar_id'],
          ];
        }
      }
    }
    // Check triggered element if triggered element is
    // webinar data table radio then this condition apply and
    // the fields inside this condition will show.
    if (!empty($triggering_element) && $triggering_element['#name'] == 'selected_item') {
      $form['generate_report_container'] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Step 3: Generate Report'),
      ];
      $form['generate_report_container']['generate_report'] = [
        '#type' => 'radios',
        '#options' => [
          'all_registrations' => 'All Registrations',
          'approved_registrants' => 'Approved Registrants',
          'denied_registrants' => 'Denied Registrants',
        ],
      ];
      $form['generate_report_container']['actions'] = [
        '#type' => 'actions',
        'submit' => [
          '#type' => 'submit',
          '#value' => 'generate CSV report',
        ],
      ];
    }
    return $form;
  }

  /**
   * Ajax callback for Report Type field.
   */
  public function getSteptwoData($form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * Ajax callback for Filter date Type field.
   */
  public function filterWebinarData($form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();
    $this->message->addMessage($this->t("Generate CSV button clicked Successfully."));
  }

}
