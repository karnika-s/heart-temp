<?php

declare(strict_types=1);

namespace Drupal\heart_diocese\Form;

use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Url;
use Drupal\heart_user_data\UserRefData;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Heart parish form.
 */
final class HeartParishInvite extends FormBase {

  /**
   * The entity repository.
   *
   * @var \Drupal\Core\Entity\EntityRepositoryInterface
   */
  protected $entityRepository;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The user reference data creation.
   *
   * @var \Drupal\heart_user_data\UserRefData
   */
  protected $userRefData;

  /**
   * Constructs a ParishInviteForm object.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   * @param \Drupal\heart_user_data\UserRefData $userRefData
   *   The user reference data creation.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, FileSystemInterface $file_system, LanguageManagerInterface $language_manager, EntityTypeManagerInterface $entity_type_manager, UserRefData $userRefData, MailManagerInterface $mail_manager,) {
    $this->entityRepository = $entity_repository;
    $this->fileSystem = $file_system;
    $this->languageManager = $language_manager;
    $this->entityTypeManager = $entity_type_manager;
    $this->userRefData = $userRefData;
    $this->mailManager = $mail_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.repository'),
      $container->get('file_system'),
      $container->get('language_manager'),
      $container->get('entity_type.manager'),
      $container->get('heart_user_data.user_ref_data'),
      $container->get('plugin.manager.mail'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'heart_parish_heart_parish_invite';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    // Add an upload field.
    $form['invite_emails'] = [
      '#type' => 'textfield',
      // '#title' => $this->t('Email Address(es)'),
      '#prefix' => '<div class="form-inline form-inline-cat"><div class="text-secondary d-inline-block m-right-2">Email Address(es)</div>',
      '#placeholder' => $this->t('to invite multiple people at once, separate with a ";"'),
    ];

    // Add sample document.
    $form['sample_document'] = [
      '#type' => 'link',
      '#prefix' => '<div class="clearfix description m-top-1">',
      '#title' => $this->t('Download multiple user Excel Document'),
      '#url' => Url::fromUri('internal:/modules/custom/heart_diocese/sample_files/SampleParish.csv'),
      '#attributes' => [
        'download' => 'sample_document_parish.csv',
        'class' => 'd-inline-block m-bottom-1 text-secondary',
      ],
      '#options' => [
        'attributes' => ['target' => '_blank'],
      ],
    ];

    // Show a button to upload the excel doc.
    $form['upload_csv_btn'] = [
      '#type' => 'markup',
      '#markup' => $this->t('<br><a class="upload-parish-list btn btn-primary">Upload Excel Doc</a>'),
      '#suffix' => '</div></div>',
    ];

    // Display the upload field for excel doc on click of above button.
    $form['parish_admin_list_upload'] = [
      '#type' => 'managed_file',
      '#upload_location' => 'public://uploads/excel',
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
      ],
      '#default_value' => $this->config('heart_parish.settings')->get('parish_admin_list_upload'),
    ];

    // Hidden field for parish id.
    $form['parish_id'] = [
      '#type' => 'hidden',
      '#title' => $this->t('Parish ID'),
      '#attributes' => [
        'id' => 'hiddent_parish_id',
      ],
    ];

    // Form submit.
    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('Send Invite(s)'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $invite_emails = $form_state->getValue('invite_emails');
    $invite_emails_doc = $form_state->getValue('parish_admin_list_upload');
    $parish_id = $form_state->getValue('parish_id');

    if (empty($invite_emails) && empty($invite_emails_doc)) {
      $form_state->setError($form['invite_emails'], $this->t('Please enter an email address or upload an Excel document.'));
    }

    if (!empty($invite_emails_doc)) {

      // Validate uploaded file.
      $file = $form_state->getValue('parish_admin_list_upload');
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
                $form_state->setErrorByName('parish_admin_list_upload', $this->t('Invalid header row in the uploaded file.'));
              }
            }
            else {

              // Check if email is valid and user exists.
              if (!filter_var($data[0], FILTER_VALIDATE_EMAIL)) {
                $form_state->setError($form['parish_admin_list_upload'], $this->t('Email address @email is not valid.', ['@email' => $data[0]]));
              }
              $users = $this->entityTypeManager->getStorage('user')->loadByProperties(['mail' => $data[0]]);
              $user = reset($users);

              if (empty($user)) {
                $form_state->setError($form['parish_admin_list_upload'], $this->t('User with email address @email does not exist, Please contact IT team.', ['@email' => $data[0]]));
              }
              else {
                $uid = $user->id();
                $getAdmins_parish = $this->userRefData->userRefDataGet($uid, 'heart_parish_data', 'heart_parish_data', null);
                $getAdmins_diocese = $this->userRefData->userRefDataGet($uid, 'heart_diocese_data', 'heart_diocese_data', null);
                if (!empty($getAdmins_parish)) {
                  $form_state->setError($form['parish_admin_list_upload'], $this->t('User with email address @email is already a parish admin.', ['@email' => $data[0]]));
                }
                if (!empty($getAdmins_diocese)) {
                  $form_state->setError($form['parish_admin_list_upload'], $this->t('User with email address @email is already a diocese admin.', ['@email' => $data[0]]));
                }
              }
            }
            $row++;
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
            $form_state->setError($form['invite_emails'], $this->t('Please enter a valid email address or check the separator.'));
          }

          $users = $this->entityTypeManager->getStorage('user')->loadByProperties(['mail' => $email]);
          $user = reset($users);
          if (empty($user)) {
            $form_state->setError($form['invite_emails'], $this->t('User with email address @email does not exist, Please contact IT team.', ['@email' => $email]));
          }
          else {
            $uid = $user->id();
            $getAdmins_parish = $this->userRefData->userRefDataGet($uid, 'heart_parish_data', 'heart_parish_data', null);
            $getAdmins_diocese = $this->userRefData->userRefDataGet($uid, 'heart_diocese_data', 'heart_diocese_data', null);
            if (!empty($getAdmins_parish)) {
              $form_state->setError($form['invite_emails'], $this->t('User with email address @email is already a parish admin.', ['@email' => $email]));
            }
            if (!empty($getAdmins_diocese)) {
              $form_state->setError($form['invite_emails'], $this->t('User with email address @email is already a diocese admin.', ['@email' => $email]));
            }
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {

    $emails = [];
    $invite_emails_doc = $form_state->getValue('parish_admin_list_upload');
    $invite_emails = $form_state->getValue('invite_emails');
    $parish_id = $form_state->getValue('parish_id');
    $parish = $this->entityTypeManager->getStorage('heart_parish_data')->load($parish_id);
    $parish_name = $parish->label();
    // Extract emails from invite_emails field.
    if (!empty($invite_emails)) {
      $emails = explode(';', $invite_emails);
    }
    else {
      if (!empty($invite_emails_doc)) {

        // Validate uploaded file.
        $file = $form_state->getValue('parish_admin_list_upload');
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

    // Loop through emails and set parish id and send invite.
    foreach ($emails as $email) {
      if (!empty($email)) {

        // Define variables.
        $uid = $rids = NULL;

        // Get the user.
        $users = $this->entityTypeManager->getStorage('user')->loadByProperties(['mail' => $email]);
        $user = reset($users);
        if ($user) {
          $uid = $user->id();
          $rids = $user->getRoles();

          // Assign parish_admin role to the user if not already assigned.
          if (!in_array('parish_admin', $rids)) {
            $user->addRole('parish_admin');
            $user->save();
          }

          if (!empty($uid) && !empty($parish_id)) {
            // Add parish info to user refrence data table.

            $this->userRefData->userRefDataAdd($uid, 'heart_parish_data', $parish_id, 'heart_parish_data');

            $custom_entity_user_profile = $this->entityTypeManager->getStorage('user_profile_data');
            $custom_entity_user_profile = $custom_entity_user_profile->loadByProperties([
              'user_data' => $uid,
            ]);

            $custom_entity_user_profile = reset($custom_entity_user_profile);
            $user_profile_id = $custom_entity_user_profile->id();
            $sub_role_id = '9';

            if (!empty($custom_entity_user_profile)) {
              $current_roles = $custom_entity_user_profile->get('sub_role')->getValue();
              $current_roles[] = ['target_id' => $sub_role_id];
              $custom_entity_user_profile->set('sub_role', $current_roles);
              $custom_entity_user_profile->save();
            }

            $custom_entity_parish = $this->entityTypeManager->getStorage('heart_parish_data');
            $custom_entity_parish = $custom_entity_parish->loadByProperties([
              'id' => $parish_id,
            ]);

            $custom_entity_parish = reset($custom_entity_parish);

            if (!empty($custom_entity_parish)) {
              $current_admins = $custom_entity_parish->get('parish_admins')->getValue();
              $current_admins[] = ['target_id' => $user_profile_id];
              $custom_entity_parish->set('parish_admins', $current_admins);
              $custom_entity_parish->save();
            }

          }

          // Email the user.
          $subject = 'Invitation for Parish admin';
          $body = [
            'parish_name' => $parish_name,
          ];

          $langcode = 'en';
          $module = 'heart_parish';
          $key = 'heart_parish_invite_user';
          $message = $body;

          $params['message'] = $message;
          $params['subject'] = $subject;
          $send = TRUE;

          $sent = $this->mailManager->mail($module, $key, $email, $langcode, $params, NULL, $send);
          if (!$sent) {
            throw new \Exception("Error encountered sending email to $to.");
          }
          else {
            $this->messenger()->addStatus($this->t('The invitation has been sent.'));
          }
        }
      }
    }
  }

}
