<?php

declare(strict_types=1);

namespace Drupal\heart_custom_forms\Form;

use CommerceGuys\Addressing\Country\CountryRepository;
use CommerceGuys\Addressing\Subdivision\SubdivisionRepository;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Template\TwigEnvironment;
use Drupal\heart_custom_forms\HeartCustomService;
use Drupal\heart_misc\EmailTemplateService;
use Drupal\heart_misc\SendMailService;
use Drupal\heart_user_data\UserRefData;
use Drupal\heart_webinar\EventRegistrantsService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Heart Custom Forms form.
 */
final class ReorderLicenseForm extends FormBase {

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
   * The date and time.
   *
   * @var Drupal\Core\Datetime\DrupalDateTime
   */
  protected $dateTime;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The subdivision repository.
   *
   * @var \CommerceGuys\Addressing\Subdivision\SubdivisionRepository
   */
  protected $subdivisionRepository;

  /**
   * The country repository.
   *
   * @var \CommerceGuys\Addressing\Country\CountryRepository
   */
  protected $countryRepository;

  /**
   * The mail manager.
   *
   * @var Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * The mail manager.
   *
   * @var Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $config;

  /**
   * The mail manager.
   *
   * @var Drupal\Core\Url
   */
  protected $url;

  /**
   * The EventRegistrantsService.
   *
   * @var Drupal\heart_webinar\EventRegistrantsService
   */
  protected $eventRegistrantsService;

  /**
   * The UserRefData Service.
   *
   * @var Drupal\heart_webinar\UserRefData
   */
  protected $userRefData;

  /**
   * The UserRefData Service.
   *
   * @var Drupal\Core\Template\TwigEnvironment
   */
  protected $twig;

  /**
   * The UserRefData Service.
   *
   * @var Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The Email template Service.
   *
   * @var Drupal\heart_misc\EmailTemplateService
   */
  protected $emailTemplateService;

  /**
   * Custom Helper service.
   *
   * @var \Drupal\heart_custom_forms\HeartCustomService
   */
  protected $helper;

