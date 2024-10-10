<?php

declare(strict_types=1);

namespace Drupal\heart_custom_forms\Form;

use Drupal\Core\Database\Connection;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\heart_custom_forms\HeartCustomService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Heart Custom Forms form.
 */
final class EventForm extends FormBase {

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
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Database connection.
   *
   * @var Drupal\Core\Database\Connection
   */
  protected $database;

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
   * @param Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param Drupal\Core\Messenger\MessengerInterface $message
   *   The message service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   * @param \Drupal\Core\Entity\Connection $database
   *   Database connection.
   * @param \Drupal\heart_custom_forms\HeartCustomService $helper
   *   The custom helper service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    CurrentPathStack $current_path,
    AccountInterface $current_user,
    RouteMatchInterface $route_match,
    MessengerInterface $message,
    LanguageManagerInterface $language_manager,
    Connection $database,
    HeartCustomService $helper
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentPath = $current_path;
    $this->currentUser = $current_user;
    $this->routematch = $route_match;
    $this->message = $message;
    $this->languageManager = $language_manager;
    $this->database = $database;
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
      $container->get('current_route_match'),
      $container->get('messenger'),
      $container->get('language_manager'),
      $container->get('database'),
      $container->get('heart_custom_forms.heart_custom_service')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'heart_custom_forms_event_add_edit';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['#attached']['library'][] = 'heart_custom_forms/heart_custom_forms';
    if (\Drupal::request()->query->get('id') && \Drupal::request()->query->get('entity') == 'event') {
      $event_id = \Drupal::request()->query->get('id');
    }
    else {
      $event_id = $this->routematch->getParameter('event_id');
    }
    $current_language = $this->languageManager->getCurrentLanguage()->getId();
    $webinar_options = $this->helper->getWebinarsDatas();
    $status = 'true';
    // Loading entity events with id.
    if ($event_id) {
      $resource_entity = $this->entityTypeManager->getStorage('event')->load($event_id);

      if (!empty($resource_entity)) {
        $webinar_options = $this->helper->getAllWebinarsDatas();
        if ($resource_entity->hasTranslation($current_language)) {
          $resource_entity = $resource_entity->getTranslation($current_language);
        }
        else {
          $resource_entity = $resource_entity->addTranslation($current_language);
        }

        $form['event_id'] = [
          '#type' => 'hidden',
          '#default_value' => $event_id ?? '',
        ];
        // Get default attach file.
        if ($resource_entity->hasField('attach_file')) {
          $attach_file_field = $resource_entity->get('attach_file');
          if (!$attach_file_field->isEmpty()) {
            $doc_default_values = [];
            foreach ($attach_file_field->getValue() as $item) {
              $doc_default_values[] = $item['target_id'];
            }
          }
        }
        // Get default thumbnail image.
        if ($resource_entity->hasField('thumbnail')) {
          $thumbnail_file_field = $resource_entity->get('thumbnail');
          if (!$thumbnail_file_field->isEmpty()) {
            $thumb_default_values = [];
            foreach ($thumbnail_file_field->getValue() as $item) {
              $thumb_default_values[] = $item['target_id'];
            }
          }
        }
        // Check resource is publish or not.
        if (isset($resource_entity) && $resource_entity->status->value == FALSE) {
          $status = 'false';
        }
        // Convert all timestamp to date format.
        if ($resource_entity->hasField('visible_start_date')) {
          $startdate = DrupalDateTime::createFromTimestamp($resource_entity->visible_start_date->value);
        }
        if ($resource_entity->hasField('visible_end_date')) {
          $enddate = DrupalDateTime::createFromTimestamp($resource_entity->visible_end_date->value);
        }
        if ($resource_entity->hasField('webinar_event_date')) {
          $webeventdate = DrupalDateTime::createFromTimestamp($resource_entity->webinar_event_date->value);
        }
        // Get the default category, keywords and type.
        if ($resource_entity->hasField('category') && !empty($resource_entity->get('category')->getValue())) {
          $categories = [];
          foreach ($resource_entity->get('category')->getValue() as $category) {
            $categories[$category['target_id']] = $category['target_id'];
          }
        }
        if ($resource_entity->hasField('keywords') && !empty($resource_entity->get('keywords')->getValue())) {
          $defaultkeyword = [];
          foreach ($resource_entity->get('keywords')->getValue() as $keyword) {
            $defaultkeyword[$keyword['target_id']] = $keyword['target_id'];
          }
        }
        if ($resource_entity->hasField('type') && !empty($resource_entity->get('type')->getValue())) {
          $defaulttype = [];
          foreach ($resource_entity->get('type')->getValue() as $type) {
            $defaulttype[$type['target_id']] = $type['target_id'];
          }
        }
      }
      else {
        $webinar_options = $this->helper->getWebinarsDatas();
        // If there is no id.
        $form['form_markup'] = [
          '#type' => 'markup',
          '#markup' => '<h3 class="text-dark">Resource PDF id not found</h3>',
        ];
        return $form;
      }
    }

