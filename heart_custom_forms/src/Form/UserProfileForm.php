<?php

declare(strict_types=1);

namespace Drupal\heart_custom_forms\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\heart_custom_forms\HeartCustomService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a Heart Custom Forms form.
 */
final class UserProfileForm extends FormBase {

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
   * Custom Helper service.
   *
   * @var \Drupal\heart_custom_forms\HeartCustomService
   */
  protected $helper;

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
   * @param \Drupal\heart_custom_forms\HeartCustomService $helper
   *   The custom helper service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager,
  CurrentPathStack $current_path,
   AccountInterface $current_user,
  RequestStack $requestStack,
  RouteMatchInterface $route_match,
  HeartCustomService $helper) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentPath = $current_path;
    $this->currentUser = $current_user;
    $this->requestStack = $requestStack;
    $this->routeMatch = $route_match;
    $this->helper = $helper;
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
      $container->get('heart_custom_forms.heart_custom_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'heart_custom_forms_user_registration';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $route_path = $this->routeMatch->getRouteObject()->getPath();
    $entity = [];
    $required = TRUE;

    $form['#prefix'] = '<div id="error_element" class="wrapper-900">';
    $form['#suffix'] = '</div>';

    $triggering_element = $form_state->getTriggeringElement();
    $parish_options = ['' => '- Select Parish -'];
    if (!empty($triggering_element) && $triggering_element['#name'] == 'diocese') {
      // Call helper function to get parish based on diocese.
      $parish_options = $this->helper->getParishByDiocese($triggering_element['#value']);
    }
    // The status messages that will contain any form errors.
    $form['status_messages'] = [
      '#type' => 'status_messages',
      '#weight' => -10,
    ];
    $form['personal_information'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Profile'),
    ];

    // Check current user to load profile edit form.
    $currentuserid = $this->currentUser->id();
    $required = FALSE;

    $form['personal_information']['user_id'] = [
      '#type' => 'hidden',
      '#default_value' => !empty($currentuserid) ? $currentuserid : '',
    ];
    $user = $this->entityTypeManager->getStorage('user')->load($currentuserid);
    // Load user profile data entities.
    $custom_entity = $this->entityTypeManager->getStorage('user_profile_data');
    $query = $custom_entity->getQuery()
      ->condition('user_data', $currentuserid)
      ->accessCheck(FALSE);
    $entity_ids = $query->execute();
    if (!empty($entity_ids)) {
      $entity_id = reset($entity_ids);
      $entity = $custom_entity->load($entity_id);
      $roles = $entity->sub_role->getValue();
      foreach ($roles as $role) {
        // Load sub role taxonomy corresponding to the target IDs.
        $subroleterm = $this->entityTypeManager->getStorage('taxonomy_term')->load($role['target_id']);
        if ($subroleterm->parent->target_id == "0") {
          $default_term[$subroleterm->tid->value] = $subroleterm->tid->value;
        }
        if ($subroleterm->parent->target_id == "20") {
          $default_teacher_term[$subroleterm->tid->value] = $subroleterm->tid->value;
        }
        if ($subroleterm->parent->target_id == "21") {
          $default_catechist_term[$subroleterm->tid->value] = $subroleterm->tid->value;
        }
      }
      if ($entity->hasField('user_diocese_field')) {
        $default_diocese = $entity->get('user_diocese_field')->target_id;
      }
    }

    $form['personal_information']['group_left'] = [
      '#prefix' => '<div class="fs-row m-bottom-4"><div class="fs-col-6">',
      '#suffix' => '</div>',
    ];
    $form['personal_information']['group_right'] = [
      '#prefix' => '<div class="fs-col-6">',
      '#suffix' => '</div></div>',
    ];
    $form['personal_information']['group_left']['first_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('First Name'),
      '#required' => TRUE,
      '#default_value' => !empty($entity->first_name->value) ? $entity->first_name->value : '',
    ];
    $form['personal_information']['group_right']['last_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Last Name'),
      '#required' => TRUE,
      '#default_value' => !empty($entity->last_name->value) ? $entity->last_name->value : '',

    ];
    $form['personal_information']['group_left']['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email Address'),
      '#required' => TRUE,
      '#default_value' => !empty($user->mail->value) ? $user->mail->value : '',
    ];
    $form['personal_information']['group_right']['recover_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Recovery Email Address'),
      // '#description' => $this->t('<div class="text-secondary text-right">Backup Email Address</div>'),
      '#default_value' => !empty($entity->recovery_email_field->value) ? $entity->recovery_email_field->value : '',
    ];

    $form['personal_information']['group_left']['confirm_password'] = [
      '#type' => 'password_confirm',
      '#required' => $required,
      // '#description' => $this->t('<div class="text-right"><a href="#" class="text-decoration-underline">Password Policy</a></div>'),
    ];

    // Check Address field value.
    if (!empty($entity) && $entity->hasField('user_profile_address')) {
      $default_address = $entity->get('user_profile_address')->getValue();
      $default_country = $default_address[0]['country_code'];
      $default_state = $default_address[0]['administrative_area'];
      $default_add_1 = $default_address[0]['address_line1'];
      $default_add_2 = $default_address[0]['address_line2'];
      $default_zip = $default_address[0]['postal_code'];
    }
    $address_class = 'Drupal\address\Element\Address';
    $form['personal_information']['group_left']['address'] = [
      '#type' => 'address',
      '#default_value' => [
        'country_code' => $default_country ?? 'US',
        'administrative_area' => $default_state ?? '',
        'address_line1' => $default_add_1 ?? '',
        'address_line2' => $default_add_2 ?? '',
        'postal_code' => $default_zip ?? '',
      ],
      '#field_overrides' => [
        'organization' => 'hidden',
        'givenName' => 'hidden',
        'familyName' => 'hidden',
        'locality' => 'hidden',
        'addressLine3' => 'hidden',
      ],
      '#process' => [
          [$address_class, 'processAddress'],
          [$address_class, 'processGroup'],
          [$this, 'customProcessAddress'],
      ],
    ];

    $form['personal_information']['group_right']['phone'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Phone'),
      '#default_value' => !empty($entity->phone->value) ? $entity->phone->value : '',
    ];

    $form['school_information'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Church/School Information'),
    ];
    $form['school_information']['group_left'] = [
      '#prefix' => '<div class="fs-row"><div class="fs-col-6">',
      '#suffix' => '</div>',
    ];
    $form['school_information']['group_right'] = [
      '#prefix' => '<div class="fs-col-6">',
      '#suffix' => '</div></div>',
    ];
    $form['school_information']['group_left']['school_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('School/Church Name'),
      '#default_value' => !empty($entity->school_name->value) ? $entity->school_name->value : '',
    ];

    // Get trigger element after ajax.
    $triggering_element = $form_state->getTriggeringElement();
    $diocese_options = $this->helper->getDioceseName();
    $form['school_information']['group_right']['diocese'] = [
      '#type' => 'select',
      '#title' => $this->t('Diocese'),
      '#options' => $diocese_options,
      '#default_value' => $default_diocese ?? '',
      // Ajax callback to get parish on the basis of diocese.
      '#ajax' => [
        'callback' => [$this, 'getParish'],
        'wrapper' => 'error_element',
        'event' => 'change',
      ],
    ];

    $form['school_information']['group_right']['parish'] = [
      '#type' => 'select',
      '#title' => $this->t('Parish'),
      '#options' => $parish_options,
      '#default_value' => '',
    ];
    $form['school_information']['group_left']['city'] = [
      '#type' => 'textfield',
      '#title' => $this->t('City'),
      '#default_value' => !empty($entity->city->value) ? $entity->city->value : '',
    ];
    $form['school_information']['group_right']['job_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Job Title'),
      '#default_value' => !empty($entity->job_title->value) ? $entity->job_title->value : '',
    ];
    $form['additional_information'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Additional Information'),
    ];

    $vocabulary_name = 'sub_roles';
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($vocabulary_name);
    // Populate options array for checkbox field.
    $options = [];
    foreach ($terms as $term) {
      // Check if the term is a top-level term.
      if ($term->depth == 0) {
        $options[$term->tid] = $term->name;
      }
    }
    $currentuserrole = $this->currentUser->getRoles();
    if (in_array('sales_staff', $currentuserrole)) {
      $roles = $this->entityTypeManager->getStorage('user_role')->loadMultiple();
      $exclude_roles = ['anonymous', 'authenticated', 'administrator'];
      $role_names = [];
      foreach ($roles as $role) {
        if (!in_array($role->id(), $exclude_roles)) {
          $role_names[$role->id()] = $role->label();
        }
      }
      $rolesoption = array_merge($options, $role_names);
      $options = array_unique($rolesoption);
    }
    $form['additional_information']['user_role'] = [
      '#title' => $this->t('Type of Account'),
      '#type' => 'select',
      '#options' => $options,
      '#prefix' => '<div class="fs-row m-bottom-4"><div class="fs-col-6">',
      '#suffix' => '</div></div>',
    ];

    $form['actions'] = [
      '#type' => 'actions',
      '#prefix' => '<div class="user-profile">',
      '#suffix' => '</div>',
      'cancel' => [
        '#type' => 'submit',
        '#value' => 'cancel changes',
        '#attributes' => [
          'class' => [
            'btn btn-secondary',
          ],
        ],
        '#submit' => ['::cancelForm'],
      ],
      'submit' => [
        '#type' => 'submit',
        '#value' => 'save',
        '#attributes' => [
          'class' => [
            'use-ajax d-inline-block',
          ],
        ],
      ],
    ];
    return $form;
  }

  /**
   * Ajax callback for Diocese select field.
   */
  public function getParish($form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * AJAX callback handler that displays any errors or a success message.
   */
  public function submitModalFormAjax(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    $response = new AjaxResponse();

    // If there are any form errors, re-display the form.
    if ($form_state->hasAnyErrors()) {
      $response->addCommand(new ReplaceCommand('#error_element', $form));
    }
    else {
      $response->addCommand(new ReplaceCommand('#error_element', $form));
    }
    return $response;
  }

  /**
   * Custom process function for Address Field.
   */
  public function customProcessAddress(array &$element, FormStateInterface $form_state, array &$complete_form) {
    return $element;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();
    // Check user_id key exist in array or not.
    if (array_key_exists('user_id', $values) == FALSE) {
      if (!empty($values['sub_role'])) {
        $sub_role = [];
        $sub_role_name = [];
        foreach ($values['sub_role'] as $role) {
          if ($role != '0') {
            $subroleload = $this->entityTypeManager->getStorage('taxonomy_term')->load($role);
            $sub_role_name[] = $subroleload->name->value;
            $sub_role[] = $role;
          }
        }
      }
      if (in_array('Teacher', $sub_role_name) && $values['sub_role_teacher'] == 0) {
        $form_state->setErrorByName('sub_role_teacher', $this->t('Please select teacher sub role'));
      }
      if (in_array('Catechist', $sub_role_name) && $values['sub_role_catechist'] == 0) {
        $form_state->setErrorByName('sub_role_catechist', $this->t('Please select catechist sub role'));
      }
    }

    if ($values['email'] != NULL && array_key_exists('user_id', $values) == FALSE) {
      $userquery = $this->entityTypeManager->getStorage('user')->getQuery()
        ->condition('name', $values['email'])
        ->accessCheck(FALSE);
      $user_ids = $userquery->execute();
      if (!empty($user_ids)) {
        $form_state->setErrorByName('email', $this->t('User already exist.'));
      }
    }
    if (!isset($values['user_id']) && strlen($form_state->getValue('confirm_password')) < 8 && array_key_exists('user_id', $values) == FALSE) {
      $form_state->setErrorByName('confirm_password', $this->t('Password Must be More Than 8 Characters'));
    }
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
    $values = $form_state->getValues();
    if (!empty($values)) {
      if (!empty($values['sub_role'])) {
        $sub_role = [];
        $sub_role_name = [];
        foreach ($values['sub_role'] as $role) {
          if ($role != '0') {
            $subroleload = $this->entityTypeManager->getStorage('taxonomy_term')->load($role);
            $sub_role_name[] = $subroleload->name->value;
            $sub_role[] = $role;
          }
        }
        if (in_array('Teacher', $sub_role_name) || in_array('Catechist', $sub_role)) {
          $sub_role_teacher = [];
          $sub_role_Catechist = [];
          if ($values['sub_role_teacher'] != '0') {
            $sub_role_teacher = explode(",", $values['sub_role_teacher']);
          }
          if ($values['sub_role_catechist'] != '0') {
            $sub_role_Catechist = explode(",", $values['sub_role_catechist']);
          }
          $sub_role = array_merge($sub_role, $sub_role_teacher, $sub_role_Catechist);
        }
      }
      if (isset($values['user_id']) && $values['user_id'] != NULL) {
        $user_role = $values['user_role'];
        $user = $this->entityTypeManager->getStorage('user')->load($values['user_id']);
        $user->set('mail', $form_state->getValue('email'));
        if (array_key_exists('confirm_password', $values) && $values['confirm_password'] != "") {
          $user->setPassword($values['confirm_password']);
        }
        if (!is_numeric($user_role) && !(int) $user_role == $user_role) {
          $user->addRole($user_role);
        }
        $user->save();
        if (is_numeric($user_role) && (int) $user_role == $user_role) {
          $user_role = [$user_role];
        }
        $custom_entity = $this->entityTypeManager->getStorage('user_profile_data');
        $query = $custom_entity->getQuery()
          ->condition('user_data', $values['user_id'])
          ->accessCheck(FALSE);
        $entity_ids = $query->execute();
        $address = [
          'country_code' => $values['address']['country_code'],
          'address_line1' => $values['address']['address_line1'],
          'address_line2' => $values['address']['address_line2'],
          'administrative_area' => $values['address']['administrative_area'],
          'postal_code' => $values['address']['postal_code'],
        ];

        if (!empty($entity_ids)) {
          $entity_id = reset($entity_ids);
          $custom_entity = $custom_entity->load($entity_id);
          if ($custom_entity) {
            // Update the entity properties.
            $custom_entity->set('sub_role', $user_role);
            $custom_entity->set('first_name', !empty($values['first_name']) ? $values['first_name'] : '');
            $custom_entity->set('last_name', !empty($values['last_name']) ? $values['last_name'] : '');
            $custom_entity->set('phone', !empty($values['phone']) ? $values['phone'] : '');
            $custom_entity->set('recovery_email_field', !empty($values['recover_email']) ? $values['recover_email'] : '');
            $custom_entity->set('school_name', !empty($values['school_name']) ? $values['school_name'] : '');
            $custom_entity->set('city', !empty($values['city']) ? $values['city'] : '');
            $custom_entity->set('job_title', !empty($values['job_title']) ? $values['job_title'] : '');
            $custom_entity->set('user_diocese_field', $values['diocese'] ?? NULL);
            $custom_entity->set('user_parish_field', $values['parish'] ?? NULL);
            $custom_entity->set('user_profile_address', $address ?? []);
            // Set other properties here.
            // Save the entity.
            $custom_entity->save();
          }
        }
        else {
          if (!empty($user)) {
            // Create the entity properties.
            $custom_entity = $this->entityTypeManager
              ->getStorage('user_profile_data')
              ->create([
                'user_data' => $user->id(),
                'sub_role' => $sub_role,
                'first_name' => !empty($values['first_name']) ? $values['first_name'] : '',
                'last_name' => !empty($values['last_name']) ? $values['last_name'] : '',
                'phone' => !empty($values['phone']) ? $values['phone'] : '',
                'recovery_email_field' => !empty($values['recover_email']) ? $values['recover_email'] : '',
                'school_name' => !empty($values['school_name']) ? $values['school_name'] : '',
                'city' => !empty($values['city']) ? $values['city'] : '',
                'job_title' => !empty($values['job_title']) ? $values['job_title'] : '',
                'user_diocese_field' => $values['diocese'] ?? NULL,
                'user_profile_address' => $address ?? [],
              // Set other properties here.
              ]);

            // Save the custom entity.
            $custom_entity->save();
          }
        }
        $this->messenger()->addMessage($this->t("User Update Successfully."));

      }
    }
  }

}
