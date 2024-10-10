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
use Drupal\heart_custom_forms\HeartCustomService;
use Drupal\heart_misc\EmailTemplateService;
use Drupal\heart_misc\SendMailService;
use Drupal\heart_user_data\UserRefData;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Heart Custom Forms form.
 */
final class ClassAddMoreLicensesForm extends FormBase {

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
    return 'class_add_more_licenses_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['#prefix'] = '<div id="class-add-more-license-form">';
    $form['#suffix'] = '</div>';
    $form['#attached']['library'][] = 'heart_custom_forms/heart_custom_forms';
    // Get the current language.
    $current_language = $this->languageManager->getCurrentLanguage()->getId();

    // Add a container to display error messages.
    $form['form_errors'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'form-errors'],
    ];
    $form['form_message'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'form-message'],
    ];
    $triggering_element = $form_state->getTriggeringElement();

    $class_id = $this->routematch->getParameter('id');
    if ($class_id) {
      $class = $this->entityTypeManager->getStorage('heart_class')->load($class_id);
      if ($class->hasTranslation($current_language)) {
        $class = $class->getTranslation($current_language);
      }
      else {
        $class = $class->addTranslation($current_language);
      }
      $license_class_applied = $class->licenses_available->value . ' Seats/Licenses';
      $course_id = $class->course_field->target_id;
      $diocese_id = $class->diocese_field->target_id;
      $parish_id = $class->parish_field->target_id;
      $license_quantity_available = '';
      $license_number = $this->helper->getCourseProductLicenseQuantity($course_id, $diocese_id, $parish_id);
      if (!empty($license_number)) {
        $license_available = reset($license_number);
        $license_quantity_available = $license_available->license_quantity_available;
      }

      if (
        !empty($triggering_element) && isset($triggering_element['#attributes']['name']) &&
        $triggering_element['#attributes']['name'] == 'add_more_license'
      ) {
        $values = $form_state->getValues();
        $license_number = $this->helper->getCourseProductLicenseQuantity($course_id, $diocese_id, $parish_id);
        if (!empty($license_number)) {
          $license_available = reset($license_number);
          $license_quantity_available = $license_available->license_quantity_available;
        }
        $fieldsToReset = [
          'license_applied',
          'available_license',
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
      $form['license_applied'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Licenses Applied to this class:'),
        // '#required' => TRUE,
        '#placeholder' => $license_class_applied,
        '#prefix' => '<div class="views-exposed-form"><div class="font-size-15 m-bottom-1">View Licenses</div><div class="class_learners_view combine_search form-inline">',
      ];

      $form['add_more_license'] = [
        '#type' => 'button',
        '#value' => $this->t('Save'),
        '#prefix' => '<div class="form-item form-actions">',
        '#suffix' => '</div></div></div>',
        // Added ajax callback to add license to class.
        '#ajax' => [
          'callback' => [$this, 'ajaxSubmitCallback'],
          'wrapper' => 'class-add-more-license-form',
          'event' => 'click',
        ],
        '#attributes' => [
          'name' => 'add_more_license',
        ],
      ];

      $purchasedlicensesstilltext = $this->t('Purchased Licenses Still Available:');

      $form['classinfo'] = [
        '#type' => 'markup',
        '#markup' => '<p class="text-secondary my-3">' . $purchasedlicensesstilltext . '<span class="text-dark">' . $license_quantity_available . '</span></p>',
      ];

      $form['available_license'] = [
        '#type' => 'hidden',
        '#default_value' => $license_quantity_available,
      ];

      // Hidden field for class id.
      $form['class_id'] = [
        '#type' => 'hidden',
        '#default_value' => $class_id,
      ];

      // Hidden field for course id.
      $form['course_id'] = [
        '#type' => 'hidden',
        '#default_value' => $course_id,
      ];

      // Hidden field for diocese id.
      $form['diocese_id'] = [
        '#type' => 'hidden',
        '#default_value' => $diocese_id,
      ];

      // Hidden field for parish id.
      $form['parish_id'] = [
        '#type' => 'hidden',
        '#default_value' => $parish_id,
      ];
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();
    if (!empty($values)) {
      if ($values['license_applied'] != '' && $values['license_applied'] > $values['available_license']) {
        $form_state->setErrorByName('license_applied', $this->t('You cannot add more than the available licenses.'));
      }
    }
  }

  /**
   * AJAX callback for the form submission.
   */
  public function ajaxSubmitCallback(array &$form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Get the current language.
    $current_language = $this->languageManager->getCurrentLanguage()->getId();
    $values = $form_state->getValues();
    if (!empty($values) && $values['license_applied'] != '') {
      $class = $this->entityTypeManager->getStorage('heart_class')->load($values['class_id']);
      if ($class->hasTranslation($current_language)) {
        $class = $class->getTranslation($current_language);
      }
      else {
        $class = $class->addTranslation($current_language);
      }
      $available_licenseclass = $class->licenses_available->value;
      $total_license = intval($values['license_applied']) + intval($available_licenseclass);
      $class->set('licenses_available', $total_license);
      $class->save();
      $license_entity = $this->entityTypeManager->getStorage('heart_license');
      $query = $license_entity->getQuery();
      $query->condition('langcode', $current_language);
      $query->condition('course_field', $values['course_id']);
      $query->condition('diocese_field', $values['diocese_id']);
      if ($values['parish_id'] != '') {
        $query->condition('parish_field', $values['parish_id']);
      }
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
        $now_license = intval($available_license) - intval($values['license_applied']);
        $course_license->set('license_quantity_available', $now_license);
        $course_license->save();
      }
      $this->message->addMessage('License updated successfully.');
    }
  }

}