    $form['#attributes']['class'][] = 'event-add-edit';
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Webinar Event Title'),
      '#required' => TRUE,
      '#default_value' => isset($resource_entity) && $resource_entity->hasField('event_title') ? $resource_entity->event_title->value : '',
    ];
    $form['presenter'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Presenter(s)'),
      '#default_value' => isset($resource_entity) && $resource_entity->hasField('presenter') ? $resource_entity->presenter->value : '',
    ];

    // Fetch keywords using a dynamic query.
    $event_query = $this->database->select('taxonomy_term_field_data', 't');
    $event_query->fields('t', ['tid', 'name'])
      // Replace 'rl_keywords' with your vocabulary machine name.
      ->condition('t.vid', 'webinar')
      // Filter by the current language.
      ->condition('t.langcode', $current_language);

    // Execute the query and fetch the results.
    $results = $event_query->execute()->fetchAll();

    // Populate options array for checkbox field.
    $weboptions = [];
    foreach ($results as $record) {
      $weboptions[$record->tid] = $record->name;
    }

    $form['webinar_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Type'),
      '#options' => $weboptions,
      '#default_value' => !empty($defaulttype) ? $defaulttype : '',
      '#prefix' => '<div class="fs-row"><div class="fs-col-6">',
      '#suffix' => '</div>',
    ];
    $form['serial_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Series Number'),
      '#prefix' => '<div class="fs-col-6">',
      '#suffix' => '</div></div>',
      '#default_value' => isset($resource_entity) && $resource_entity->hasField('serial_number') ? $resource_entity->serial_number->value : '',
    ];
    $form['item_cost'] = [
      '#type' => 'radios',
      '#title' => $this->t('Item Cost'),
      '#options' => [
        'complimentary' => $this->t('Complimentary'),
        'priced' => $this->t('Sells for a Price'),
      ],
      '#field_prefix' => '<div class="form-inline">',
      '#field_suffix' => '</div>',
      '#default_value' => isset($resource_entity) && $resource_entity->hasField('item_cost') ? $resource_entity->item_cost->value : 'complimentary',
    ];
    $form['event_price'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Price'),
      '#prefix' => '<div class="fs-row"><div class="fs-col-6">',
      '#suffix' => '</div>',
      '#default_value' => isset($resource_entity) && $resource_entity->hasField('price') ? $resource_entity->price->value : '',
    ];
    $form['nav_item_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('NAV Item Number'),
      '#prefix' => '<div class="fs-col-6">',
      '#suffix' => '</div></div>',
      '#default_value' => isset($resource_entity) && $resource_entity->hasField('nav_item_number') ? $resource_entity->nav_item_number->value : '',
    ];
    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description/Overview'),
      '#required' => TRUE,
      '#default_value' => $resource_entity->description->value ?? '',
    ];
    if (!empty($thumb_default_values)) {
      // Load file entities corresponding to the target IDs.
      $thumbnail_files = $this->entityTypeManager->getStorage('file')->loadMultiple($thumb_default_values);

      // Extract file IDs from the loaded file entities.
      $default_file_ids = [];
      foreach ($thumbnail_files as $file) {
        $thumb_file_ids[] = $file->id();
      }
    }
    $form['thumbnail_upload'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Thumbnail'),
      '#description' => $this->t('Accepted file types: %file_types', ['%file_types' => '.gif, .jpeg, .png, .jpg']),
      '#chosen' => TRUE,
      '#upload_location' => 'public://thumbnails/',
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpeg gif jpg'],
      ],
      '#theme' => 'image_widget',
      '#preview_image_style' => 'thumbnail',
      '#default_value' => $thumb_file_ids ?? '',
    ];
    $form['visible_start_date'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Visible Start Date'),
      '#default_value' => $startdate ?? '',
      '#date_date_format' => 'Y-m-d',
      '#date_time_format' => 'H:i:s',
      '#attributes' => ['step' => 0],
      '#date_increment' => 15,
      '#required' => TRUE,
      '#prefix' => '<div class="fs-row"><div class="fs-col-6 form-inline form-inline-date">',
      '#suffix' => '</div>',
    ];

    $form['visible_end_date'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Visible End Date'),
      '#default_value' => $enddate ?? '',
      '#date_date_format' => 'Y-m-d',
      '#date_time_format' => 'H:i:s',
      '#attributes' => ['step' => 0],
      '#date_increment' => 15,
      '#required' => TRUE,
      '#prefix' => '<div class="fs-col-6 form-inline form-inline-date">',
      '#suffix' => '</div></div>',
    ];

    $form['webinar_event_date'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Webinar Event Date'),
      '#default_value' => $webeventdate ?? '',
      '#date_date_format' => 'Y-m-d',
      '#date_time_format' => 'H:i:s',
      '#attributes' => ['step' => 0],
      '#date_increment' => 15,
      '#required' => TRUE,
      '#prefix' => '<div class="fs-row"><div class="fs-col-6 form-inline form-inline-date">',
      '#suffix' => '</div></div>',
    ];
    // Get the list of timezones.
    $timezones = system_time_zones();

    // Remove the USA timezone from the list.
    // You can change this to the appropriate USA timezone.
    $usa_timezone = 'America/New_York';
    unset($timezones[$usa_timezone]);

    // Add the USA timezone to the top of the list.
    $timezones = [$usa_timezone => $usa_timezone] + $timezones;
    $form['timezone'] = [
      '#type' => 'select',
      '#title' => $this->t('Time Zone'),
      '#options' => $timezones,
      '#default_value' => isset($resource_entity) && $resource_entity->hasField('timezone') ? $resource_entity->get('timezone')->value : '',
    ];
    $form['certificate'] = [
      '#type' => 'radios',
      '#title' => $this->t('Certificate of Completion'),
      '#options' => [
        1 => $this->t('Yes'),
        0 => $this->t('No'),
      ],
      '#prefix' => '<div class="attendance form-inline m-bottom-4 position-relative">',
      // Set the default value to 'No' (0).
      '#default_value' => isset($resource_entity) && $resource_entity->hasField('certificate') ? $resource_entity->get('certificate')->value : 0,
      '#attributes' => [
        'class' => ['certificate-radio'],
      ],
    ];

    $form['completion_percent'] = [
      '#type' => 'textfield',
      '#placeholder' => $this->t('% of time in attendance'),
      '#suffix' => '</div>',
      '#default_value' => isset($resource_entity) && $resource_entity->hasField('certificate_completion') ? $resource_entity->certificate_completion->value : '',
      '#attributes' => [
        'class' => ['completion-percent-field'],
      ],
    ];

    $form['publish'] = [
      '#type' => 'radios',
      '#title' => $this->t('Publish video to:'),
      '#options' => [
        'all' => $this->t('All users'),
        'attendees' => $this->t('Event attendees only'),
        'unpublish' => $this->t('Do not publish'),
      ],
      '#prefix' => '<div class="form-inline">',
      '#suffix' => '</div>',
      '#default_value' => isset($resource_entity) && $resource_entity->hasField('publish') ? $resource_entity->get('publish')->value : 'all',
    ];

    // Fetch keywords using a dynamic query.
    $event_query = $this->database->select('taxonomy_term_field_data', 't');
    $event_query->fields('t', ['tid', 'name'])
      // Replace 'rl_keywords' with your vocabulary machine name.
      ->condition('t.vid', 'event_category')
      // Filter by the current language.
      ->condition('t.langcode', $current_language);

    // Execute the query and fetch the results.
    $results = $event_query->execute()->fetchAll();

    // Populate options array for checkbox field.
    $catoptions = [];
    foreach ($results as $record) {

      $catoptions[$record->tid] = $record->name;
    }

    $form['categories'] = [
      '#type' => 'select',
      '#title' => $this->t('Categories'),
      '#options' => $catoptions,
      '#multiple' => TRUE,
      '#chosen' => TRUE,
      '#prefix' => '<div class="form-inline-cat">',
      '#suffix' => '</div>',
      '#default_value' => !empty($categories) ? $categories : '',
      '#attributes' => ['class' => ['chosen-placeholder']],
    ];

    // Fetch keywords using a dynamic query.
    $event_query = $this->database->select('taxonomy_term_field_data', 't');
    $event_query->fields('t', ['tid', 'name'])
      // Replace 'rl_keywords' with your vocabulary machine name.
      ->condition('t.vid', 'rl_keywords')
      // Filter by the current language.
      ->condition('t.langcode', $current_language);

    // Execute the query and fetch the results.
    $results = $event_query->execute()->fetchAll();

    // Populate options array for checkbox field.
    $keyoptions = [];
    foreach ($results as $record) {

      $keyoptions[$record->tid] = $record->name;
    }
    $form['keywords'] = [
      '#type' => 'select',
      '#title' => $this->t('Keywords'),
      '#options' => $keyoptions,
      '#chosen' => TRUE,
      '#multiple' => TRUE,
      '#prefix' => '<div class="form-inline-cat">',
      '#suffix' => '</div>',
      '#default_value' => !empty($defaultkeyword) ? $defaultkeyword : '',
    ];
    $validators = [
      'file_validate_extensions' => ['pdf docx doc'],
    ];
    // Load file entities corresponding to the target IDs.
    if (!empty($doc_default_values)) {
      $files = $this->entityTypeManager->getStorage('file')->loadMultiple($doc_default_values);
      // Extract file IDs from the loaded file entities.
      $default_file_ids = [];
      foreach ($files as $file) {
        $default_file_ids[] = $file->id();
      }
    }
    $form['attach_file'] = [
      '#type' => 'managed_file',
      '#name' => 'Attach File',
      '#title' => $this->t('Attach files?'),
      '#size' => 30,
      '#chosen' => TRUE,
      '#multiple' => TRUE,
      '#description' => $this->t('Accepted file types: %file_types', ['%file_types' => '.pdf,.docx']),
      '#upload_validators' => $validators,
      '#upload_location' => 'public://pdf_resource/files/',
      '#default_value' => $default_file_ids ?? NULL,
    ];
    $form['zoom_link'] = [
      '#type' => 'select',
      '#title' => $this->t('Zoom Link'),
      '#options' => $webinar_options ?? [],
      '#default_value' => isset($resource_entity) && $resource_entity->hasField('heart_webinar_reference') ? $resource_entity->heart_webinar_reference->target_id : '',
    ];

    $form['promot_to_front'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Promote to front page'),
      '#default_value' => 0,
    ];

    $form['status'] = [
      '#type' => 'radios',
      '#title' => $this->t('Publish to calendar?'),
      '#required' => TRUE,
      '#options' => [
        'true' => $this->t('Publish'),
        'false' => $this->t('Unpublish'),
      ],
      '#prefix' => '<div class="form-inline">',
      '#suffix' => '</div>',
      '#default_value' => $status,
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('save'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();
    // Check start date is less than end date.
    if ($values['visible_start_date'] >= $values['visible_end_date']) {
      $form_state->setErrorByName('visible_start_date', $this->t('Start date should be less than end date.'));
    }
    // Check complete percent is an integer.
    if ($values['certificate'] == '1' && !is_numeric($values['completion_percent'])) {
      $form_state->setErrorByName('completion_percent', $this->t('Please enter a valid integer value.'));
    }
    // If price is empty.
    if ($values['item_cost'] == 'priced' && $values['event_price'] == '') {
      $form_state->setErrorByName('event_price', $this->t('Please provide price'));
    }
    // If NAV Item Number is empty.
    // if ($values['item_cost'] == 'priced' && $values['nav_item_number'] == '') {
    //   $form_state->setErrorByName('nav_item_number', $this->t('Please add NAV Item Number'));
    // }.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();
    $current_language = $this->languageManager->getCurrentLanguage()->getId();

    // Get the timestamp of datetime form elements.
    if (!empty($values)) {
      $promot_front = ($values['promot_to_front'] == 1) ? TRUE : FALSE;
      $startdate_object = $values['visible_start_date'];
      if (!empty($startdate_object)) {
        $starttimestamp = $startdate_object->getTimestamp();
      }
      $enddate_object = $values['visible_end_date'];
      if (!empty($enddate_object)) {
        $endtimestamp = $enddate_object->getTimestamp();
      }
      $webdate_object = $values['webinar_event_date'];
      if (!empty($enddate_object)) {
        $webdatetimestamp = $webdate_object->getTimestamp();
      }
      // Set the publish or not status.
      $status = TRUE;
      if ($values['status'] == 'false') {
        $status = FALSE;
      }

      $attachfile = $values['attach_file'] ?? NULL;
      $thumbnail_file = $values['thumbnail_upload'] ?? NULL;

      if ($values['item_cost'] == 'complimentary') {
        $event_price = '';
        $nav_item_number = '';
      }
      else {
        $event_price = $values['event_price'];
        $nav_item_number = $values['nav_item_number'];
      }
      $zoomLink = '';
      if (!empty($values['zoom_link'])) {
        $webinarentity = $this->entityTypeManager->getStorage('heart_zoom_webinars')->load($values['zoom_link']);
        $zoomLink = $webinarentity->join_url->value;
      }
      // If event id is empty create new entity.
      if (array_key_exists('event_id', $values) == FALSE) {
        // Create new event.
        $custom_entity = $this->entityTypeManager
          ->getStorage('event')
          ->create([
            'event_title' => $values['title'],
            'presenter' => $values['presenter'] ?? '',
            'type' => !empty($values['webinar_type']) ? $values['webinar_type'] : [],
            'serial_number' => $values['serial_number'] ?? '',
            'item_cost' => $values['item_cost'] ?? '',
            'price' => $event_price ?? '',
            'nav_item_number' => $nav_item_number ?? '',
            'description' => $values['description'] ?? '',
            'thumbnail' => $thumbnail_file,
            'visible_start_date' => $starttimestamp ?? '',
            'visible_end_date' => $endtimestamp ?? '',
            'webinar_event_date' => $webdatetimestamp ?? '',
            'timezone' => $values['timezone'] ?? '',
            'certificate' => $values['certificate'],
            'certificate_completion' => $values['completion_percent'] ?? '',
            'publish' => $values['publish'] ?? '',
            'category' => !empty($values['categories']) ? $values['categories'] : [],
            'keywords' => !empty($values['keywords']) ? $values['keywords'] : [],
            'zoomlink' => !empty($zoomLink) ? $zoomLink : '',
            'heart_webinar_reference' => $values['zoom_link'],
            'attach_file' => $attachfile,
            'promot_to_front' => $promot_front,
            'status' => $status,
            'langcode' => $current_language,
          ]);
        $custom_entity->save();

        // Creating product.
        $product = $this->entityTypeManager->getStorage('commerce_product')->create([
          'type' => 'events',
          'title' => $values['title'],
          'body' => $values['description'],
          'field_event_price' => [
            'number' => $event_price ?? '',
            'currency_code' => 'USD',
          ],
          'field_event_reference' => $custom_entity->id(),
          'field_promote_to_front_page' => $promot_front,
          'stores' => 1,
          'status' => $status,
          'langcode' => $current_language,
        ]);
        $product->save();
        $sku = 'SKUE' . date("Y") . $custom_entity->id();

        // Create a product variation.
        $variation = $this->entityTypeManager->getStorage('commerce_product_variation')->create([
          'type' => 'default',
          'sku' => $sku,
          'status' => $status,
          'price' => [
            'number' => $event_price ?? '',
            'currency_code' => 'USD',
          ],
          'product_id' => $product->id(),
          'langcode' => $current_language,
        ]);

        // Save the product variation.
        $variation->save();

        // Assign the variation to the product.
        $product->addVariation($variation);
        $product->save();
        $this->message->addMessage($this->t("Event added Successfully."));
      }
      else {
        // Update Entity based on event id.
        $custom_entity = $this->entityTypeManager
          ->getStorage('event')->load($values['event_id']);

        if ($custom_entity->hasTranslation($current_language)) {
          $custom_entity = $custom_entity->getTranslation($current_language);
        }
        else {
          $custom_entity = $custom_entity->addTranslation($current_language);
        }

        $custom_entity->set('event_title', $values['title']);
        $custom_entity->set('presenter', $values['presenter']);
        $custom_entity->set('type', !empty($values['webinar_type']) ? $values['webinar_type'] : []);
        $custom_entity->set('serial_number', $values['serial_number'] ?? '');
        $custom_entity->set('item_cost', $values['item_cost']);
        $custom_entity->set('price', $event_price ?? '');
        $custom_entity->set('nav_item_number', $nav_item_number ?? '');
        $custom_entity->set('description', $values['description'] ?? '');
        $custom_entity->set('thumbnail', $thumbnail_file);
        $custom_entity->set('visible_start_date', $starttimestamp ?? '');
        $custom_entity->set('visible_end_date', $endtimestamp ?? '');
        $custom_entity->set('webinar_event_date', $webdatetimestamp ?? '');
        $custom_entity->set('timezone', $values['timezone'] ?? '');
        $custom_entity->set('certificate', $values['certificate'] ?? '');
        $custom_entity->set('certificate_completion', $values['completion_percent'] ?? '');
        $custom_entity->set('category', !empty($values['categories']) ? $values['categories'] : []);
        $custom_entity->set('keywords', !empty($values['keywords']) ? $values['keywords'] : []);
        $custom_entity->set('zoomlink', !empty($zoomLink) ? $zoomLink : []);
        $custom_entity->set('heart_webinar_reference', !empty($values['zoom_link']) ? $values['zoom_link'] : []);
        $custom_entity->set('attach_file', $attachfile);
        $custom_entity->set('promot_to_front', $promot_front);
        $custom_entity->set('publish', $values['publish'] ?? '');
        $custom_entity->set('status', $status);
        $custom_entity->save();

        // Updating product.
        $query = $this->entityTypeManager->getStorage('commerce_product')->getQuery();
        $query->condition('type', 'events');
        $query->accessCheck(FALSE);
        $query->condition('field_event_reference', $values['event_id']);
        $product_id_val = $query->execute();
        $product_id = reset($product_id_val);
        $product = $this->entityTypeManager->getStorage('commerce_product')->load($product_id);

        if ($product->hasTranslation($current_language)) {
          $product = $product->getTranslation($current_language);
        }
        else {
          $product = $product->addTranslation($current_language);
        }

        if ($product) {
          $product->setTitle($values['title']);
          $product->set('body', $values['description']);
          $product->set('field_event_price', [
            'number' => $event_price,
            'currency_code' => 'USD',
          ]);
          $product->set('field_promote_to_front_page', $promot_front);
          $product->set('stores', 1);
          $product->set('status', $status);
        }
        $variation_id = '';
        $product_variation_id = $product->getVariationIds();
        if (!empty($product_variation_id)) {
          $variation_id = reset($product_variation_id);
        }

        // Update the product variation.
        $variation = $this->entityTypeManager->getStorage('commerce_product_variation')->load($variation_id);
        if ($variation->hasTranslation($current_language)) {
          $variation = $variation->getTranslation($current_language);
        }
        else {
          $variation = $variation->addTranslation($current_language);
        }
        if ($variation) {
          $variation->setTitle($values['title']);
          $variation->set('price', [
            'number' => $event_price ?? 0,
            'currency_code' => 'USD',
            'language' => $current_language,
          ]);
          $variation->save();
          $product->save();
        }
        $product->save();
        $this->message->addMessage($this->t("Event updated Successfully."));
      }
    }
    $url = Url::fromUri('internal:/manage-content#calender-event-tab');
    $form_state->setRedirectUrl($url);
  }

}
