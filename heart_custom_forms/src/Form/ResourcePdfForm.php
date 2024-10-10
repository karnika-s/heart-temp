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
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Heart Custom Forms form.
 */
final class ResourcePdfForm extends FormBase {

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
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, CurrentPathStack $current_path, AccountInterface $current_user, RouteMatchInterface $route_match, MessengerInterface $message, LanguageManagerInterface $language_manager, Connection $database) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentPath = $current_path;
    $this->currentUser = $current_user;
    $this->routematch = $route_match;
    $this->message = $message;
    $this->languageManager = $language_manager;
    $this->database = $database;
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
      $container->get('database')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'heart_custom_forms_add_pdf_resource';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    if (\Drupal::request()->query->get('id') && \Drupal::request()->query->get('entity') == 'pdf') {
      $resource_id = \Drupal::request()->query->get('id');
    }
    else {
      $resource_id = $this->routematch->getParameter('id');
    }
    $status = 'true';
    $current_language = $this->languageManager->getCurrentLanguage()->getId();
    // Check resource id is present or not in current url.
    if ($resource_id) {
      $resource_entity = $this->entityTypeManager->getStorage('pdf_resource')->load($resource_id);
      if (!empty($resource_entity)) {
        if ($resource_entity->hasTranslation($current_language)) {
          $resource_entity = $resource_entity->getTranslation($current_language);
        }
        else {
          $resource_entity = $resource_entity->addTranslation($current_language);
        }

        $form['resource_id'] = [
          '#type' => 'hidden',
          '#default_value' => $resource_id ?? '',
        ];
        // Get default attach file.
        if ($resource_entity->hasField('upload_document')) {
          $target_id = $resource_entity->get('upload_document')->target_id;
          if (!empty($target_id)) {
            $doc_default_value = [$target_id];
          }
        }
        // Get default thumbnail image.
        if ($resource_entity->hasField('replace_thumbnail')) {
          $target_id = $resource_entity->get('replace_thumbnail')->target_id;
          if (!empty($target_id)) {
            $thumb_default_value = [$target_id];
          }
        }
        // Check resource is publish or not.
        if (isset($resource_entity) && $resource_entity->status->value == FALSE) {
          $status = 'false';
        }
        // Convert all timestamp to date format.p.
        if ($resource_entity->hasField('visible_start_date')) {
          $startdate = NULL;
          if ($resource_entity->visible_start_date->value != 0) {
            $startdate = DrupalDateTime::createFromTimestamp($resource_entity->visible_start_date->value);
          }
        }
        if ($resource_entity->hasField('visible_end_date')) {
          $enddate = NULL;
          if ($resource_entity->visible_end_date->value != 0) {
            $enddate = DrupalDateTime::createFromTimestamp($resource_entity->visible_end_date->value);
          }
        }
        // Get the default category, keywords.
        if ($resource_entity->hasField('rl_category') && !empty($resource_entity->get('rl_category')->getValue())) {
          $categories = [];
          foreach ($resource_entity->get('rl_category')->getValue() as $category) {
            $categories[$category['target_id']] = $category['target_id'];
          }
        }
        if ($resource_entity->hasField('rl_keywords') && !empty($resource_entity->get('rl_keywords')->getValue())) {
          $defaultkeyword = [];
          foreach ($resource_entity->get('rl_keywords')->getValue() as $keyword) {
            // Populate options array for select field.
            $defaultkeyword[$keyword['target_id']] = $keyword['target_id'];
          }
        }
      }
      else {
        // If there is no id.
        $form['form_markup'] = [
          '#type' => 'markup',
          '#markup' => '<h3 class="text-dark">Resource PDF id not found</h3>',
        ];
        return $form;
      }
    }
    $form['group_left'] = [
      '#prefix' => '<div class="fs-row"><div class="fs-col-6">',
      '#suffix' => '</div>',
    ];
    $form['group_right'] = [
      '#prefix' => '<div class="fs-col-6">',
      '#suffix' => '</div></div>',
    ];
    $form['group_left']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title of Product'),
      '#required' => TRUE,
      '#default_value' => $resource_entity->product_title->value ?? '',
    ];
    $form['group_left']['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description/Overview'),
      '#required' => TRUE,
      '#default_value' => $resource_entity->description->value ?? '',
    ];
    $validators = [
      'file_validate_extensions' => ['pdf docx'],
    ];

    $form['group_left']['doc_file'] = [
      '#type' => 'managed_file',
      '#name' => 'Upload File',
      '#title' => $this->t('Upload Document?'),
      '#size' => 30,
      '#required' => TRUE,
      '#description' => $this->t('Accepted file types: %file_types', ['%file_types' => '.pdf,.docx']),
      '#upload_validators' => $validators,
      '#upload_location' => 'public://pdf_resource/files/',
      '#default_value' => $doc_default_value ?? NULL,
    ];
    $form['group_left']['thumbnail_upload'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Replace Thumbnail'),
      '#required' => TRUE,
      '#description' => $this->t('Accepted file types: %file_types', ['%file_types' => '.gif, .jpeg, .png, .jpg']),
      '#upload_location' => 'public://thumbnails/',
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpeg gif'],
      ],
      '#theme' => 'image_widget',
      '#preview_image_style' => 'thumbnail',
      '#default_value' => $thumb_default_value ?? '',
    ];

    $form['group_right']['edition_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Edition Number'),
      '#default_value' => isset($resource_entity) && $resource_entity->hasField('edition_number') ? $resource_entity->edition_number->value : '',
    ];

    $form['group_right']['item_cost'] = [
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

    $form['group_right']['rl_price'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Price'),
      '#states' => [
        'visible' => [
          ':input[name="item_cost"]' => ['value' => 'priced'],
        ],
      ],
      '#prefix' => '<div class="fs-row"><div class="fs-col-6">',
      '#suffix' => '</div>',
      '#default_value' => isset($resource_entity) && $resource_entity->hasField('rl_price') ? $resource_entity->rl_price->value : '',
    ];

    $form['group_right']['nav_item_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('NAV Item Number'),
      '#states' => [
        'visible' => [
          ':input[name="item_cost"]' => ['value' => 'priced'],
        ],
      ],
      '#prefix' => '<div class="fs-col-6">',
      '#suffix' => '</div></div>',
      '#default_value' => isset($resource_entity) && $resource_entity->hasField('nav_item_number') ? $resource_entity->nav_item_number->value : '',
    ];

    $form['group_right']['visible_start_date'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Visible Start Date'),
      '#default_value' => $startdate ?? '',
      '#date_date_format' => 'Y-m-d',
      '#date_time_format' => 'H:i:s',
      '#attributes' => ['step' => 0],
      '#date_increment' => 15,

      '#prefix' => '<div class="fs-row"><div class="fs-col-6 form-inline form-inline-date">',
      '#suffix' => '</div>',
    ];
    $form['group_right']['visible_end_date'] = [
      '#type' => 'datetime',
      '#title' => $this->t('Visible End Date'),
      '#default_value' => $enddate ?? '',
      '#date_date_format' => 'Y-m-d',
      '#date_time_format' => 'H:i:s',
      '#attributes' => ['step' => 0],
      '#date_increment' => 15,

      '#prefix' => '<div class="fs-col-6 form-inline form-inline-date">',
      '#suffix' => '</div></div>',
    ];

    // Fetch keywords using a dynamic query.
    $category_query = $this->database->select('taxonomy_term_field_data', 't');
    $category_query->fields('t', ['tid', 'name'])
      // Replace 'rl_keywords' with your vocabulary machine name.
      ->condition('t.vid', 'rl_categories')
      // Filter by the current language.
      ->condition('t.langcode', $current_language);

    // Execute the query and fetch the results.
    $results = $category_query->execute()->fetchAll();

    // Populate options array for checkbox field.
    $catoptions = [];
    foreach ($results as $record) {

      $catoptions[$record->tid] = $record->name;
    }

    $form['group_right']['categories'] = [
      '#type' => 'select',
      '#title' => $this->t('Categories'),
      '#options' => $catoptions,
      '#multiple' => TRUE,
      '#chosen' => TRUE,
      '#required' => TRUE,
      '#default_value' => !empty($categories) ? $categories : '',
      '#prefix' => '<div class="form-inline-cat">',
      '#suffix' => '</div>',
    ];
    // Fetch keywords using a dynamic query.
    $keyword_query = $this->database->select('taxonomy_term_field_data', 't');
    $keyword_query->fields('t', ['tid', 'name'])
      // Replace 'rl_keywords' with your vocabulary machine name.
      ->condition('t.vid', 'rl_keywords')
      // Filter by the current language.
      ->condition('t.langcode', $current_language);

    // Execute the query and fetch the results.
    $results = $keyword_query->execute()->fetchAll();

    // Populate options array for checkbox field.
    $keyoptions = [];
    foreach ($results as $record) {

      $keyoptions[$record->tid] = $record->name;
    }
    $form['group_right']['keywords'] = [
      '#type' => 'select',
      '#title' => $this->t('Keywords'),
      '#options' => $keyoptions,
      '#multiple' => TRUE,
      '#chosen' => TRUE,
      '#required' => TRUE,
      '#default_value' => !empty($defaultkeyword) ? $defaultkeyword : '',
      '#prefix' => '<div class="form-inline-cat">',
      '#suffix' => '</div>',
    ];

    $form['group_right']['promot_to_front'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Promote to front page'),
      '#default_value' => 0,
    ];

    $form['group_right']['status'] = [
      '#type' => 'radios',
      '#title' => $this->t('Publish resource?'),
      '#required' => TRUE,
      '#options' => [
        'true' => $this->t('Publish'),
        'false' => $this->t('UnPublish'),
      ],
      '#field_prefix' => '<div class="form-inline">',
      '#field_suffix' => '</div>',
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

    if ($values['item_cost'] == 'priced' && $values['rl_price'] == '') {
      $form_state->setErrorByName('rl_price', $this->t('Please provide price'));
    }
    if ($values['item_cost'] == 'priced' && $values['nav_item_number'] == '') {
      $form_state->setErrorByName('nav_item_number', $this->t('Please add NAV Item Number'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();
    $current_language = $this->languageManager->getCurrentLanguage()->getId();
    if (!empty($values)) {
      $promot_front = ($values['promot_to_front'] == 1) ? TRUE : FALSE;

      // Convert date objects to timestamps.
      $startdate_object = $values['visible_start_date'];
      if (!empty($startdate_object)) {
        $starttimestamp = $startdate_object->getTimestamp();
      }
      $enddate_object = $values['visible_end_date'];
      if (!empty($enddate_object)) {
        $endtimestamp = $enddate_object->getTimestamp();
      }
      $status = TRUE;
      // Check whether entity is published or not.
      if ($values['status'] == 'false') {
        $status = FALSE;
      }
      // Get the uploaded file and thumbnails.
      $docfile = $values['doc_file'][0] ?? NULL;
      $thumbnail_file = $values['thumbnail_upload'][0] ?? NULL;
      if ($values['item_cost'] == 'complimentary') {
        $rl_price = '';
        $nav_item_number = '';
      }
      else {
        $rl_price = $values['rl_price'];
        $nav_item_number = $values['nav_item_number'];
      }

      // If there is no resource id, create new resource.
      if (array_key_exists('resource_id', $values) == FALSE) {

        // Create new resource pdf entity.
        $custom_entity = $this->entityTypeManager
          ->getStorage('pdf_resource')
          ->create([
            'product_title' => $values['title'],
            'edition_number' => $values['edition_number'] ?? '',
            'description' => $values['description'] ?? '',
            'item_cost' => $values['item_cost'] ?? '',
            'rl_price' => $rl_price ?? '',
            'nav_item_number' => $nav_item_number ?? '',
            'upload_document' => $docfile,
            'visible_start_date' => $starttimestamp ?? '',
            'visible_end_date' => $endtimestamp ?? '',
            'replace_thumbnail' => $thumbnail_file,
            'rl_category' => !empty($values['categories']) ? $values['categories'] : [],
            'rl_keywords' => !empty($values['keywords']) ? $values['keywords'] : [],
            'promot_to_front' => $promot_front,
            'status' => $status,
            'langcode' => $current_language,
          ]);
        $custom_entity->save();

        // Create new resource pdf product.
        $product_type_id = 'resource_library';
        $product = $this->entityTypeManager->getStorage('commerce_product')->create([
          'type' => $product_type_id,
          'title' => $values['title'],
          'body' => $values['description'],
          'field_price' => [
            'number' => $rl_price ?? '',
            'currency_code' => 'USD',
          ],
          'field_pdf_resource' => $custom_entity->id(),
          'field_promote_to_front_page' => $promot_front,
          'stores' => 1,
          'status' => $status,
          'langcode' => $current_language,
        ]);
        $product->save();

        $sku = 'SKURP' . date("Y") . $custom_entity->id();

        // Create a product variation.
        $variation = $this->entityTypeManager->getStorage('commerce_product_variation')->create([
          'type' => 'default',
          'sku' => $sku,
          'status' => $status,
          'price' => [
            'number' => $rl_price,
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

        $this->message->addMessage($this->t("PDF resource added Successfully."));
      }
      else {
        // Update existing resource pdf entity.
        $custom_entity = $this->entityTypeManager
          ->getStorage('pdf_resource')->load($values['resource_id']);

        if ($custom_entity->hasTranslation($current_language)) {
          $custom_entity = $custom_entity->getTranslation($current_language);
        }
        else {
          $custom_entity = $custom_entity->addTranslation($current_language);
        }

        $custom_entity->set('product_title', $values['title']);
        $custom_entity->set('edition_number', $values['edition_number'] ?? '');
        $custom_entity->set('description', $values['description'] ?? '');
        $custom_entity->set('item_cost', $values['item_cost']);
        $custom_entity->set('rl_price', $rl_price ?? '');
        $custom_entity->set('nav_item_number', $nav_item_number ?? '');
        $custom_entity->set('upload_document', $docfile);
        $custom_entity->set('visible_start_date', $starttimestamp ?? '');
        $custom_entity->set('visible_end_date', $endtimestamp ?? '');
        $custom_entity->set('replace_thumbnail', $thumbnail_file);
        $custom_entity->set('rl_category', !empty($values['categories']) ? $values['categories'] : []);
        $custom_entity->set('rl_keywords', !empty($values['keywords']) ? $values['keywords'] : []);
        $custom_entity->set('promot_to_front', $promot_front);
        $custom_entity->set('status', $status);
        $custom_entity->save();

        // Update existing resource pdf product.
        $query = $this->entityTypeManager->getStorage('commerce_product')->getQuery();
        $query->condition('type', 'resource_library');
        $query->accessCheck(FALSE);
        $query->condition('field_pdf_resource', $values['resource_id']);
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
          $product->set('field_price', [
            'number' => $rl_price,
            'currency_code' => 'USD',
          ]);
          $product->set('field_promote_to_front_page', $promot_front);
          $product->set('stores', 1);
          $product->set('status', $status);
        }
        $product->save();
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
            'number' => $rl_price ?? 0,
            'currency_code' => 'USD',
          ]);
          $variation->save();
          $product->save();
        }

        $this->message->addMessage($this->t("PDF resource updated Successfully."));
      }
    }
  }

}
