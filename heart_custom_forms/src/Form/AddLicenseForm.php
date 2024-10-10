<?php

declare(strict_types=1);

namespace Drupal\heart_custom_forms\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\heart_custom_forms\HeartCustomService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Heart Custom Forms form.
 */
final class AddLicenseForm extends FormBase {

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
   * Get route parameter.
   *
   * @var Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routematch;

  /**
   * Protected @var message message service.
   *
   * @var message
   */
  protected $message;

  /**
   * Custom Helper service.
   *
   * @var \Drupal\heart_custom_forms\HeartCustomService
   */
  protected $helper;
  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs an UserCustomFormsHelper object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Path\CurrentPathStack $current_path
   *   The current path.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param Drupal\Core\Messenger\MessengerInterface $message
   *   The message service.
   * @param Drupal\heart_custom_forms\HeartCustomService $helper
   *   The custom helper service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    CurrentPathStack $current_path,
    AccountInterface $current_user,
    RouteMatchInterface $route_match,
    MessengerInterface $message,
    HeartCustomService $helper,
    LanguageManagerInterface $language_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentPath = $current_path;
    $this->currentUser = $current_user;
    $this->routematch = $route_match;
    $this->message = $message;
    $this->helper = $helper;
    $this->languageManager = $language_manager;
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
      $container->get('heart_custom_forms.heart_custom_service'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'heart_custom_forms_add_license';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['#prefix'] = '<div id="add-license-form">';
    $form['#suffix'] = '</div>';
    // Add a container to display error messages.
    $form['form_errors'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'form-errors'],
    ];
    $form['purchased_for'] = [
      '#type' => 'radios',
      '#title' => $this->t('Purchased for'),
      '#options' => [
        'diocese' => 'Diocese',
        'parish' => 'Parish',
      ],
      '#default_value' => 'diocese',
      '#description' => $this->t('If no Diocese found, contact IT.'),
      '#prefix' => '<div class="form-inline purchased-for">',
      '#suffix' => '</div>',
    ];
    // Call helper function to get diocese.
    $diocese_options = $this->helper->getDioceseName();
    $form['field_diocese'] = [
      '#type' => 'select',
      '#chosen' => TRUE,
      '#options' => $diocese_options,
      // Ajax callback to get parish on the basis of diocese.
      '#ajax' => [
        'callback' => [$this, 'getParishData'],
        'wrapper' => 'add-license-form',
        'event' => 'change',
      ],
    ];
    // Get trigger element after ajax.
    $triggering_element = $form_state->getTriggeringElement();
    // Check triggered element if triggered element is
    // diocese select then this condition apply.
    $parish_options = ['' => $this->t('- Select Parish -')];
    if (!empty($triggering_element) && $triggering_element['#name'] == 'field_diocese') {
      // Call helper function to get parish based on diocese.
      $parish_options = $this->helper->getParishByDiocese($triggering_element['#value']);
    }

    $form['field_parish'] = [
      '#type' => 'select',
      '#chosen' => TRUE,
      '#options' => $parish_options,
      '#states' => [
        'visible' => [
          ':input[name="purchased_for"]' => ['value' => 'parish'],
        ],
      ],
    ];
    $course_option = $this->helper->getCourseProduct();
    $form['courses'] = [
      '#type' => 'select',
      '#chosen' => TRUE,
      '#title' => $this->t('Courses Available'),
      '#options' => $course_option,
    ];

    $form['purchased_date'] = [
      '#type' => 'date',
      '#title' => $this->t('Date Purchased'),
      '#required' => TRUE,
    ];

    $form['license_quantity'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('License Quantity Purchased'),
    ];

    $form['sales_order_number'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Sales Order Number'),
    ];

    $form['nav_item_number'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Nav Item Number'),
    ];

    $form['nav_customer_number'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Nav Customer Number'),
    ];

    $form['field_description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Notes'),
      '#rows' => 6,
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'cancel' => [
        '#type' => 'submit',
        '#value' => 'cancel',
        '#attributes' => [
          'class' => [
            'btn btn-light',
          ],
        ],
        '#submit' => ['::cancelForm'],
        '#limit_validation_errors' => [],
      ],
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('add licenses'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();
    // Get the current language.
    $current_language = $this->languageManager->getCurrentLanguage()->getId();
    if (!empty($values)) {
      if ($values['purchased_for'] == 'diocese' && $values['field_diocese'] == '') {
        $form_state->setErrorByName('field_diocese', $this->t('Please select Diocese.'));
      }
      if ($values['purchased_for'] == 'parish' && $values['field_diocese'] == '') {
        $form_state->setErrorByName('field_diocese', $this->t('Please select Diocese.'));
      }
      if ($values['purchased_for'] == 'parish' && $values['field_parish'] == '') {
        $form_state->setErrorByName('field_parish', $this->t('Please select Parish.'));
      }
      if ($values['courses'] != '') {
        $class_entity = $this->entityTypeManager->getStorage('heart_license');
        $query = $class_entity->getQuery();
        $query->condition('course_field', $values['courses']);
        $query->condition('langcode', $current_language);
        if ($values['purchased_for'] == 'parish' && $values['field_parish'] != '') {
          $query->condition('diocese_field', $values['field_diocese']);
          $query->condition('parish_field', $values['field_parish']);
        }
        else {
          $query->condition('diocese_field', $values['field_diocese']);
        }
        $query->accessCheck(FALSE);
        $entity_ids = $query->execute();
        if (!empty($entity_ids)) {
          if ($values['field_parish'] == '') {
            $form_state->setErrorByName('courses', $this->t('The license for the course is already exist for this diocese.'));
          }
          else {
            $form_state->setErrorByName('courses', $this->t('The license for the course is already exist for this parish.'));
          }
        }
      }
    }
  }

  /**
   * Ajax callback for Diocese select field.
   */
  public function getParishData($form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * AJAX callback for the form submission.
   */
  public function ajaxSubmitCallback($form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * Custom submit handler for the cancel button.
   */
  public function cancelForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('<front>'); // Redirect to homepage
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Get the current language.
    $current_language = $this->languageManager->getCurrentLanguage()->getId();
    // Check if there are any validation errors.
    $values = $form_state->getValues();
    if (!empty($values)) {
      // Create heart license entity.
      $license_entity = $this->entityTypeManager->getStorage('heart_license')->create([
        'purchased_for' => $values['purchased_for'],
        'diocese_field' => $values['field_diocese'],
        'course_field' => $values['courses'],
        'purchased_date' => date('Y-m-d', strtotime($values['purchased_date'])),
        'license_quantity_purchased' => $values['license_quantity'],
        'license_quantity_available' => $values['license_quantity'],
        'sales_order_number' => $values['sales_order_number'],
        'nav_item_number' => $values['nav_item_number'],
        'nav_customer_number' => $values['nav_customer_number'],
        'description' => $values['field_description'],
        'label' => '',
        'status' => TRUE,
        'langcode' => $current_language,
      ]);
      // Check if license created for parish,
      // if for parish than set value to entity.
      if ($values['purchased_for'] == 'parish' && $values['field_parish'] != '') {
        $license_entity->set('parish_field', $values['field_parish']);
      }
      $license_entity->save();
      $this->message->addMessage($this->t('Your changes have been successfully saved!'));
      $options = [
        'fragment' => 'add-licenses-tab',
      ];
      // $form_state->setRedirect('<current>', [], $options);
    }
  }

}
