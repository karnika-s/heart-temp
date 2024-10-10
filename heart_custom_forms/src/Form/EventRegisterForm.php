<?php

declare(strict_types=1);

namespace Drupal\heart_custom_forms\Form;

use CommerceGuys\Addressing\Country\CountryRepository;
use CommerceGuys\Addressing\Subdivision\SubdivisionRepository;
use Drupal\commerce_cart\CartManagerInterface;
use Drupal\commerce_cart\CartProviderInterface;
use Drupal\commerce_price\Price;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Template\TwigEnvironment;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\Core\Url;
use Drupal\heart_misc\EmailTemplateService;
use Drupal\heart_misc\SendMailService;
use Drupal\heart_user_data\UserRefData;
use Drupal\heart_webinar\EventRegistrantsService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Heart Custom Forms form.
 */
final class EventRegisterForm extends FormBase {

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
   * The temp store Service.
   *
   * @var Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStore;
  /**
   * The commerce cart manager Service.
   *
   * @var Drupal\commerce_cart\CartManagerInterface
   */
  protected $commerceCartManager;
  /**
   * The commerce cart provider Service.
   *
   * @var Drupal\commerce_cart\CartProviderInterface
   */
  protected $cartProvider;

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
   * @param Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store_factory
   *   The temp store  Service.
   * @param Drupal\commerce_cart\CartManagerInterface $cart_manager
   *   The Cart manager  Service.
   * @param Drupal\commerce_cart\CartProviderInterface $cart_provider
   *   The Cart provider  Service.
   * @param Drupal\heart_misc\SendMailService $send_mail_service
   *   The Send Mail  Service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
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
    PrivateTempStoreFactory $temp_store_factory,
    CartProviderInterface $cart_provider,
    CartManagerInterface $cart_manager,
    SendMailService $send_mail_service,
    LanguageManagerInterface $language_manager
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
    $this->tempStore = $temp_store_factory->get('heart_custom_forms');
    $this->cartProvider = $cart_provider;
    $this->commerceCartManager = $cart_manager;
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
      $container->get('tempstore.private'),
      $container->get('commerce_cart.cart_provider'),
      $container->get('commerce_cart.cart_manager'),
      $container->get('heart_misc.send_mail_service'),
      $container->get('language_manager'),

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

    $webinar_id = $this->routeMatch->getParameter('id');

    $event_product = $this->entityTypeManager->getStorage('commerce_product')->load($webinar_id);
    // dump($event_product->field_event_reference->target_id);exit;.
    $zoom_webinar_id = '';
    if (!empty($event_product->field_event_reference->target_id)) {
      $event_entity = $event_product->get('field_event_reference')->referencedEntities();
      if (!empty($event_entity)) {
        $event_entity = reset($event_entity);
        if ($event_entity->heart_webinar_reference->target_id != NULL) {
          $zoom_webinar_entity = $event_entity->get('heart_webinar_reference')->referencedEntities();
          if (!empty($zoom_webinar_entity)) {
            $zoom_webinar_entity = reset($zoom_webinar_entity);
            $zoom_webinar_id = $zoom_webinar_entity->zoom_id->value;
          }
        }
      }
    }

