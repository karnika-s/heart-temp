<?php

declare(strict_types=1);

namespace Drupal\heart_custom_forms\Form;

use Drupal\Core\Database\Connection;
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
final class CourseProductForm extends FormBase {

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
    return 'heart_custom_forms_course_product_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $heart_course_id = $this->routematch->getParameter('heart_course_id');
    $course_product_id = $this->routematch->getParameter('course_product_id');
    $current_language = $this->languageManager->getCurrentLanguage()->getId();
    $status = 'true';
    // Check resource id is present or not in current url.
    if ($heart_course_id && $course_product_id) {
      $heart_course_entity = $this->entityTypeManager->getStorage('heart_course')->load($heart_course_id);
      $course_product_entity = $this->entityTypeManager->getStorage('course_product')->load($course_product_id);
      if ($heart_course_entity && $course_product_entity) {
        if ($heart_course_entity->hasTranslation($current_language)) {
          $heart_course_entity = $heart_course_entity->getTranslation($current_language);
        }
        else {
          $heart_course_entity = $heart_course_entity->addTranslation($current_language);
        }
        if ($course_product_entity->hasTranslation($current_language)) {
          $course_product_entity = $course_product_entity->getTranslation($current_language);
        }
        else {
          $course_product_entity = $course_product_entity->addTranslation($current_language);
        }

        if ($heart_course_entity->id() == $course_product_entity->heart_course_reference->target_id) {
          $form['heart_course_id'] = [
            '#type' => 'hidden',
            '#default_value' => $heart_course_id ?? '',
          ];

          $form['course_product_id'] = [
            '#type' => 'hidden',
            '#default_value' => $course_product_id ?? '',
          ];

          // Get default thumbnail image.
          if ($course_product_entity->hasField('thumbnail_image')) {
            $target_id = $course_product_entity->get('thumbnail_image')->target_id;
            if (!empty($target_id)) {
              $thumb_default_value = [$target_id];
            }
          }
          if ($course_product_entity->hasField('keywords') && !empty($course_product_entity->get('keywords')->getValue())) {
            $defaultkeyword = [];
            foreach ($course_product_entity->get('keywords')->getValue() as $keyword) {
              // Populate options array for select field.
              $defaultkeyword[$keyword['target_id']] = $keyword['target_id'];
            }
          }
          if ($course_product_entity->hasField('module_type') && !empty($course_product_entity->get('module_type')->getValue())) {
            $defaulttype = [];
            foreach ($course_product_entity->get('module_type')->getValue() as $type) {
              $defaulttype[$type['target_id']] = $type['target_id'];
            }
          }
          if ($course_product_entity->hasField('module_bundle') && !empty($course_product_entity->get('module_bundle')->getValue())) {
            $defaultbundle = [];
            foreach ($course_product_entity->get('module_bundle')->getValue() as $bundle) {
              $defaultbundle[$bundle['target_id']] = $bundle['target_id'];
            }
          }
          // Check resource is publish or not.
          if (isset($course_product_entity) && $course_product_entity->status->value == FALSE) {
            $status = 'false';
          }
        }
        else {
          // If there is no id.
          $form['form_markup'] = [
            '#type' => 'markup',
            '#markup' => '<h3 class="text-dark">Product not found</h3>',
          ];
          return $form;
        }
      }
    }
    if ($heart_course_id) {
      $heart_course_entity = $this->entityTypeManager->getStorage('heart_course')->load($heart_course_id);
      if ($heart_course_entity) {
        if ($heart_course_entity->hasTranslation($current_language)) {
          $heart_course_entity = $heart_course_entity->getTranslation($current_language);
        }
        else {
          $heart_course_entity = $heart_course_entity->addTranslation($current_language);
        }
        $form['title'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Product Title'),
          '#required' => TRUE,
          '#default_value' => $course_product_entity->product_title->value ?? '',
        ];
        $form['description'] = [
          '#type' => 'textarea',
          '#title' => $this->t('Description'),
          '#required' => TRUE,
          '#default_value' => $course_product_entity->description->value ?? '',
        ];

        $form['thumbnail_upload'] = [
          '#type' => 'managed_file',
          '#title' => $this->t('Replace Thumbnail'),
          '#description' => $this->t('Accepted file types<br>Image (GIF) .gif<br>Image (JPEG) .jpeg<br>Image (PNG) .png'),
          '#upload_location' => 'public://thumbnails/',
          '#upload_validators' => [
            'file_validate_extensions' => ['png jpeg gif'],
          ],
          '#theme' => 'image_widget',
          '#preview_image_style' => 'thumbnail',
          '#default_value' => $thumb_default_value ?? '',
        ];

        $form['price'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Price'),
          '#states' => [
            'visible' => [
              ':input[name="item_cost"]' => ['value' => 'priced'],
            ],
          ],
          '#prefix' => '<div class="fs-row"><div class="fs-col-6">',
          '#suffix' => '</div>',
          '#default_value' => isset($course_product_entity) && $course_product_entity->hasField('price') ? $course_product_entity->price->value : '',
        ];

        $form['isbn'] = [
          '#type' => 'textfield',
          '#title' => $this->t('ISBN'),

          '#default_value' => isset($course_product_entity) && $course_product_entity->hasField('isbn') ? $course_product_entity->isbn->value : '',
        ];

        // Fetch keywords using a dynamic query.
        $query = $this->database->select('taxonomy_term_field_data', 't');
        $query->fields('t', ['tid', 'name'])
          // Replace 'rl_keywords' with your vocabulary machine name.
          ->condition('t.vid', 'rl_keywords')
          // Filter by the current language.
          ->condition('t.langcode', $current_language);

        // Execute the query and fetch the results.
        $results = $query->execute()->fetchAll();

        // Populate options array for the checkbox field.
        $keyoptions = [];
        foreach ($results as $record) {
          $keyoptions[$record->tid] = $record->name;
        }
        $form['keywords'] = [
          '#type' => 'select',
          '#title' => $this->t('Keywords'),
          '#options' => $keyoptions,
          '#multiple' => TRUE,
          '#chosen' => TRUE,
          '#default_value' => !empty($defaultkeyword) ? $defaultkeyword : '',
          '#prefix' => '<div class="form-inline-cat">',
          '#suffix' => '</div>',
        ];

        // Fetch module type using a dynamic query.
        $type_query = $this->database->select('taxonomy_term_field_data', 't');
        $type_query->fields('t', ['tid', 'name'])
          // Replace 'rl_keywords' with your vocabulary machine name.
          ->condition('t.vid', 'module_type')
          // Filter by the current language.
          ->condition('t.langcode', $current_language);

        // Execute the query and fetch the results.
        $results = $type_query->execute()->fetchAll();

        // Populate options array for checkbox field.
        $typeoptions = [];
        foreach ($results as $record) {
          $typeoptions[$record->tid] = $record->name;
        }

        $form['module_type'] = [
          '#type' => 'select',
          '#title' => $this->t('Module Type'),
          '#options' => $typeoptions,
          '#default_value' => !empty($defaulttype) ? $defaulttype : '',
          '#prefix' => '<div class="form-inline-cat">',
          '#suffix' => '</div>',
        ];

        // Fetch module type using a dynamic query.
        $bundle_query = $this->database->select('taxonomy_term_field_data', 't');
        $bundle_query->fields('t', ['tid', 'name'])
          // Replace 'rl_keywords' with your vocabulary machine name.
          ->condition('t.vid', 'module_bundle')
          // Filter by the current language.
          ->condition('t.langcode', $current_language);

        // Execute the query and fetch the results.
        $results = $bundle_query->execute()->fetchAll();

        // Populate options array for checkbox field.
        $bundleoptions = [];
        foreach ($results as $record) {
          // Check if the term is a top-level term.
          $bundleoptions[$record->tid] = $record->name;
        }

        $form['module_bundle'] = [
          '#type' => 'select',
          '#title' => $this->t('Module bundle'),
          '#options' => $bundleoptions,
          '#default_value' => !empty($defaultbundle) ? $defaultbundle : '',
          '#prefix' => '<div class="form-inline-cat">',
          '#suffix' => '</div>',
        ];

        $form['status'] = [
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
    }
    else {
      // If there is no id.
      $form['form_markup'] = [
        '#type' => 'markup',
        '#markup' => '<h3 class="text-dark">Product formation not found</h3>',
      ];
      return $form;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();
    // If price is empty or not numeric.
    if ($values['price'] == '' &&  !is_numeric($values['price'])) {
      $form_state->setErrorByName('price', $this->t('Please provide price'));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();
    $heart_course_id = $this->routematch->getParameter('heart_course_id');
    $current_language = $this->languageManager->getCurrentLanguage()->getId();
    if (!empty($values)) {

      $status = TRUE;
      // Check whether entity is published or not.
      if ($values['status'] == 'false') {
        $status = FALSE;
      }
      // Get the thumbnail.
      $thumbnail_file = $values['thumbnail_upload'][0] ?? NULL;

      // If there is no resource id, create new resource.
      if (array_key_exists('course_product_id', $values) == FALSE) {

        // Create new resource pdf entity.
        $course_product_entity = $this->entityTypeManager
          ->getStorage('course_product')
          ->create([
            'product_title' => $values['title'],
            'price' => $values['price'] ?? '',
            'description' => $values['description'] ?? '',
            'module_type' => $values['module_type'] ?? '',
            'module_bundle' => $values['module_bundle'] ?? '',
            'thumbnail_image' => $thumbnail_file,
            'isbn' => $values['isbn'] ?? '',
            'keywords' => !empty($values['keywords']) ? $values['keywords'] : [],
            'status' => $status,
            'heart_course_reference' => $heart_course_id,
            'langcode' => $current_language,
          ]);
        $course_product_entity->save();

        // Create new resource pdf product.
        $product_type_id = 'course';
        $product = $this->entityTypeManager->getStorage('commerce_product')->create([
          'type' => $product_type_id,
          'title' => $values['title'],
          'body' => $values['description'],
          'field_price' => [
            'number' => $values['price'] ?? '',
            'currency_code' => 'USD',
          ],
          'field_course_product' => $course_product_entity->id(),
          'stores' => 1,
          'status' => $status,
          'langcode' => $current_language,
        ]);
        $product->save();

        $sku = 'SKUCP' . date("Y") . $course_product_entity->id();

        // Create a product variation.
        $variation = $this->entityTypeManager->getStorage('commerce_product_variation')->create([
          'type' => 'default',
          'sku' => $sku,
          'status' => $status,
          'price' => [
            'number' => $values['price'] ?? '',
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

        $this->message->addMessage($this->t("Course Product added Successfully."));
      }
      else {
        // Update existing resource pdf entity.
        $custom_entity = $this->entityTypeManager
          ->getStorage('course_product')->load($values['course_product_id']);

        if ($custom_entity->hasTranslation($current_language)) {
          $custom_entity = $custom_entity->getTranslation($current_language);
        }
        else {
          $custom_entity = $custom_entity->addTranslation($current_language);
        }
        $custom_entity->set('product_title', $values['title']);
        $custom_entity->set('description', $values['description'] ?? '');
        $custom_entity->set('module_type', $values['module_type'] ?? '');
        $custom_entity->set('module_bundle', $values['module_bundle'] ?? '');
        $custom_entity->set('price', $values['price'] ?? '');
        $custom_entity->set('isbn', $values['isbn'] ?? '');
        $custom_entity->set('thumbnail_image', $thumbnail_file);
        $custom_entity->set('keywords', !empty($values['keywords']) ? $values['keywords'] : []);
        $custom_entity->set('status', $status);
        $custom_entity->save();

        // Update existing resource pdf product.
        $query = $this->entityTypeManager->getStorage('commerce_product')->getQuery();
        $query->condition('type', 'course');
        $query->accessCheck(FALSE);
        $query->condition('field_course_product', $values['course_product_id']);
        $product_id_val = $query->execute();
        $product_id = reset($product_id_val);
        $product = $this->entityTypeManager->getStorage('commerce_product')->load($product_id);
        if ($product) {
          if ($product->hasTranslation($current_language)) {
            $product = $product->getTranslation($current_language);
          }
          else {
            $product = $product->addTranslation($current_language);
          }

          $product->setTitle($values['title']);
          $product->set('body', $values['description']);
          $product->set('field_price', [
            'number' => $values['price'],
            'currency_code' => 'USD',
          ]);
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
        if ($variation) {
          if ($variation->hasTranslation($current_language)) {
            $variation = $variation->getTranslation($current_language);
          }
          else {
            $variation = $variation->addTranslation($current_language);
          }
          $variation->setTitle($values['title']);
          $variation->set('price', [
            'number' => $values['price'] ?? 0,
            'currency_code' => 'USD',
          ]);
          $variation->save();
          $product->save();
        }
        $product->save();
        $this->message->addMessage($this->t("Product updated Successfully."));
      }
    }
  }

}
