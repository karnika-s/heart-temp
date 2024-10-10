<?php

declare(strict_types=1);

namespace Drupal\heart_custom_forms\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\heart_custom_forms\HeartCustomService;
use Drupal\heart_misc\EmailTemplateService;
use Drupal\heart_misc\SendMailService;
use Drupal\heart_user_data\UserRefData;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Heart Custom Forms form.
 */
final class ClassSetupForm extends FormBase {

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
   * The user reference data creation.
   *
   * @var \Drupal\heart_user_data\UserRefData
   */
  protected $userRefData;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The Email template Service.
   *
   * @var Drupal\heart_misc\EmailTemplateService
   */
  protected $emailTemplateService;

  /**
   * The Send Mail Service.
   *
   * @var Drupal\heart_misc\SendMailService
   */
  protected $sendMailService;

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
   * @param \Drupal\heart_user_data\UserRefData $userRefData
   *   The user reference data creation.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   * @param \Drupal\heart_misc\EmailTemplateService $email_template_service
   *   The Email Template  Service.
   * @param \Drupal\heart_misc\SendMailService $send_mail_service
   *   The Send Mail  Service.
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
    UserRefData $userRefData,
    MailManagerInterface $mail_manager,
    EmailTemplateService $email_template_service,
    SendMailService $send_mail_service,
    LanguageManagerInterface $language_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentPath = $current_path;
    $this->currentUser = $current_user;
    $this->routematch = $route_match;
    $this->message = $message;
    $this->helper = $helper;
    $this->userRefData = $userRefData;
    $this->mailManager = $mail_manager;
    $this->emailTemplateService = $email_template_service;
    $this->sendMailService = $send_mail_service;
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
      $container->get('heart_user_data.user_ref_data'),
      $container->get('plugin.manager.mail'),
      $container->get('heart_misc.email_template_service'),
      $container->get('heart_misc.send_mail_service'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'heart_custom_forms_class_setup_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['#prefix'] = '<div id="class-setup-form">';
    $form['#suffix'] = '</div>';
    // Get the current language.
    $current_language = $this->languageManager->getCurrentLanguage()->getId();
    // Get trigger element after ajax.
    $triggering_element = $form_state->getTriggeringElement();
    $currentUserId = $this->currentUser->id();
    $custom_entity = $this->entityTypeManager->getStorage('user_profile_data');
    $query = $custom_entity->getQuery()
      ->condition('user_data', $currentUserId)
      ->accessCheck(FALSE);
    $entity_ids = $query->execute();
    if (!empty($entity_ids)) {
      $userProfileId = reset($entity_ids);
    }
    $course_option = ['- Select Class -'];
    $currentUserRole = $this->currentUser->getRoles();
    if (in_array('parish_admin', $currentUserRole)) {
      $hideclass = 'visually-hidden';
      $diocese_options = $this->helper->getParishAdminDiocese($userProfileId);
      if (!empty($diocese_options)) {
        $diocese_keys = array_keys($diocese_options);
        $default_diocese = $diocese_keys[1];
        $form_state->set('default_diocese', $default_diocese);
        $parish_options = $this->helper->getParishAdminParish($userProfileId, $default_diocese);
        $form_state->set('parish_options', $parish_options);
        if (!empty($parish_options)) {
          $parish_keys = array_keys($parish_options);
          $default_parish = $parish_keys[1];
          $form_state->set('default_parish', $default_parish);
          // Call helper function to get course based on Parish.
          $course_option = $this->helper->getCourseProductByParish($default_diocese, $default_parish);
          $form_state->set('course_option', $course_option);
        }
      }
    }

    if (in_array('diocesan_admin', $currentUserRole)) {

      $hideclass = 'visually-hidden';
      $diocese_options = $this->helper->getDioceseAdminDiocese($userProfileId);
      if (!empty($diocese_options)) {
        $diocese_keys = array_keys($diocese_options);
        $default_diocese = $diocese_keys[1];
        $form_state->set('default_diocese', $default_diocese);
        $parish_options = [];
        $default_parish = '';
        $form_state->set('default_parish', $default_parish);
        $course_option = $this->helper->getCourseProductByDiocese($default_diocese);
        $form_state->set('course_option', $course_option);
      }
    }

    if (in_array('administrator', $currentUserRole) || in_array('consultant', $currentUserRole)) {

      $parish_options = ['' => '- Select Parish -'];
      $hideclass = '';
      $diocese_options = $this->helper->getDioceseName();
      $default_diocese = '';
      $default_parish = '';
      $parish_options = $form_state->get('parish_options');
      $course_option = $form_state->get('course_option');
      // Check triggered element if triggered element is
      // diocese select then this condition apply.
      if (!empty($triggering_element) && $triggering_element['#name'] == 'field_diocese') {

        // Call helper function to get parish based on diocese.
        $parish_options = $this->helper->getParishByDiocese($triggering_element['#value']);
        $form_state->set('parish_options', $parish_options);
        // Call helper function to get course based on diocese.
        $course_option = $this->helper->getCourseProductByDiocese($triggering_element['#value']);
        $form_state->set('course_option', $course_option);
        $form_state->set('license_quantity_available', '');
        $form_state->set('licenses_applied_to_class', '');
        $fieldsToReset = [
          'licenses_applied',
          'add_licenses',
        ];
      }

      if (!empty($triggering_element) && $triggering_element['#name'] == 'field_parish') {
        $values = $form_state->getValues();
        $form_state->set('course_option', []);
        // Call helper function to get course based on Parish.
        $parish_options = $this->helper->getParishByDiocese($values['field_diocese']);
        $form_state->set('parish_options', $parish_options);
        $course_option = $this->helper->getCourseProductByParish($values['field_diocese'], $triggering_element['#value']);
        $form_state->set('course_option', $course_option);
        $form_state->set('license_quantity_available', '');
        $form_state->set('licenses_applied_to_class', '');
        $fieldsToReset = [
          'licenses_applied',
          'add_licenses',
        ];
      }
    }
    if (!empty($triggering_element) && $triggering_element['#name'] == 'select_class') {
      $values = $form_state->getValues();
      // Call helper service to get license quantity.
      $parish_options = $form_state->get('parish_options');
      $course_option = $form_state->get('course_option');
      $default_parish = $form_state->get('default_parish');
      $default_diocese = $form_state->get('default_diocese');
      if ($default_parish != '') {
        $parish = $default_parish;
      }
      else {
        $parish = $values['field_parish'];
      }
      if ($default_diocese != '') {
        $diocese = $default_diocese;
      }
      else {
        $diocese = $values['field_diocese'];
      }
      $license_number = $this->helper->getCourseProductLicenseQuantity($triggering_element['#value'], $diocese, $parish);
      $license_available = reset($license_number);
      $license_quantity_available = $license_available->license_quantity_available;
      if ($license_quantity_available != '') {
        $form_state->set('license_quantity_available', $license_quantity_available);
      }
      else {
        $form_state->set('license_quantity_available', '0');
      }
      $form_state->set('licenses_applied_to_class', '');
      $fieldsToReset = [
        'licenses_applied',
        'add_licenses',
      ];
    }

    if (!empty($triggering_element) && isset($triggering_element['#attributes']['name']) && $triggering_element['#attributes']['name'] == 'add_license_button') {
      $values = $form_state->getValues();
      // Check if user add license first time to class.
      if ($form_state->get('licenses_applied_to_class') == '') {
        $licenses_applied_to_class = $values['add_licenses'];
        $form_state->set('licenses_applied_to_class', $licenses_applied_to_class);
        $license_available = $form_state->get('license_quantity_available');
        $license_quantity_available = $license_available - $licenses_applied_to_class;
        $form_state->set('license_quantity_available', $license_quantity_available);
      }
      else {
        $licenses_applied_to_class = $values['add_licenses'] + $form_state->get('licenses_applied_to_class');
        $form_state->set('licenses_applied_to_class', $licenses_applied_to_class);
        $license_available = $form_state->get('license_quantity_available');
        $license_quantity_available = $license_available - $values['add_licenses'];
        $form_state->set('license_quantity_available', $license_quantity_available);
      }
      $fieldsToReset = [
        'licenses_applied',
      ];
    }
    // Unset field value.
    if (!empty($fieldsToReset)) {
      $input = &$form_state->getUserInput();
      foreach ($fieldsToReset as $fieldName) {
        if (isset($input[$fieldName])) {
          unset($input[$fieldName]);
        }
        $form_state->unsetValue($fieldName);
      }
    }

    $form['field_diocese'] = [
      '#type' => 'select',
      '#chosen' => TRUE,
      '#title' => $this->t('Diocese'),
      '#options' => $diocese_options ?? [],
      '#default_value' => $default_diocese ?? '',
      '#required' => TRUE,
      // Ajax callback to get parish on the basis of diocese.
      '#ajax' => [
        'callback' => [$this, 'getLicenses'],
        'wrapper' => 'class-setup-form',
        'event' => 'change',
      ],
      '#prefix' => '<div class=' . $hideclass . '>',
      '#suffix' => '</div>',
    ];

    $form['field_parish'] = [
      '#type' => 'select',
      '#chosen' => TRUE,
      '#title' => $this->t('Parish'),
      '#options' => $parish_options ?? [],
      '#default_value' => $default_parish ?? '',
      '#prefix' => '<div class=' . $hideclass . '>',
      '#suffix' => '</div>',
      '#ajax' => [
        'callback' => [$this, 'getLicenses'],
        'wrapper' => 'class-setup-form',
        'event' => 'change',
      ],
    ];

    $form['select_class'] = [
      '#type' => 'select',
      '#chosen' => TRUE,
      '#title' => $this->t('Select Class'),
      '#options' => $course_option ?? [],
      '#required' => TRUE,
      // Ajax call to get available licenses.
      '#ajax' => [
        'callback' => [$this, 'getLicenses'],
        'wrapper' => 'class-setup-form',
        'event' => 'change',
      ],
    ];

    $form['class_identifier'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Assign Class Identifier'),
      '#required' => TRUE,
      '#attributes' => ['class' => ['input-width-280']],
    ];

    $form['invite_facilitator'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Invite Facilitator'),
      '#description' => $this->t('Sends invite after class is created.'),
      '#attributes' => ['class' => ['input-width-280']],
    ];

    $license_quantity_available = $form_state->get('license_quantity_available') ?? '';
    $licenses_applied_to_class = $form_state->get('licenses_applied_to_class') ?? '';
    $form['classinfo'] = [
      '#type' => 'markup',
      '#markup' => '
            <div class="rcl-text">
              <p class="text-primary font-size-12">' . $this->t('Licenses Applied to this class:') . ' <span class="d-inline-block p-left-2">' . $licenses_applied_to_class . '</span></p>
              <p class="text-secondary font-size-12">' . $this->t('Licenses Available:') . '<span class="d-inline-block p-left-2">' . $license_quantity_available . '</span></p>
            </div>',
    ];

    $form['licenses_applied'] = [
      '#type' => 'textfield',
      '#default_value' => $licenses_applied_to_class,
      '#attributes' => ['class' => ['visually-hidden']],
    ];

    $form['add_licenses'] = [
      '#type' => 'textfield',
      // '#title' => $this->t('Add Licenses to Class'),
    ];

    $form['add_license_button'] = [
      '#type' => 'button',
      '#value' => $this->t('Add to class'),
      // Added ajax callback to add license to class.
      '#ajax' => [
        'callback' => [$this, 'getLicenses'],
        'wrapper' => 'class-setup-form',
        'event' => 'click',
      ],
      '#attributes' => [
        'name' => 'add_license_button',
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'cancel' => [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#attributes' => [
        'class' => ['btn', 'btn-secondary'],
      ],
      '#submit' => ['::cancelForm'],
      '#limit_validation_errors' => [],
      ],
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Create Class'),
      ],
    ];