    // $event_re
    // Check if webinar exist.
    if ($webinar_id && $event_product) {
      // Fetch current logged-in user.
      $current_user = $this->currentUser();

      // Default values if the user is logged in.
      $default_values = [
        'first_name' => '',
        'last_name' => '',
        'email' => '',
        'phone' => '',
        'city' => '',
        'state' => '',
        'country' => 'US',
        'school_name' => '',
        'diocese' => '',
        'job_title' => '',
      ];

      // Check if the user is logged in and has a profile.
      if ($current_user->isAuthenticated()) {
        $user_id = $current_user->id();
        $user_profile = $this->entityTypeManager->getStorage('user_profile_data')->loadByProperties(['user_data' => $user_id]);

        // If profile data exists, populate the default values.
        if ($user_profile) {
          $user_profile = reset($user_profile);
          // Initialize default values array.
          $default_values = [];

          // Set default values only if fields are not empty.
          $default_values['first_name'] = !empty($user_profile->get('first_name')->value) ? $user_profile->get('first_name')->value : '';
          $default_values['last_name'] = !empty($user_profile->get('last_name')->value) ? $user_profile->get('last_name')->value : '';
          $default_values['email'] = !empty($current_user->getEmail()) ? $current_user->getEmail() : '';
          $default_values['phone'] = !empty($user_profile->get('phone')->value) ? $user_profile->get('phone')->value : '';
          $default_values['city'] = !empty($user_profile->get('city')->value) ? $user_profile->get('city')->value : '';

          // Access the country code from the address field.
          if (!empty($user_profile->user_profile_address)) {
            $default_values['country'] = !empty($user_profile->user_profile_address->country_code) ? $user_profile->user_profile_address->country_code : 'US';
            // Default to empty if not available.
            $default_values['state'] = !empty($user_profile->user_profile_address->administrative_area) ? $user_profile->user_profile_address->administrative_area : '';
          }
          else {
            // Default to 'US' if address is not available.
            $default_values['country'] = 'US';
            // Default to empty.
            $default_values['state'] = '';
          }

          $default_values['school_name'] = !empty($user_profile->get('school_name')->value) ? $user_profile->get('school_name')->value : '';
          $default_values['diocese'] = !empty($user_profile->get('user_diocese_field')->target_id) ? $user_profile->get('user_diocese_field')->target_id : '';
          $default_values['job_title'] = !empty($user_profile->get('job_title')->value) ? $user_profile->get('job_title')->value : '';
        }
      }

      $form['#prefix'] = '<div id="error_element">';
      $form['#suffix'] = '</div>';
      // The status messages that will contain any form errors.
      $form['status_messages'] = [
        '#type' => 'status_messages',
        '#weight' => -10,
      ];
      $form['personal_information']['group_left'] = [
        '#prefix' => '<div class="fs-row"><div class="fs-col-6">',
        '#suffix' => '</div>',
      ];
      $form['personal_information']['group_right'] = [
        '#prefix' => '<div class="fs-col-6">',
        '#suffix' => '</div></div>',
      ];
      // Add a hidden field to store the webinar ID.
      $form['redirect_webinar_id'] = [
        '#type' => 'hidden',
        '#value' => $this->routeMatch->getParameter('id'),
      ];

      $form['zoom_webinar_id'] = [
        '#type' => 'hidden',
        '#value' => $zoom_webinar_id,
      ];

      $form['personal_information']['group_left']['first_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('First Name'),
        '#required' => TRUE,
        '#default_value' => $default_values['first_name'],
      ];
      $form['personal_information']['group_right']['last_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Last Name'),
        '#required' => TRUE,
        '#default_value' => $default_values['last_name'],
      ];
      $form['personal_information']['group_left']['email'] = [
        '#title' => $this->t('Email Address'),
        '#type' => 'email',
        '#required' => TRUE,
        '#default_value' => $default_values['email'],

      ];

      $form['personal_information']['group_right']['phone'] = [
        '#title' => $this->t('Phone'),
        '#type' => 'textfield',
        '#required' => TRUE,
        '#default_value' => $default_values['phone'],
      ];
      $form['personal_information']['group_left']['city'] = [
        '#title' => $this->t('City'),
        '#type' => 'textfield',
        '#required' => TRUE,
        '#default_value' => $default_values['city'],
      ];
      $form['personal_information']['group_right']['country'] = [
        '#title' => $this->t('Country'),
        '#type' => 'select',
        '#chosen' => FALSE,
        '#options' => $this->getCountryList(),
        // Set a default country.
        '#default_value' => $default_values['country'],
        '#required' => TRUE,
        '#ajax' => [
          // AJAX callback function.
          'callback' => '::updateStateOptions',
          // The ID of the HTML element to replace.
          'wrapper' => 'state-wrapper',
          'event' => 'change',
        ],
      ];

