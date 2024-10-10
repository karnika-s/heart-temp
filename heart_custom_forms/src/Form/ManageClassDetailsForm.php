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
final class ManageClassDetailsForm extends FormBase {

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
   * Custom Helper service.
   *
   * @var \Drupal\heart_custom_forms\HeartCustomService
   */
  protected $helper;

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
   * @param \Drupal\heart_user_data\UserRefData $userRefData
   *   The user reference data creation.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   * @param Drupal\heart_custom_forms\HeartCustomService $helper
   *   The custom helper service.
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
    UserRefData $userRefData,
    MailManagerInterface $mail_manager,
    HeartCustomService $helper,
    EmailTemplateService $email_template_service,
    SendMailService $send_mail_service,
    LanguageManagerInterface $language_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentPath = $current_path;
    $this->currentUser = $current_user;
    $this->routematch = $route_match;
    $this->message = $message;
    $this->userRefData = $userRefData;
    $this->mailManager = $mail_manager;
    $this->helper = $helper;
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
      $container->get('heart_user_data.user_ref_data'),
      $container->get('plugin.manager.mail'),
      $container->get('heart_custom_forms.heart_custom_service'),
      $container->get('heart_misc.email_template_service'),
      $container->get('heart_misc.send_mail_service'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'manage_class_details_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $current_user = \Drupal::currentUser();
    $roles = $current_user->getRoles();
    $role_class = '';

    if (in_array('facilitator', $roles)) {
      $role_class = 'facilitator-manage-class-details';
    }
    elseif ((in_array('parish_admin', $roles)) && (in_array('diocesan_admin', $roles))) {
      $role_class = 'diocesan_admin-manage-class-details';
    }
    elseif (in_array('diocesan_admin', $roles)) {
      $role_class = 'diocesan_admin-manage-class-details';
    } elseif (in_array('parish_admin', $roles)) {
      $role_class = 'parish_admin-manage-class-details';
    }
    elseif (in_array('content_editor', $roles)) {
      $role_class = 'content_editor-manage-class-details';
    }
    elseif (in_array('sales_staff', $roles)) {
      $role_class = 'sales_staff-manage-class-details';
    }
    elseif (in_array('consultant', $roles)) {
      $role_class = 'consultant-manage-class-details';
    } else {
      $role_class = 'manage-class-details';
    }

    $form['#prefix'] = '<div id="manage-class-details-form" class="' . $role_class . '">';
    $form['#suffix'] = '</div>';
    $form['#attached']['library'][] = 'heart_custom_forms/heart_custom_forms';
    // Get the current language code using the injected language manager.
    $current_language_code = $this->languageManager->getCurrentLanguage()->getId();
    // dump($current_language_code);exit;
    $form['choose_another_class_button'] = [
      '#type' => 'button',
      '#value' => $this->t('Choose Another Class'),
      '#prefix' => '<div class="position-relative">',
      '#suffix' => '</div>',
      '#attributes' => [
        'name' => 'send_invite_button',
        'class' => ['choose-another-class btn btn-secondary btn-position btn-position-0'],
      ],
    ];
    // Add a container to display error messages.
    $form['form_errors'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'form-errors'],
    ];
    $form['form_message'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'form-message'],
    ];
    $class_id = $this->routematch->getParameter('id');
    if ($class_id !== '') {
      $class = $this->entityTypeManager->getStorage('heart_class')->load($class_id);
      $facilitator = $class->get('invite_facilitator')->target_id;
      $faciname = '';
      if ($facilitator != NULL) {
        $user_profile = $this->entityTypeManager->getStorage('user_profile_data')->load($facilitator);
        if (!empty($user_profile)) {
          $faciname = $user_profile->first_name->value . ' ' . $user_profile->last_name->value;
        }
      }
      if (!empty($class)) {
        $class_label = $this->t($class->label->value);
        $form['class_heading'] = [
          '#type' => 'markup',
          '#markup' => '<h2>' . $class_label . '</h2>',
        ];
        $facilitator_text = $this->t('Facilitator');
        $license_used_text = $this->t(' licenses used');
        $form['class_markup'] = [
          '#type' => 'markup',
          '#markup' => '<div class="m-bottom-3"><span class="font-size-15">' . $facilitator_text . ' :' . $faciname . ' </span><span class="font-size-15 text-secondary">' . $class->class_identifier->value . '</span><span class="text-primary m-left-2">' . $class->licenses_used->value . ' of ' . $class->licenses_available->value . $license_used_text . '</span></div>',
        ];
        $form['wrapper'] = [
          '#prefix' => '<div class="main_wrapper">',
          '#suffix' => '</div>',
        ];

        $currentUser = $this->currentUser->getRoles();
        if (
          in_array('facilitator', $currentUser) || in_array('administrator', $currentUser) ||
          in_array('sales_staff', $currentUser) || in_array('consultant', $currentUser)
        ) {
          $form['wrapper']['wrapper_left'] = [
            '#prefix' => '<div class="wrapper_left">',
            '#suffix' => '</div>',
          ];
          $invite_learner = $this->t('Invite Learner(s)');
          $form['wrapper']['wrapper_left']['invite_markup'] = [
            '#type' => 'markup',
            '#markup' => '<div class="font-size-15 m-bottom-1">' . $invite_learner . '</div>',
          ];
          // Add an upload field.
          $form['wrapper']['wrapper_left']['invite_emails'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Email Address(es)'),
            '#placeholder' => $this->t('to invite multiple people at once, separate with a ";"'),
            '#prefix' => '<div class="form-inline form-class-setup form-inline-manage-class m-bottom-2 position-relative">',
          ];

          $form['wrapper']['wrapper_left']['send_learner_invite_button'] = [
            '#type' => 'submit',
            '#value' => $this->t('Send Learner Invite(s)'),
            // Added ajax callback to add license to class.
            '#ajax' => [
              'callback' => [$this, 'ajaxSubmitCallback'],
              'wrapper' => 'manage-class-details-form',
              'event' => 'click',
            ],
            '#attributes' => [
              'name' => 'send_invite_button',
              'class' => ['btn-position btn-position-0'],
            ],
            '#suffix' => '</div>',
          ];
          // Add sample document.
          $form['wrapper']['wrapper_left']['sample_document'] = [
            '#type' => 'link',
            '#title' => $this->t('Download multiple user Excel Document'),
            '#url' => Url::fromUri('internal:/modules/custom/heart_diocese/sample_files/SampleClassFacilatator.csv'),
            '#attributes' => [
              'download' => 'sample_document.csv',
              'class' => 'text-secondary',
            ],
            '#options' => [
              'attributes' => ['target' => '_blank'],
            ],
            '#prefix' => '<div class="form-inline form-class-setup form-inline-manage-class m-bottom-2 position-relative"><div class="form-item"><span class="d-inline-block label"></span><div class="form-element-item">',
            '#suffix' => '</div></div></div>',
          ];
          $form['wrapper']['wrapper_left']['class_learners_invite_upload'] = [
            '#type' => 'managed_file',
            '#upload_location' => 'public://uploads/excel',
            '#upload_validators' => [
              'file_validate_extensions' => ['csv'],
            ],
            '#default_value' => $this->config('heart_diocese.settings')->get('class_learners_list_upload'),
          ];
        }
        if (
          in_array('diocesan_admin', $currentUser) || in_array('parish_admin', $currentUser) ||
          in_array('administrator', $currentUser) || in_array('sales_staff', $currentUser) || in_array('consultant', $currentUser)
        ) {
          $form['wrapper']['wrapper_right'] = [
            '#prefix' => '<div class="wrapper_right">',
            '#suffix' => '</div>',
          ];
          $invite_facilitator = $this->t('Invite Facilitator(s)');
          $form['wrapper']['wrapper_right']['invite_markup_facilitator'] = [
            '#type' => 'markup',
            '#markup' => '<div class="font-size-15 m-bottom-1">' . $invite_facilitator . '</div>',
          ];
          // Add an upload field facilitator.
          $form['wrapper']['wrapper_right']['invite_emails_facilitator'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Email Address(es)'),
            '#placeholder' => $this->t('to invite multiple people at once, separate with a ";"'),
            '#prefix' => '<div class="form-inline form-class-setup form-inline-manage-class m-bottom-2 position-relative">',
          ];
          $form['wrapper']['wrapper_right']['send_facilitator_invite_button'] = [
            '#type' => 'submit',
            '#value' => $this->t('Send Facilitator Invite(s)'),
            // Added ajax callback to add license to class.
            '#ajax' => [
              'callback' => [$this, 'ajaxSubmitCallback'],
              'wrapper' => 'manage-class-details-form',
              'event' => 'click',
            ],
            '#attributes' => [
              'name' => 'send_invite_facilitator_button',
              'class' => ['btn-position btn-position-0'],
            ],
            '#suffix' => '</div>',
          ];
          // Add sample document facilitator.
          $form['wrapper']['wrapper_right']['sample_document_facilitator'] = [
            '#type' => 'link',
            '#title' => $this->t('Download multiple user Excel Document'),
            '#url' => Url::fromUri('internal:/modules/custom/heart_diocese/sample_files/SampleClassFacilatator.csv'),
            '#attributes' => [
              'download' => 'sample_document.csv',
              'class' => 'text-secondary',
            ],
            '#options' => [
              'attributes' => ['target' => '_blank'],
            ],
            '#prefix' => '<div class="form-inline form-class-setup form-inline-manage-class"><div class="form-item"><span class="d-inline-block label"></span><div class="form-element-item">',
            '#suffix' => '</div></div></div>',
          ];
          $form['wrapper']['wrapper_right']['class_facilitator_invite_upload'] = [
            '#type' => 'managed_file',
            '#upload_location' => 'public://uploads/excel',
            '#upload_validators' => [
              'file_validate_extensions' => ['csv'],
            ],
            '#default_value' => $this->config('heart_diocese.settings')->get('class_facilator_list_upload'),

          ];
        }

        // Hidden field for class id.
        $form['class_id'] = [
          '#type' => 'hidden',
          '#default_value' => $class_id,
        ];
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();
    $triggering_element = $form_state->getTriggeringElement();
    if (
      !empty($triggering_element) && isset($triggering_element['#attributes']['name']) &&
      $triggering_element['#attributes']['name'] == 'send_invite_button'
    ) {
      $invite_emails = $form_state->getValue('invite_emails');
      $invite_emails_doc = $form_state->getValue('class_learners_invite_upload');
      $upload_field = 'class_learners_invite_upload';
      $email_field = 'invite_emails';
    }
    elseif (
      !empty($triggering_element) && isset($triggering_element['#attributes']['name']) &&
      $triggering_element['#attributes']['name'] == 'send_invite_facilitator_button'
    ) {
      $invite_emails = $form_state->getValue('invite_emails_facilitator');
      $invite_emails_doc = $form_state->getValue('class_facilitator_invite_upload');
      $upload_field = 'class_facilitator_invite_upload';
      $email_field = 'invite_emails_facilitator';
    }
    $class_id = $form_state->getValue('class_id');
    $class = $this->entityTypeManager->getStorage('heart_class')->load($class_id);
    $license_left = intval($class->licenses_available->value) - intval($class->licenses_used->value);
    if (
      !empty($triggering_element) && isset($triggering_element['#attributes']['name']) &&
      $triggering_element['#attributes']['name'] == 'send_invite_button' || !empty($triggering_element) && isset($triggering_element['#attributes']['name']) &&
      $triggering_element['#attributes']['name'] == 'send_invite_facilitator_button'
    ) {
      if (empty($invite_emails) && empty($invite_emails_doc)) {
        if (
          !empty($triggering_element) && isset($triggering_element['#attributes']['name']) &&
          $triggering_element['#attributes']['name'] == 'send_invite_button'
        ) {
          $form_state->setErrorByName('invite_emails', $this->t('Please enter an email address or upload an Excel document.'));
        }
        if (
          !empty($triggering_element) && isset($triggering_element['#attributes']['name']) &&
          $triggering_element['#attributes']['name'] == 'send_invite_facilitator_button'
        ) {
          $form_state->setErrorByName('invite_emails_facilitator', $this->t('Please enter an email address or upload an Excel document.'));
        }
      }
      if (!empty($invite_emails_doc)) {

        // Validate uploaded file.
        // $file = $form_state->getValue('class_learners_invite_upload');.
        $file = $invite_emails_doc;
        if (!empty($file)) {

          // Load the file entity.
          /** @var \Drupal\file\Entity\FileInterface $file_entity */
          $file_entity = $this->entityTypeManager->getStorage('file')->load($file[0]);
          $file_path = $file_entity->getFileUri();
          $headers = [
            'Emails',
          ];

          // File must have proper headers.
          $row = 0;
          if (($handle = fopen($file_path, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
              if ($row == 0) {
                if ($headers !== $data) {
                  $form_state->setErrorByName($upload_field, $this->t('Invalid header row in the uploaded file.'));
                }
              }
              else {
                $emails[] = $data[0];
                // Check if email is valid and user exists.
                if (!filter_var($data[0], FILTER_VALIDATE_EMAIL)) {
                  $form_state->setError($form[$upload_field], $this->t('Email address @email is not valid.', ['@email' => $data[0]]));
                }
                $users = $this->entityTypeManager->getStorage('user')->loadByProperties(['mail' => $data[0]]);
                $user = reset($users);

                if (empty($user)) {
                  $form_state->setError($form[$upload_field], $this->t('User with email address @email does not exist, Please contact IT team.', ['@email' => $data[0]]));
                }
                else {
                  $uid = $user->id();
                  $getclassfacilator = $this->userRefData->userRefDataGet($uid, 'heart_class', 'heart_class', $class_id);
                  if (!empty($getclassfacilator)) {
                    $form_state->setError($form[$upload_field], $this->t('User with email address @email is already a Facilator of this class.', ['@email' => $data[0]]));
                  }
                }
              }
              $row++;
            }
            if (count($emails) > $license_left) {
              $form_state->setError($form[$upload_field], $this->t('You can not invite user more than license available.'));
            }
            fclose($handle);
          }
        }
      }

      if (!empty($invite_emails)) {
        $emails = explode(';', $invite_emails);
        foreach ($emails as $email) {
          if (!empty($email)) {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
              $form_state->setError($form[$email_field], $this->t('Please enter a valid email address or check the separator.'));
            }

            $users = $this->entityTypeManager->getStorage('user')->loadByProperties(['mail' => $email]);
            $user = reset($users);
            if (empty($user)) {
              $form_state->setError($form[$email_field], $this->t('User with email address @email does not exist, Please contact IT team.', ['@email' => $email]));
            }
            else {
              $uid = $user->id();
              $getclassfacilator = $this->userRefData->userRefDataGet($uid, 'heart_class', 'heart_class', $class_id);
              if (!empty($getclassfacilator)) {
                $form_state->setError($form[$upload_field], $this->t('User with email address @email is already a Facilator of this class.', ['@email' => $data[0]]));
              }
            }
          }
        }
        if (count($emails) > $license_left) {
          $form_state->setError($form[$email_field], $this->t('You can not invite user more than license available.'));
        }
      }
    }
  }

  /**
   * AJAX callback for the form submission.
   */
  public function ajaxSubmitCallback($form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $emails = [];
    $triggering_element = $form_state->getTriggeringElement();

    if (
      !empty($triggering_element) && isset($triggering_element['#attributes']['name']) &&
      $triggering_element['#attributes']['name'] == 'send_invite_button'
    ) {
      $invite_emails = $form_state->getValue('invite_emails');
      $invite_emails_doc = $form_state->getValue('class_learners_invite_upload');
      $upload_field = 'class_learners_invite_upload';
      $type = 'student';
    }
    elseif (
      !empty($triggering_element) && isset($triggering_element['#attributes']['name']) &&
      $triggering_element['#attributes']['name'] == 'send_invite_facilitator_button'
    ) {
      $invite_emails = $form_state->getValue('invite_emails_facilitator');
      $invite_emails_doc = $form_state->getValue('class_facilitator_invite_upload');
      $upload_field = 'class_facilitator_invite_upload';
      $type = 'teacher';
    }
    $class_id = $form_state->getValue('class_id');
    $class = $this->entityTypeManager->getStorage('heart_class')->load($class_id);
    $class_name = $class->label();
    // Extract emails from invite_emails field.
    if (!empty($invite_emails)) {
      $emails = explode(';', $invite_emails);
    }
    else {
      if (!empty($invite_emails_doc)) {

        // Validate uploaded file.
        $file = $form_state->getValue($upload_field);
        if (!empty($file)) {

          // Load the file entity.
          /** @var \Drupal\file\Entity\FileInterface $file_entity */
          $file_entity = $this->entityTypeManager->getStorage('file')->load($file[0]);
          $file_path = $file_entity->getFileUri();

          $row = 0;
          if (($handle = fopen($file_path, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
              if ($row != 0) {
                $emails[] = $data[0];
              }
              $row++;
            }
            fclose($handle);
          }
        }
      }
    }
    if (!empty($emails)) {
      // Loop through emails and set diocese id and send invite.
      foreach ($emails as $email) {
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
          ['user_id' => $user->uid->value, 'class_id' => $class->id()],
          [
            'absolute' => TRUE,
            'query' => ['type' => $type],
          ],
        );
        $reject_link = Url::fromRoute(
          'heart_custom_forms.reject_invite',
          ['user_id' => $user->uid->value, 'class_id' => $class->id()],
          ['absolute' => TRUE]
        );

        if ($type == 'teacher') {
          // Get email template ids.
          $email_template_entity_id_submitter = $this->emailTemplateService->emailTemplateIdsByTermName('Invitation to Join a Class: Facilitator');
          if ($email_template_entity_id_submitter) {

            // Prepare the template variables.
            $translate = [
              '[User]' => $user_name,
              '[Class Title]' => $class_name,
              '[Accept Invitation Link]' => $accept_link->toString(),
            ];
            $this->sendMailService->heartSendMail($email_template_entity_id_submitter, $translate, $email);
          }
        }
        else {
          // Get email template ids.
          $email_template_entity_id_submitter = $this->emailTemplateService->emailTemplateIdsByTermName('Invitation to Join a Class: Learner');
          if ($email_template_entity_id_submitter) {

            // Prepare the template variables.
            $translate = [
              '[User]' => $user_name,
              '[Class Title]' => $class_name,
              '[Accept Invitation Link]' => $accept_link->toString(),
            ];
            $this->sendMailService->heartSendMail($email_template_entity_id_submitter, $translate, $email);
          }
        }

        // Check class invitation entity is already present.
        $class_invitation_entity = $this->entityTypeManager->getStorage('class_invitation');
        $query = $class_invitation_entity->getQuery()
          ->condition('class_reference', $class_id)
          ->condition('invited_user', $user_id)
          ->condition('invitation_type', $type)
          ->accessCheck(FALSE);
        $entity_ids = $query->execute();
        if (empty($entity_ids)) {
          // Create new class invitation entity.
          $invitation_entity = $this->entityTypeManager
            ->getStorage('class_invitation')
            ->create([
              'label' => 'Class Invitation ' . $class->id(),
              'class_reference' => $class->id() ?? '',
              'invitation_type' => $type,
              'invitation_status' => 'pending',
              'status' => TRUE,
            ]);
          $invitation_entity->invited_user = ['target_id' => $user_id];
          $invitation_entity->save();
        }
        $this->message->addStatus($this->t('Successfully sent invitation to @mail.', ['@mail' => $email]));
      }
    }
  }

}