    $form['#theme'] = 'class_setup';
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    // Get the current language.
    $current_language = $this->languageManager->getCurrentLanguage()->getId();
    $values = $form_state->getValues();
    $invite_email = $values['invite_facilitator'];
    $triggering_element = $form_state->getTriggeringElement();
    // User cannot add more than the available licenses validation.
    if (!empty($triggering_element) && isset($triggering_element['#attributes']['name']) && $triggering_element['#attributes']['name'] == 'add_license_button') {
      $values = $form_state->getValues();
      if ($values['add_licenses'] == '') {
        $form_state->setErrorByName('add_licenses', $this->t('Please add licenses.'));
      }
      if ($values['add_licenses'] > $form_state->get('license_quantity_available')) {
        $form_state->setErrorByName('add_licenses', $this->t('You cannot add more than the available licenses.'));
      }
    }
    if ($values['select_class'] == '') {
      $form_state->setErrorByName('select_class', $this->t('Please select class.'));
    }
    if ($values['select_class'] != '') {
      $class_entity = $this->entityTypeManager->getStorage('heart_class');
      $query = $class_entity->getQuery();
      $query->condition('langcode', $current_language);
      $query->condition('course_field', $values['select_class']);
      if ($values['field_parish'] != '') {
        $query->condition('diocese_field', $values['field_diocese']);
        $query->condition('parish_field', $values['field_parish']);
        $query->condition('class_identifier', $values['class_identifier']);
      }
      else {
        $query->condition('diocese_field', $values['field_diocese']);
        $query->condition('class_identifier', $values['class_identifier']);
      }
      $query->accessCheck(FALSE);
      $entity_ids = $query->execute();
      if (!empty($entity_ids)) {
        if ($values['parish'] == '') {
          $form_state->setErrorByName('select_class', $this->t('The Class Identifier is already in use for this diocese.'));
        }
        else {
          $form_state->setErrorByName('select_class', $this->t('The Class Identifier is already in use for this diocese.'));
        }
      }
    }
    if (!empty($invite_email)) {
      $email = trim($invite_email);
      if (!empty($email)) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
          $form_state->setError($form['invite_facilitator'], $this->t('Please enter a valid email.'));
        }