  /**
   * The Send Mail Service.
   *
   * @var Drupal\heart_misc\SendMailService
   */
  protected $sendMailService;

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
   * @param Drupal\Core\Datetime\DrupalDateTime $date_time
   *   The date and time.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \CommerceGuys\Addressing\Subdivision\SubdivisionRepository $subdivision_repository
   *   The subdivision repository.
   * @param \CommerceGuys\Addressing\Country\CountryRepository $country_repository
   *   The country repository.
   * @param Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   * @param Drupal\Core\Config\ConfigFactoryInterface $config
   *   The Config factory.
   * @param Drupal\Core\Url $url
   *   The url generator.
   * @param Drupal\heart_webinar\EventRegistrantsService $event_registrants_service
   *   The eventRegistrants Service.
   * @param Drupal\heart_webinar\UserRefData $user_ref_data
   *   The UserRefData Service.
   * @param Drupal\Core\Template\TwigEnvironment $twig
   *   The Template render Service.
   * @param Drupal\Core\Database\Connection $database
   *   The Database connection Service.
   * @param Drupal\heart_misc\EmailTemplateService $email_template_service
   *   The Email Template  Service.
   * @param Drupal\heart_custom_forms\HeartCustomService $helper
   *   The custom helper service.
   * @param Drupal\heart_misc\SendMailService $send_mail_service
   *   The Send Mail service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    CurrentPathStack $current_path,
    AccountInterface $current_user,
    RouteMatchInterface $route_match,
    TimeInterface $date_time,
    MessengerInterface $messenger,
    SubdivisionRepository $subdivision_repository,
    CountryRepository $country_repository,
    MailManagerInterface $mail_manager,
    ConfigFactoryInterface $config,
    UrlGeneratorInterface $url,
    EventRegistrantsService $event_registrants_service,
    UserRefData $user_ref_data,
    TwigEnvironment $twig,
    Connection $database,
    EmailTemplateService $email_template_service,
    HeartCustomService $helper,
    SendMailService $send_mail_service
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentPath = $current_path;
    $this->currentUser = $current_user;
    $this->routeMatch = $route_match;
    $this->dateTime = $date_time;
    $this->messenger = $messenger;
    $this->subdivisionRepository = $subdivision_repository;
    $this->countryRepository = $country_repository;
    $this->mailManager = $mail_manager;
    $this->config = $config;
    $this->url = $url;
    $this->eventRegistrantsService = $event_registrants_service;
    $this->userRefData = $user_ref_data;
    $this->twig = $twig;
    $this->database = $database;
    $this->emailTemplateService = $email_template_service;
    $this->helper = $helper;
    $this->sendMailService = $send_mail_service;
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
      $container->get('datetime.time'),
      $container->get('messenger'),
      $container->get('address.subdivision_repository'),
      $container->get('address.country_repository'),
      $container->get('plugin.manager.mail'),
      $container->get('config.factory'),
      $container->get('url_generator'),
      $container->get('heart_webinar.event_registrants_service'),
      $container->get('heart_user_data.user_ref_data'),
      $container->get('twig'),
      $container->get('database'),
      $container->get('heart_misc.email_template_service'),
      $container->get('heart_custom_forms.heart_custom_service'),
      $container->get('heart_misc.send_mail_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'heart_custom_forms_event_registration';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // Query to fetch custom entity titles.
    // Call helper function to get class.
    $form['#prefix'] = '<div id="reorder_license" class="wrapper-900">';
    $form['#suffix'] = '</div>';
    $course_option = $this->helper->getCourseProduct();
    $form['select_class'] = [
      '#type' => 'select',
      '#title' => $this->t('Existing Classes Available'),
      '#options' => $course_option,
      '#ajax' => [
        // AJAX callback method.
        'callback' => '::updateClassesCallback',
        // Id of container.
        'wrapper' => 'class-list-wrapper',
        // Trigger on change.
        'event' => 'change',
      ],
    ];

    // Initially, no classes will be shown (empty markup).
    $form['class_includes'] = [
      '#type' => 'markup',

      // Wrapper.
      '#prefix' => '<div id="class-list-wrapper">',
      '#suffix' => '</div>',
    ];
    $form['item_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Item Number'),
    ];
    $form['customer_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Customer Number'),
    ];
    $form['license_quantity'] = [
      '#type' => 'textfield',
      '#title' => $this->t('License Quantity Requested'),
    ];
    $form['customer_contact_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Customer Contact Name'),
    ];
    $form['customer_contact_phone'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Customer Contact Phone Number'),
    ];
    $form['customer_contact_email'] = [
      '#type' => 'email',
      '#title' => $this->t('Customer Contact Email'),
    ];
    $form['comment'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Comments'),
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
        '#value' => $this->t('Submit Request'),
      ],
    ];
    $form['#theme'] = 'reorder_licenses';
    return $form;
  }

  /**
   * Ajax callback for Diocese select field.
   */
  public function getCourseClasses($form, FormStateInterface $form_state) {
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();

    // Validate Item Number.
    if (empty($values['item_number']) || !ctype_digit($values['item_number'])) {
      $form_state->setErrorByName('item_number', $this->t('Item Number is required and must contain only digits.'));
    }

    // Validate Customer Number.
    if (empty($values['customer_number'])) {
      $form_state->setErrorByName('customer_number', $this->t('Customer Number is required.'));
    }

    // Validate License Quantity Requested.
    if (empty($values['license_quantity']) || !ctype_digit($values['license_quantity']) || intval($values['license_quantity']) <= 0) {
      $form_state->setErrorByName('license_quantity', $this->t('License Quantity Requested is required and must be a positive integer.'));
    }

    // Validate Customer Contact Name.
    if (empty($values['customer_contact_name'])) {
      $form_state->setErrorByName('customer_contact_name', $this->t('Customer Contact Name is required.'));
    }

    // Validate Customer Contact Phone.
    if (empty($values['customer_contact_phone']) || !ctype_digit($values['customer_contact_phone'])) {
      $form_state->setErrorByName('customer_contact_phone', $this->t('Customer Contact Phone is required and must contain only digits.'));
    }
  }

  /**
   * Your form submit function.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    // Get the selected key.
    $selected_key = $form_state->getValue('select_class');

    // Get the options array.
    $course_options = $this->helper->getCourseProduct();

    // Get the corresponding label (value) for the selected key.
    $selected_value = $course_options[$selected_key] ?? NULL;

    // Get email template ids.
    $email_template_entity_id_submitter = $this->emailTemplateService->emailTemplateIdsByTermName('License Reorder: Submitter');
    $email_template_entity_id_reciever = $this->emailTemplateService->emailTemplateIdsByTermName('License Reorder: Receiver');
    if ($email_template_entity_id_submitter) {

      // Prepare the template variables.
      $translate = [
        '[[User]]' => $values['customer_contact_name'],
      ];
      $this->sendMailService->heartSendMail($email_template_entity_id_submitter, $translate, $values['customer_contact_email']);
    }
    if ($email_template_entity_id_reciever) {

      // Prepare the template variables.
      $translate = [
        '[[User]]' => $values['customer_contact_name'],
        '[[Item Number]]' => $values['item_number'],
        '[[Quantity Requested]]' => $values['license_quantity'],
        '[[Comments]]' => $values['comment'],
        '[[Customer Number]]' => $values['customer_number'],
        '[[Customer Name]]' => $values['customer_contact_name'],
        '[[Customer Phone]]' => $values['customer_contact_phone'],
        '[[Customer Email]]' => $values['customer_contact_email'],
        '[[class]]' => $selected_value,
      ];
      $this->sendMailService->heartSendMail($email_template_entity_id_reciever, $translate, $values['customer_contact_email']);
      $this->messenger->addMessage('A mail has been sent to you.Team will process your request shortly.');
    }
    // Check if email template ids exist.
    else {
      // If email template not found.
      $this->messenger->addError($this->t('Email not sent. Please contact the administrator.'));
    }
  }

  /**
   *
   */
  public function cancelForm(array &$form, FormStateInterface $form_state) {
    // Redirect to homepage.
    $form_state->setRedirect('<front>');
  }

  /**
   * AJAX callback to update the class options based on the selected course.
   */

  /**
   * AJAX callback to update the class options based on the selected course.
   */
  public function updateClassesCallback(array &$form, FormStateInterface $form_state) {
    // Get the selected course ID from the form state.
    $selected_course_id = $form_state->getValue('select_class');

    if (!empty($selected_course_id)) {
      // Fetch the classes based on the selected course ID.
      $class_options = $this->helper->getClassByCourseId($selected_course_id);

      // Update the class list.
      $class_list = !empty($class_options) ? implode('; ', array_values($class_options)) : $this->t('No classes available for the selected course.');

      // Update the markup.
      $form['class_includes']['#markup'] = '<div>' . $this->t('@classes', ['@classes' => $class_list]) . '</div>';
      // Make the markup visible after a valid selection.
      $form['class_includes']['#access'] = TRUE;
    }
    else {
      // If no course is selected, hide class list.
      $form['class_includes']['#markup'] = '';
      $form['class_includes']['#access'] = FALSE;
    }

    // Return portion of the form.
    return $form['class_includes'];
  }

}
