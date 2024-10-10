<?php

declare(strict_types=1);

namespace Drupal\heart_custom_forms\Form;

use Drupal\commerce_price\Price;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\heart_custom_forms\HeartCustomService;
use Drupal\user\Entity\User;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a Heart Custom Forms form.
 */
final class RegistrationForm extends FormBase {

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
   * The date and time.
   *
   * @var Drupal\Core\Datetime\DrupalDateTime
   */
  protected $dateTime;

  /**
   * The Entity repo service.
   *
   * @var Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

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
   * @param Drupal\Core\Datetime\DrupalDateTime $date_time
   *   The date and time.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    CurrentPathStack $current_path,
    AccountInterface $current_user,
    RequestStack $requestStack,
    RouteMatchInterface $route_match,
    HeartCustomService $helper,
    TimeInterface $date_time,
    EntityRepositoryInterface $entityRepository
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentPath = $current_path;
    $this->currentUser = $current_user;
    $this->requestStack = $requestStack;
    $this->routeMatch = $route_match;
    $this->helper = $helper;
    $this->dateTime = $date_time;
    $this->entityRepository = $entityRepository;
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
      $container->get('heart_custom_forms.heart_custom_service'),
      $container->get('datetime.time'),
      $container->get('entity.repository')
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
    $path_param = $this->routeMatch->getParameter('id');
    $request = $request = $this->requestStack->getCurrentRequest();
    // Retrieve query parameters.
    $query_parameters = $request->query->all();
    // If you want a specific query parameter:
    $access_code = $request->query->get('access_code');

    // Attach js file to form.
    $form['#attached']['library'][] = 'heart_custom_forms/heart_custom_forms';

    $entity = [];
    $default_term = [];
    $default_teacher_term = [];
    $default_catechist_term = [];
    $required = TRUE;
    $form = [
      '#type' => 'markup',
      '#markup' => '<div class="m-bottom-5"><h1 class="m-0">' . $this->t('Register Here') . '</h1><p>' . $this->t('We are excited that you want to learn with us. Please let us know if you need any assistance setting up your account.') . '</p></div>',
    ];
    $form['#attributes']['class'][] = 'heart-custom-forms-user-registration-identifier';
    $form['#prefix'] = '<div id="error_element" class="wrapper-900">';
    $form['#suffix'] = '</div>';

    $triggering_element = $form_state->getTriggeringElement();
    $parish_options = ['' => $this->t('- Select Parish -')];
    if (!empty($triggering_element) && $triggering_element['#name'] == 'diocese') {
      // Call helper function to get parish based on diocese.
      $parish_options = $this->helper->getParishByDiocese($triggering_element['#value']);
    }

    $form['personal_information'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Personal Information'),
    ];
    if (isset($access_code) && $access_code != NULL) {
      $form['personal_information']['access_code'] = [
        '#type' => 'hidden',
        '#default_value' => $access_code,
      ];
    }

    $form['personal_information']['group_left'] = [
      '#prefix' => '<div class="fs-row"><div class="fs-col-6">',
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
    ];
    $form['personal_information']['group_left']['last_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Last Name'),
      '#required' => TRUE,

    ];
    $form['personal_information']['group_left']['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#required' => TRUE,
    ];
    $form['personal_information']['group_left']['recover_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Recovery Email Address'),
      '#description' => $this->t('<div class="text-secondary text-right">Backup Email Address</div>'),
    ];
    $form['personal_information']['group_right']['phone'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Phone'),
      '#attributes' => [
    // Allows digits and optional dashes.
        'pattern' => '[0-9-]+',
    // Tooltip for validation rule.
        'title' => $this->t('The phone number should only contain numbers and optional dashes.'),
    // Example placeholder.
        'placeholder' => $this->t('e.g. 123-456-7890 or 1234567890'),
    // Ensures a numeric keyboard on mobile devices.
        'inputmode' => 'numeric',
        'oninput' => "this.value = this.value.replace(/[^0-9-]/g, '');",
      ],
    ];
    // Current Password field.
    $form['personal_information']['group_right']['confirm_password'] = [
      '#type' => 'password_confirm',
      '#required' => $required,
      '#description' => '<div class="text-right"><a href="/password-policy" class="text-decoration-underline">' . $this->t('Password Policy') . '</a></div>',
    ];

    $form['school_information'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Church/School Information'),
      '#markup' => '<div class="text-secondary m-bottom-2">' . $this->t('info used to connect you to your location - reworded') . '</div>',
      '#prefix' => '<div id="school_information_fieldset">',
      '#suffix' => '</div>',
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
      '#default_value' => $default_parish ?? '',
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
        $term_name_translated = $this->entityRepository->getTranslationFromContext(
          $this->entityTypeManager->getStorage('taxonomy_term')->load($term->tid)
        )->getName();
        $options[$term->tid] = $this->t($term_name_translated);
      }
    }

    $form['additional_information']['sub_role'] = [
      '#type' => 'checkboxes',
      // '#title' => $this->t('I am . . . (you can check more than one)'),
      '#options' => $options,
      '#prefix' => '<div class="font-size-20 m-bottom-1 additional-information-label"><span class="fw-bold">I am . . .</span><span class="font-style-italic form-required"> (you can check more than one)</span></div><div class="additional-information m-bottom-3">',
      '#default_value' => !empty($default_term) ? $default_term : [19],
      '#required' => TRUE,
      '#attributes' => ['class' => ['additional-information-checkbox-select--wrapper']],

    ];

    $teacherchildoptions = [$this->t('-select-')];
    foreach ($terms as $term) {
      $parents = $term->parents;
      if (!in_array('0', $parents)) {
        $parent_term = $this->entityTypeManager->getStorage('taxonomy_term')->load($parents[0]);
        if ($term->depth == 1 && $parent_term->getName() == 'Teacher') {
          $term_name_translated = $this->entityRepository->getTranslationFromContext(
            $this->entityTypeManager->getStorage('taxonomy_term')->load($term->tid)
          )->getName();
          $teacherchildoptions[$term->tid] = $this->t($term_name_translated);
        }
      }
    }

    $catechistchildoptions = [$this->t('-select-')];
    foreach ($terms as $term) {
      $parents = $term->parents;
      if (!in_array('0', $parents)) {
        $parent_term = $this->entityTypeManager->getStorage('taxonomy_term')->load($parents[0]);
        if ($term->depth == 1 && $parent_term->getName() == 'Catechist') {
          $term_name_translated = $this->entityRepository->getTranslationFromContext(
            $this->entityTypeManager->getStorage('taxonomy_term')->load($term->tid)
          )->getName();
          $catechistchildoptions[$term->tid] = $this->t($term_name_translated);
        }
      }
    }

    $form['additional_information']['sub_role_teacher'] = [
      '#type' => 'select',
      '#options' => $teacherchildoptions,
      '#default_value' => !empty($default_teacher_term) ? $default_teacher_term : [],
    ];

    $form['additional_information']['sub_role_catechist'] = [
      '#type' => 'select',
      '#options' => $catechistchildoptions,
      '#default_value' => !empty($default_catechist_term) ? $default_catechist_term : [],
      '#suffix' => '</div>',
    ];

    $form['terms_condition'] = [
      '#type' => 'checkbox',
      '#required' => TRUE,
      '#title' => $this->t('<span class="font-size-12 font-style-italic">Check if you have read and agree to our <a class="m-left-2 text-decoration-underline" href="/terms-of-use">Terms of Use</a></span>'),
      '#attributes' => ['class' => ['terms-and-condition-checkbox']],
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('register'),
        '#states' => [
          'disable' => [
            // Use 'checked' instead of 'value'.
            ':input[name="terms_condition"]' => ['checked' => TRUE],
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
      // Create user.
      $user = User::create();
      $user->setPassword($form_state->getValue('confirm_password'));
      $user->enforceIsNew();
      $user->setEmail($form_state->getValue('email'));
      $user->setUsername($form_state->getValue('email'));
      $user->addRole('learner');
      $user->activate();
      $user->save();
      $uid = $user->id();
      if (!empty($uid)) {
        // Update the entity properties.
        $custom_entity = $this->entityTypeManager
          ->getStorage('user_profile_data')
          ->create([
            'user_data' => $uid,
            'sub_role' => $sub_role,
            'first_name' => !empty($values['first_name']) ? $values['first_name'] : '',
            'last_name' => !empty($values['last_name']) ? $values['last_name'] : '',
            'phone' => !empty($values['phone']) ? $values['phone'] : '',
            'recovery_email_field' => !empty($values['recover_email']) ? $values['recover_email'] : '',
            'school_name' => !empty($values['school_name']) ? $values['school_name'] : '',
            'city' => !empty($values['city']) ? $values['city'] : '',
            'job_title' => !empty($values['job_title']) ? $values['job_title'] : '',
            'user_diocese_field' => $values['diocese'] ?? NULL,
            // Set other properties here.
          ]);

        // Save the custom entity.
        $custom_entity->save();
        $profile_id = $custom_entity->id();
        $user->field_user_profile_reference = ['target_id' => $profile_id];
        $user->save();
        $this->messenger()->addMessage($this->t("Your account has been successfully created. Please check your email to confirm your account."));
        _user_mail_notify('register_no_approval_required', $user);
      }

      if (isset($values['access_code']) && $values['access_code'] != NULL) {
        $custom_entity = $this->entityTypeManager->getStorage('heart_access_code');
        $custom_entity = $custom_entity->loadByProperties(['label' => $values['access_code']]);
        $redeemCodeEntity = reset($custom_entity);
        $product_id = $redeemCodeEntity->course_field->target_id;
        if ($product_id != NULL) {
          // Create an entity query for Commerce products.
          $query = $this->entityTypeManager->getStorage('commerce_product')->getQuery();
          // Add a condition to filter by the field_course_product.
          $query->condition('field_course_product', $product_id);
          $query->accessCheck(FALSE);
          // Execute the query to get the entity IDs.
          $entity_ids = $query->execute();
          if (!empty($entity_ids)) {
            $entity_id = reset($entity_ids);

            // Load the products using the entity IDs.
            $product = $this->entityTypeManager->getStorage('commerce_product')->load($entity_id);
            $product_entity_bundle = $product->bundle();
            $variations = $product->getVariations();
            $variation_id = $variations[0]->id();
            // Load the product variation.
            $variation = $this->entityTypeManager->getStorage('commerce_product_variation')->load($variation_id);

            // Create an order item and save it.
            $order_item = $this->entityTypeManager
              ->getStorage('commerce_order_item')
              ->create([
                'type' => 'default',
                'purchased_entity' => $variation,
                'quantity' => 1,
                'field_complementary' => 1,
                'field_product_type' => $product_entity_bundle,
                'field_product' => $product,
                'unit_price' => new Price('0', 'USD'),
                'overridden_unit_price' => TRUE,
              ]);

            // Set the product title explicitly.
            $order_item->set('title', $product->getTitle());
            $order_item->save();

            // Create order and attach the previous order item generated.
            $order = $this->entityTypeManager
              ->getStorage('commerce_order')
              ->create([
                'type' => 'default',
                'mail' => $user->getEmail(),
                'uid' => $user->id(),
                'store_id' => 1,
                'order_items' => [$order_item],
                'placed' => $this->dateTime->getCurrentTime(),
                'payment_gateway' => '',
                'checkout_step' => 'complete',
                'state' => 'completed',
              ]);

            // Add order number and save (based on order id).
            $order->set('order_number', $order->id());
            $order->save();

            $current_datetime = new DrupalDateTime();
            $redeemCodeEntity->set('consumed_date', $current_datetime->format('Y-m-d\TH:i:s'));
            $redeemCodeEntity->set('consumed', TRUE);
            $redeemCodeEntity->set('user_id', $user->id());
            $redeemCodeEntity->save();
          }
        }
      }
    }
  }

}