        $users = $this->entityTypeManager->getStorage('user')->loadByProperties(['mail' => $email]);
        $user = reset($users);
        if (empty($user)) {
          $form_state->setError($form['invite_facilitator'], $this->t('User with email address @email does not exist, Please contact IT team.', ['@email' => $email]));
        }
      }
    }
  }

  /**
   * Ajax callback for Diocese select field.
   */
  public function getLicenses($form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Get the current language.
    $current_language = $this->languageManager->getCurrentLanguage()->getId();
    $values = $form_state->getValues();
    if (!empty($values)) {
      // Create heart license entity.
      $course = $this->entityTypeManager->getStorage('course_product')->load($values['select_class']);
      $class_entity = $this->entityTypeManager->getStorage('heart_class')->create([
        'label' => $course->product_title,
        'course_field' => $values['select_class'],
        'class_identifier' => $values['class_identifier'],
        'licenses_available' => $values['licenses_applied'],
        'diocese_field' => $values['field_diocese'],
        'parish_field' => $values['field_parish'],
        'status' => TRUE,
        'langcode' => $current_language,
      ]);
      $class_entity->save();
      $class_id = $class_entity->id();
      $license_entity = $this->entityTypeManager->getStorage('heart_license');
      $query = $license_entity->getQuery();
      $query->condition('course_field', $values['select_class']);
      $query->condition('langcode', $current_language);
      $query->accessCheck(FALSE);
      $entity_ids = $query->execute();
      if (!empty($entity_ids)) {
        $course_license = reset($entity_ids);
        // Update count of available license in lisence entity.
        $course_license = $this->entityTypeManager->getStorage('heart_license')->load($course_license);
        if ($course_license->hasTranslation($current_language)) {
          $course_license = $course_license->getTranslation($current_language);
        }
        else {
          $course_license = $course_license->addTranslation($current_language);
        }
        $available_license = $course_license->get('license_quantity_available')->value;
        $now_license = intval($available_license) - intval($values['licenses_applied']);
        $course_license->set('license_quantity_available', $now_license);
        $course_license->save();
      }
      $this->message->addMessage('Class added successfully.');
      $email = trim($values['invite_facilitator']);
      if (!empty($email)) {
        $license_used = $class_entity->licenses_used->value;
        $class_entity->set('licenses_used', intval($license_used) + 1);
        $users = $this->entityTypeManager->getStorage('user')->loadByProperties(['mail' => $email]);
        $user = reset($users);
        $custom_entity = $this->entityTypeManager->getStorage('user_profile_data');
        $query = $custom_entity->getQuery()
          ->condition('user_data', $user->uid->value)
          ->accessCheck(FALSE);
        $entity_ids = $query->execute();
        $user_id = '';
        if (!empty($entity_ids)) {
          $user_id = reset($entity_ids);
          $user_profile = $this->entityTypeManager->getStorage('user_profile_data')->load($user_id);

          $user_name = $user_profile->first_name->value . ' ' . $user_profile->last_name->value;
        }
        else {
          $user_name = $user->name->value;
        }

        // Generate accept and reject links.
        $accept_link = Url::fromRoute(
          'heart_custom_forms.accept_invite',
          ['user_id' => $user->uid->value, 'class_id' => $class_id],
          [
            'absolute' => TRUE,
            'query' => ['type' => 'teacher'],
          ],
        );
        $reject_link = Url::fromRoute(
          'heart_custom_forms.reject_invite',
          ['user_id' => $user->uid->value, 'class_id' => $class_id],
          ['absolute' => TRUE]
        );

        // Get email template ids.
        $email_template_entity_id_submitter = $this->emailTemplateService->emailTemplateIdsByTermName('Invitation to Join a Class: Facilitator');
        if ($email_template_entity_id_submitter) {

          // Prepare the template variables.
          $translate = [
            '[User]' => $user_name,
            '[Class Title]' => $class_entity->label(),
            '[Accept Invitation Link]' => $accept_link->toString(),
          ];
          $this->sendMailService->heartSendMail($email_template_entity_id_submitter, $translate, $email);
        }

        // Check class invitation entity is already present.
        $class_invitation_entity = $this->entityTypeManager->getStorage('class_invitation');
        $query = $class_invitation_entity->getQuery()
          ->condition('class_reference', $class_id)
          ->condition('invited_user', $user_id)
          ->accessCheck(FALSE);
        $entity_ids = $query->execute();
        if (empty($entity_ids)) {
          // Create new class invitation entity.
          $invitation_entity = $this->entityTypeManager
            ->getStorage('class_invitation')
            ->create([
              'label' => 'Class Invitation ' . $class_id,
              'class_reference' => $class_id ?? '',
              'invitation_type' => 'teacher',
              'invitation_status' => 'pending',
              'status' => TRUE,
            ]);
          $invitation_entity->invited_user = ['target_id' => $user_id];
          $invitation_entity->save();
        }
      }
    }
  }

  public function cancelForm(array &$form, FormStateInterface $form_state) {
    $form_state->setRedirect('<front>'); // Redirect to homepage
  }

}