      $form['personal_information']['group_right']['state'] = [
        '#title' => $this->t('State'),
        '#type' => 'select',
        '#chosen' => FALSE,

        // Default to US states.
        '#options' => $this->subdivisionRepository->getList(['US']),
        '#default_value' => $default_values['state'],
        // Skip validation for this field after AJAX update.
        '#validated' => TRUE,
        '#required' => TRUE,
        // Wrap the field in a div for AJAX replacement.
        '#prefix' => '<div id="state-wrapper">',
        '#suffix' => '</div>',
      ];

      $form['personal_information']['group_left']['school_name'] = [
        '#type' => 'textfield',
        '#title' => $this->t('School/Church Name'),
        '#default_value' => $default_values['school_name'],
      ];

      $current_language = $this->languageManager->getCurrentLanguage()->getId();

      // Load diocese data.
      $diocese_query = $this->database->select('heart_diocese_data_field_data', 'd')
        ->condition('langcode', $current_language)
        ->fields('d', ['id', 'label']);

      // Execute the query.
      $result = $diocese_query->execute();

      // Initialize an array to store diocese options.
      $diocese_options = ['' => '--select--'];
      foreach ($result as $record) {
        $diocese_options[$record->id] = $record->label;
      }

      $form['personal_information']['group_right']['diocese'] = [
        '#type' => 'select',
        '#title' => $this->t('Diocese'),
        '#options' => $diocese_options,
        '#default_value' => $default_values['diocese'],
      ];
      $form['personal_information']['group_left']['job_title'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Job Title'),
        '#default_value' => $default_values['job_title'],
      ];

      $form['submit'] = [
        '#type' => 'submit',
        '#value' => $this->t('Save And Continue'),
        '#prefix' => '<div class="form-actions">',
        '#suffix' => '</div>',
        '#attributes' => [
          'class' => [
            'use-ajax',
          ],
        ],
        '#ajax' => [
          'callback' => '::submitModalFormAjax',
          'event' => 'click',
        ],
      ];
    }
    else {
      // If there is no webinar with that id.
      $form['form_markup'] = [
        '#type' => 'markup',
        '#markup' => '<h3 class="text-dark">Event not found</h3>',
      ];
    }

    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    return $form;
  }

  /**
   * AJAX callback handler that displays any errors or a success message.
   */
  public function submitModalFormAjax(array $form, FormStateInterface $form_state) {
    $values = $form_state->getValues();

    // Get the webinar ID from the form state.
    $webinarId = $form_state->getValue('redirect_webinar_id');
    $response = new AjaxResponse();

    // If there are any form errors, re-display the form.
    if ($form_state->hasAnyErrors()) {
      $response->addCommand(new ReplaceCommand('#error_element', $form));
    }
    else {

      // Success mail.
      $event_product = $this->entityTypeManager->getStorage('commerce_product')->load($values['redirect_webinar_id']);
      $event_entity = $this->entityTypeManager->getStorage('event')->load($event_product->field_event_reference->target_id);
      if (!$event_product && !$event_entity) {
        $this->messenger->addWarning($this->t('Event not found'));
      }
      if ($event_product  && $event_entity->item_cost->value == 'complimentary') {
        // Redirect to current webinar page using RedirectCommand.
        $url = $this->url->generateFromRoute('heart_webinar.heart_webinar_detail', ['id' => $webinarId]);
        $command = new RedirectCommand($url);
        $response->addCommand($command);
        $this->messenger->addMessage($this->t('Thank you for registering!.A mail has been send to the registered email'));
      }
      else {
        // Redirect to the cart page using RedirectCommand.
        $url = Url::fromRoute('commerce_cart.page')->toString();
        $command = new RedirectCommand($url);
        $response->addCommand($command);
        $this->messenger->addMessage($this->t('Form filled,please proceed with the payment'));
      }
    }
    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();
    $event_product = $this->entityTypeManager->getStorage('commerce_product')->load($values['redirect_webinar_id']);
    $user = $this->entityTypeManager->getStorage('user')->loadByProperties(['mail' => $values['email']]);
    $current_user_id = $this->currentUser->id();
    $userId = '';
    // Check if site user is already registered for the event.
    if (!empty($user)) {
      // Get the first user (assuming unique email addresses).
      $user = reset($user);
      // Get the user ID.
      $userId = $user->id();

      // Error if try to register another site user.
      if ($userId != $current_user_id) {
        $form_state->setErrorByName('email', $this->t('Cannot Register other user.'));
      }
      // Check if email is already registered for the event.
      if ($this->userRefData->userRefDataGet($userId, $event_product->getEntityTypeId(), $event_product->bundle(), $values['redirect_webinar_id'])) {
        $form_state->setErrorByName('email', $this->t('User already Registered.'));
      };
    }
    // Check if email is already registered apart from site users for the event.
    if (!empty($event_product) && $this->eventRegistrantsService->eventRegistrantsGet($current_user_id, $event_product->EntityTypeId, $event_product->bundle(), $event_product->id())) {
      $form_state->setErrorByName('email', $this->t('User already Registered.'));
    }
  }

  /**
   * Your form submit function.
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $zoom_webinar_id = $values['zoom_webinar_id'];
    // Load user data if mail user exists.
    $user = $this->entityTypeManager->getStorage('user')->loadByProperties(['mail' => $values['email']]);
    $current_user_id = $this->currentUser->id();
    // Load Event data.
    $event_product = $this->entityTypeManager->getStorage('commerce_product')->load($values['redirect_webinar_id']);
    // Check if product is exit and is complementry.
    if (!empty($event_product) && (is_null($event_product->field_event_price->number) || $event_product->field_event_price->number == 0)) {
      // Check if user(s) with the given email exist.
      if (!empty($user)) {
        // Get the first user (assuming unique email addresses).
        $user = reset($user);
        // Get the user ID.
        $userId = $user->id();

        // Create product order.
        $this->createProductOrder($user, $event_product, $values, $userId);

        // Add entry for site user for registration.
        $this->userRefData->userRefDataAdd($userId, $event_product->getEntityTypeId(), $values['redirect_webinar_id'], $event_product->bundle());
      }
      else {
        // Add entry for site user for registration.
        if (!empty($event_product)) {
          $entity_id = $values['redirect_webinar_id'];
          $entity_type = $event_product->getEntityTypeId();
          // Create product order.
          $this->createProductOrder($user, $event_product, $values);
          $bundle = $event_product->bundle();

          // Define the data to be inserted.
          $registrant_data = [
            'source_uid' => $current_user_id,
            'registrant_email' => $values['email'],
            'first_name' => !empty($values['first_name']) ? $values['first_name'] : '',
            'last_name' => !empty($values['last_name']) ? $values['last_name'] : '',
            'phone_no' => !empty($values['phone']) ? $values['phone'] : '',
            'school' => !empty($values['school_name']) ? $values['school_name'] : '',
            'city' => !empty($values['city']) ? $values['city'] : '',
            'job_title' => !empty($values['job_title']) ? $values['job_title'] : '',
            'state' => !empty($values['state']) ? $values['state'] : '',
            'diocese' => $values['diocese'] ?? NULL,
            'ref_entity_type' => $entity_type,
            'ref_entity_bundle' => $bundle,
            'ref_entity_id' => $entity_id,
            'country' => !empty($values['country']) ? $values['country'] : '',
          ];
          // Add registration for registrants apart from site users.
          $this->eventRegistrantsService->eventRegistrantsAdd($registrant_data);
        }
      }

      // Check if product is attached to event entity.
      if ($event_product->field_event_reference->target_id) {
        $this->sendRegistrantConfirmMail($event_product->field_event_reference->target_id, $values, $user, $userId);
      }
    }
    else {
      // Priced product: add to cart and redirect to checkout.
      if (!empty($event_product)) {
        $store_storage = $this->entityTypeManager->getStorage('commerce_store');

        // Load the default store.
        $stores = $store_storage->loadMultiple();
        $default_store = reset($stores);
        // Store form values in a temporary variable with a unique key.
        $product_type = $event_product->bundle();
        $temp_store_key = 'registration_data_' . $product_type . $event_product->id() . '_' . $current_user_id;
        $this->tempStore->set($temp_store_key, $values);

        // Create or load the current order for the user.
        $order = $this->cartProvider->getCart('default', $default_store, $this->currentUser);
        if (!$order) {
          $order = $this->cartProvider->createCart('default', $default_store, $this->currentUser);
        }
        // Get the first variation of the product.
        $variations = $event_product->getVariations();
        $variation = reset($variations);
        // Check if the product is already in the cart.
        $product_found = FALSE;
        foreach ($order->getItems() as $order_item) {
          if ($order_item->getPurchasedEntity()->id() == $variation->id()) {
            // Update the quantity to 1 if the item is already in the cart.
            $order_item->setQuantity(1);
            $order_item->save();
            $product_found = TRUE;
            break;
          }
        }

        // If the product is not found in the cart, add it.
        if (!$product_found) {
          $this->commerceCartManager->addEntity($order, $variation);
        }
      }
    }
    if ($zoom_webinar_id != '') {
      $client = \Drupal::service('zoomapi.client');
      $webinarId = $zoom_webinar_id;

      $userId = 'dfrommelt@kendallhunt.com';
      $data = [
        'json' => [
          "first_name" => !empty($values['first_name']) ? $values['first_name'] : '',
          "last_name" => !empty($values['last_name']) ? $values['last_name'] : '',
          "email" => $values['email'],
          "address" => "",
          "city" => !empty($values['city']) ? $values['city'] : '',
          "state" => !empty($values['state']) ? $values['state'] : '',
          "zip" => "",
          "country" => !empty($values['country']) ? $values['country'] : '',
          "phone" => !empty($values['phone']) ? $values['phone'] : '',
          "comments" => "",
          "custom_questions" => [
            [
              "title" => "",
              "value" => "",
            ],
          ],
          "industry" => "",
          "job_title" => !empty($values['job_title']) ? $values['job_title'] : '',
          "no_of_employees" => "",
          "org" => "",
          "purchasing_time_frame" => "",
          "role_in_purchase_process" => "",
          "language" => "",
          "source_id" => "",
        ],
      ];

      try {
        // Make the API request to Zoom's registrants endpoint.
        $response = $client->post("/webinars/$webinarId/registrants", $data);
        // Log the response for debugging purposes.
        \Drupal::logger('heart_zoom')->info('<code>' . print_r($response, TRUE) . '</code>');
        // Return the response, ensuring it is an array.
        return [
          '#type' => 'markup',
          '#markup' => '<pre>' . print_r($response, TRUE) . '</pre>',
        ];
      }
      catch (\Exception $e) {
        // Handle the error and log the exception.
        \Drupal::logger('heart_zoom')->error($e->getMessage());

        // Return an error message as a response.
        return [
          '#type' => 'markup',
          '#markup' => $this->t('An error occurred: @message', ['@message' => $e->getMessage()]),
        ];
      }
    }
  }

  /**
   * Create order for complimentary product.
   */
  public function createProductOrder($user, $event_product, $values, $user_id = NULL) {
    $product_entity_bundle = $event_product->bundle();

    // Get Variation details.
    $variations = $event_product->getVariations();
    if (isset($variations[0])) {
      $variation_id = $variations[0]->id();
      // Load the product variation.
      $variation = $this->entityTypeManager->getStorage('commerce_product_variation')->load($variation_id);
      // Create an order item and save it.
      $order_item = $this->entityTypeManager->getStorage('commerce_order_item')->create([
        'type' => 'default',
        'purchased_entity' => $variation,
        'quantity' => 1,
        'field_complementary' => 1,
        'field_product_type' => $product_entity_bundle,
        'field_external_register_mail' => !empty($user_id) ? '' : $values['email'],
        'field_product' => $event_product,
        'unit_price' => new Price('0', 'USD'),
        'overridden_unit_price' => TRUE,
      ]);
      // Set the product title explicitly.
      $order_item->setTitle($event_product->getTitle());
      $order_item->save();
      // Create order and attach the previous order item generated.
      $order = $this->entityTypeManager->getStorage('commerce_order')->create([
        'type' => 'default',
        'mail' => $this->currentUser->getEmail(),
        'uid' => $this->currentUser->id(),
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
    }
  }

  /**
   * Send registrant confirm mail.
   */
  public function sendRegistrantConfirmMail($event_product_id, $values, $user, $userId) {
    $event_entity = $this->entityTypeManager->getStorage('event')->load($event_product_id);
    // Check if event is complimentary.,$userId.
    if (!empty($event_entity) && $event_entity->item_cost->value == 'complimentary') {
      // Format date for webinar event date.
      $date = date('Y-m-d', (int) $event_entity->webinar_event_date->value);
      if (!$user) {
        $name = $values['first_name'] . " " . $values['last_name'];
      }
      else {
        $users_profile_data = $this->entityTypeManager->getStorage('user_profile_data')->loadByProperties(['user_data' => $userId]);
        $user_profile_data = reset($users_profile_data);
        $name = $user_profile_data->first_name->value . " " . $user_profile_data->last_name->value;
      }
      // Format time for webinar event date.
      $time = date('g:i A', (int) $event_entity->webinar_event_date->value);

      $to = $values['email'];
      // Prepare the template variables.
      $translate = [
        '[[User]]' => $name,
        '[[Webinar_Title]]' => $event_entity->event_title->value,
        '[[Webinar_Date]]' => $date,
        '[[Webinar_Time]]' => $time,
        '[[Webinar_Presenter]]' => $event_entity->presenter->value,
        '[[url_en_datetime]]' => urlencode($date . 'T' . $time),
        '[[url_en_title]]' => urlencode($event_entity->event_title->value),
      ];
      // Get email template ids.
      $email_template_entity_ids = $this->emailTemplateService->emailTemplateIdsByTermName('Confirm Event Registration');

      // Check if email template ids exist.
      if (!empty($email_template_entity_ids)) {
        $this->sendMailService->heartSendMail($email_template_entity_ids, $translate, $to);
      }
      else {
        // If email template not found.
        $this->messenger->addError($this->t('Email not sent. Please contact the administrator.'));
      }
    }
  }

  /**
   * AJAX callback to update the state options based on the selected country.
   */
  public function updateStateOptions(array &$form, FormStateInterface $form_state) {
    // Get the selected country value.
    $selected_country = $form_state->getValue('country');

    // Update the state options based on the selected country.
    $form['personal_information']['group_right']['state']['#options'] = $this->subdivisionRepository->getList([$selected_country]);

    // Reset the state value.
    $form_state->setValue('state', NULL);

    // Return the updated state element.
    return $form['personal_information']['group_right']['state'];
  }

  /**
   * Helper function to get the country list with US and Canada at the top.
   */
  protected function getCountryList() {
    // Fetch the complete list of countries.
    $countries = $this->countryRepository->getList();

    // Place US and Canada at the top.
    $top_countries = [
      'US' => $this->t('United States'),
      'CA' => $this->t('Canada'),
    ];

    // Remove US and Canada from the original list.
    unset($countries['US'], $countries['CA']);

    // Combine the top countries with the rest of the list.
    return $top_countries + $countries;
  }

}
