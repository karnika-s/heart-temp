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
 * Provides a Heart Course add/edit form.
 */
final class HeartCourseForm extends FormBase {

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
   * {@inheritdoc}
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
   * Get the form id.
   */
  public function getFormId(): string {
    return 'heart_custom_forms_heart_course_add_edit';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    // Get route parameters.
    $id = $this->routematch->getParameter('id');
    $status = 'true';

    $current_language = $this->languageManager->getCurrentLanguage()->getId();

    // Get current count of catechist bundle.
    $catechist_count = $form_state->get('catechist_count');
    $parish_paragraphs = [];
    // Check if an entity ID is present and
    // Ensure that the form is not being rebuilt.
    if ($id && !$form_state->isRebuilding()) {
      // Load heart course entity.
      $heart_course_entity = $this->entityTypeManager->getStorage('heart_course')->load($id);

      if ($heart_course_entity->hasTranslation($current_language)) {
        $heart_course_entity = $heart_course_entity->getTranslation($current_language);
      }
      else {
        $heart_course_entity = $heart_course_entity->addTranslation($current_language);
      }
      // Check heart course entity exist or not.
      if ($heart_course_entity) {

        // Check if catechist information field exist.
        if ($heart_course_entity->hasField('catechist_information')) {
          // Get paragraph entities of catechist information.
          $catechist_paragraphs = $heart_course_entity->get('catechist_information')->referencedEntities();

          // Check if catechist paragraph exist.
          if (!empty($catechist_paragraphs)) {
            // Set catechist count to count of catechist paragraph.
            $form_state->set('catechist_count', count($catechist_paragraphs));
            $catechist_count = count($catechist_paragraphs);
            // Store initial paragraph IDs to delete if them if removed.
            $initial_catechist_ids = [];
            foreach ($catechist_paragraphs as $paragraph) {
              $initial_catechist_ids[] = $paragraph->id();
            }
            $form_state->set('initial_catechist_ids', $initial_catechist_ids);
          }
        }
        // Check if parish information field exist.
        if ($heart_course_entity->hasField('parish_information')) {
          if ($heart_course_entity->parish_information->target_id) {
            // Fetch paragraph entity ofheart_course_entity->hasField('parish_information') parish information.
            $parish_paragraphs = $this->entityTypeManager->getStorage('paragraph')->load($heart_course_entity->parish_information->target_id);

            if ($parish_paragraphs->hasTranslation($current_language)) {
              $parish_paragraphs = $parish_paragraphs->getTranslation($current_language);
            }
            else {
              $parish_paragraphs = $parish_paragraphs->addTranslation($current_language);
            }
            // Get default attach file.
            if ($parish_paragraphs->hasField('field_parish_file')) {
              $target_id = $parish_paragraphs->get('field_parish_file')->target_id;
              if (!empty($target_id)) {
                $parish_default_file = [$target_id];
              }
            }
          }
        }
        // Check resource is publish or not.
        if (isset($heart_course_entity) && $heart_course_entity->status->value == FALSE) {
          $status = 'false';
        }
        // Get default attach banner image.
        if ($heart_course_entity->hasField('banner_image')) {
          $target_id = $heart_course_entity->get('banner_image')->target_id;
          if (!empty($target_id)) {
            $banner_default_img = [$target_id];
          }
        }
        // Get default attach thumbnail image.
        if ($heart_course_entity->hasField('thumbnail_image')) {
          $target_id = $heart_course_entity->get('thumbnail_image')->target_id;
          if (!empty($target_id)) {
            $thumb_default_img = [$target_id];
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

    // Ensure atlease one catechist bundle exist.
    if ($catechist_count === NULL) {
      $form_state->set('catechist_count', 1);
      $catechist_count = 1;
    }

    $form['#prefix'] = '<div class="wrapper-600">';
    $form['#suffix'] = '</div>';
    $form['course_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Course Name'),
      '#required' => TRUE,
      '#default_value' => isset($heart_course_entity) && $heart_course_entity->hasField('label') ? $heart_course_entity->label->value : '',
    ];

    $form['banner_upload'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Banner Upload'),
      '#description' => $this->t('Accepted file types<br>Image (GIF) .gif<br>Image (JPEG) .jpeg<br>Image (PNG) .png'),
      '#upload_location' => 'public://banners/',
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpeg gif'],
      ],
      '#theme' => 'image_widget',
      '#preview_image_style' => 'thumbnail',
      '#default_value' => $banner_default_img ?? '',
    ];

    $form['thumbnail_upload'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Thumbnail Upload'),
      '#description' => $this->t('Accepted file types<br>Image (GIF) .gif<br>Image (JPEG) .jpeg<br>Image (PNG) .png'),
      '#upload_location' => 'public://banners/',
      '#upload_validators' => [
        'file_validate_extensions' => ['png jpeg gif'],
      ],
      '#theme' => 'image_widget',
      '#preview_image_style' => 'thumbnail',
      '#default_value' => $thumb_default_img ?? '',
    ];

    $form['description'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Description'),
      '#required' => TRUE,
      '#default_value' => isset($heart_course_entity) && $heart_course_entity->hasField('description') ? $heart_course_entity->description->value : '',
    ];

    $validators = [
      'file_validate_extensions' => ['pdf docx'],
    ];
    $form['parish_information'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Parish Information'),
      '#prefix' => '<div id="names-parish-information">',
      '#suffix' => '</div>',
    ];

    $form['parish_information']["parish_id"] = [
      '#type' => 'hidden',
      '#default_value' => !empty($parish_paragraphs) ? $parish_paragraphs->id() : '',
    ];

    $form['parish_information']['parish_title'] = [
      '#title' => $this->t('Title'),
      '#type' => 'textfield',
      '#required' => TRUE,
      '#default_value' => !empty($parish_paragraphs) ? $parish_paragraphs->get('field_parish_title')->value : '',
    ];

    $form['parish_information']['parish_attach_file'] = [
      '#type' => 'managed_file',
      '#name' => 'Attach File',
      '#title' => $this->t('Upload file'),
      '#size' => 30,
      '#description' => $this->t('Accepted file types<br>Document (PDF) .pdf<br>Document (DOC) .docx'),
      '#upload_validators' => $validators,
      '#upload_location' => 'public://pdf_resource/files/',
      '#default_value' => $parish_default_file ?? NULL,
    ];

    $form['parish_information']['parish_description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#required' => TRUE,
      '#default_value' => !empty($parish_paragraphs) ? $parish_paragraphs->get('field_parish_description')->value : '',
    ];

    // Enable hierarchical form structure.
    $form['#tree'] = TRUE;
    // Define fieldset for catechist information.
    $form['catechist'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Catechist Information'),
      '#prefix' => '<div id="names-fieldset-wrapper">',
      '#suffix' => '</div>',
    ];

    // Loop through catechist fieldsets.
    for ($i = 1; $i <= $catechist_count; $i++) {
      $catechist_default_file = [];
      // Get the catechist paragraph entity if it exists.
      $catechist_paragraph = $catechist_paragraphs[$i - 1] ?? NULL;

      if ($catechist_paragraph) {
        if ($catechist_paragraph->hasTranslation($current_language)) {
          $catechist_paragraph = $catechist_paragraph->getTranslation($current_language);
        }
        else {
          $catechist_paragraph = $catechist_paragraph->addTranslation($current_language);
        }
      }
      // Create a new fieldset for each catechist.
      $form['catechist'][$i] = [
        '#type' => 'fieldset',
      ];
      // Set default value for attached file.
      if ($catechist_paragraph) {
        // Get default attach file.
        if ($catechist_paragraph->hasField('field_file')) {
          $target_id = $catechist_paragraph->get('field_file')->target_id;
          if (!empty($target_id)) {
            $catechist_default_file = [$target_id];
          }
        }
      }
      // Store the catechist paragraph ID.
      $form['catechist'][$i]["catechist_id"] = [
        '#type' => 'hidden',
        '#default_value' => $catechist_paragraph ? $catechist_paragraph->id() : '',
      ];

      $form['catechist'][$i]["catechist_title"] = [
        '#title' => $this->t('Title'),
        '#type' => 'textfield',
        '#required' => TRUE,
        '#parents' => ['catechist', $i, 'catechist_title'],
        '#default_value' => $catechist_paragraph ? $catechist_paragraph->get('field_title')->value : '',
      ];

      $form['catechist'][$i]['catechist_attach_file'] = [
        '#type' => 'managed_file',
        '#name' => 'Attach File',
        '#title' => $this->t('Upload file'),
        '#size' => 30,
        '#description' => $this->t('Accepted file types<br>Document (PDF) .pdf<br>Document (DOC) .docx'),
        '#upload_validators' => $validators,
        '#upload_location' => 'public://pdf_resource/files/',
        '#default_value' => $catechist_default_file ?? NULL,
        '#parents' => ['catechist', $i, 'catechist_attach_file'],
      ];

      $form['catechist'][$i]['catechist_description'] = [
        '#type' => 'textarea',
        '#title' => $this->t('Description'),
        '#required' => TRUE,
        '#default_value' => $catechist_paragraph ? $catechist_paragraph->get('field_description')->value : '',
        '#parents' => ['catechist', $i, 'catechist_description'],
      ];
      $form['catechist']['actions'] = [
        '#type' => 'actions',
      ];
      // Add button to add more catechist fields if less than 3.
      if ($catechist_count < 3) {
        $form['catechist']['actions']['add_name'] = [
          '#type' => 'submit',
          '#value' => $this->t('+'),
          '#submit' => ['::addOne'],
          '#limit_validation_errors' => [],
          '#ajax' => [
            'callback' => '::addmoreCallback',
            'wrapper' => 'names-fieldset-wrapper',
          ],
        ];
      }

      // Remove button to remove catechist fields if more than one.
      if ($catechist_count > 1) {
        $form['catechist']['actions']['remove_name'] = [
          '#type' => 'submit',
          '#value' => $this->t('-'),
          '#submit' => ['::removeCallback'],
          '#limit_validation_errors' => [],
          '#ajax' => [
            'callback' => '::addmoreCallback',
            'wrapper' => 'names-fieldset-wrapper',
          ],
        ];
      }
    }

    $form['promot_to_front'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Promote to front page'),
      '#default_value' => 0
    ];

    $form['status'] = [
      '#type' => 'radios',
      '#title' => $this->t('Publish?'),
      '#required' => TRUE,
      '#options' => [
        'true' => $this->t('Publish'),
        'false' => $this->t('UnPublish'),
      ],
      '#prefix' => '<div class="form-inline">',
      '#suffix' => '</div>',
      '#default_value' => $status,
    ];

    // Main submit button.
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {}

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();
    $current_language = $this->languageManager->getCurrentLanguage()->getId();

    // Get route parameters.
    $heart_course_id = $this->routematch->getParameter('id');

    // Check if values are not empty.
    if (!empty($values)) {
      if($values['promot_to_front'] == 1){
        $promot_front = true;
      }else{
        $promot_front = false;
      }
      // Set the publish or not status.
      $status = TRUE;
      if ($values['status'] == 'false') {
        $status = FALSE;
      }

      // Get the values from the form.
      $banner_img = $values['banner_upload'] ?? NULL;
      $thumbnail_img = $values['thumbnail_upload'] ?? NULL;
      $description = $values['description'] ?? NULL;
      // If event id is empty create new entity.
      if (!$heart_course_id) {
        // Create new heart_course entity.
        $heart_course_entity = $this->entityTypeManager
          ->getStorage('heart_course')
          ->create([
            'label' => $values['course_name'] ?? '',
            'banner_image' => $banner_img ?? '',
            'thumbnail_image' => $thumbnail_img ?? '',
            'description' => $description ?? '',
            'promot_to_front' => $promot_front,
            'status' => $status ?? 1,
            'langcode' => $current_language,
          ]);
        $heart_course_entity->save();

        // Array for paragraph IDs.
        $catechist_paragraph_ids = [];

        // Create catechist_information paragraphs.
        foreach ($values['catechist'] as $id => $catechist_data) {
          // Check if the array are no action buttons.
          if ($id != 'actions') {
            // Create catechist information paragraph.
            $catechist_paragraph = $this->entityTypeManager->getStorage('paragraph')->create([
              'type' => 'catechist_information',
              'field_title' => $this->t($catechist_data['catechist_title']),
              'field_file' => $catechist_data['catechist_attach_file'],
              'field_description' => $this->t($catechist_data['catechist_description']),
              'langcode' => $current_language,
            ]);

            // Attach paragraph to heart_course entity.
            $catechist_paragraph->setParentEntity($heart_course_entity, 'catechist_information');
            $catechist_paragraph->save();
            // Store the paragraph IDs.
            $catechist_paragraph_ids[] = $catechist_paragraph->id();
          }
        }

        // Create parish_information paragraph.
        $parish_paragraph = $this->entityTypeManager->getStorage('paragraph')->create([
          'type' => 'parish_information',
          'field_parish_title' => $this->t($values['parish_information']['parish_title']),
          'field_parish_file' => $values['parish_information']['parish_attach_file'],
          'field_parish_description' => $this->t($values['parish_information']['parish_description']),
          'langcode' => $current_language,
        ]);

        // Attach the paragraph to the heart_course entity.
        $parish_paragraph->setParentEntity($heart_course_entity, 'parish_information');

        $parish_paragraph->save();
        // Get parish information paragraph ID.
        $parish_paragraph_id = $parish_paragraph->id();

        // Attach paragraphs to the heart_course entity.
        $heart_course_entity->set('catechist_information', $catechist_paragraph_ids);
        $heart_course_entity->set('parish_information', $parish_paragraph_id);
        $heart_course_entity->set('langcode', $current_language);
        $heart_course_entity->save();
        $this->message->addMessage($this->t("Heart Course entity created Successfully."));
      }
      else {
        // Load the existing heart_course entity.
        $heart_course_entity = $this->entityTypeManager
          ->getStorage('heart_course')
          ->load($heart_course_id);

        if ($heart_course_entity->hasTranslation($current_language)) {
          $heart_course_entity = $heart_course_entity->getTranslation($current_language);
        }
        else {
          $heart_course_entity = $heart_course_entity->addTranslation($current_language);
        }
        // Update the existing heart_course fields.
        $heart_course_entity->set('label', $values['course_name']);
        $heart_course_entity->set('banner_image', $values['banner_upload'] ?? '');
        $heart_course_entity->set('thumbnail_image', $values['thumbnail_upload'] ?? '');
        $heart_course_entity->set('description', $values['description']);
        $heart_course_entity->set('promot_to_front', $promot_front);
        $heart_course_entity->set('langcode', $current_language);
        $heart_course_entity->set('status', $status);
        $heart_course_entity->save();

        $catechist_paragraph_ids = [];
        // Loop thorough catechist bundles to update paragraphs.
        foreach ($values['catechist'] as $id => $catechist_data) {
          // Check if the array are no action buttons.
          if ($id != 'actions') {
            // Check if catechist ID is not empty.
            if (!empty($catechist_data['catechist_id'])) {
              // Load and update existing catechist paragraph.
              $catechist_paragraph = $this->entityTypeManager->getStorage('paragraph')->load($catechist_data['catechist_id']);

              if ($catechist_paragraph->hasTranslation($current_language)) {
                $catechist_paragraph = $catechist_paragraph->getTranslation($current_language);
              }
              else {
                $catechist_paragraph = $catechist_paragraph->addTranslation($current_language);
              }

              $catechist_paragraph->set('field_title', $this->t($catechist_data['catechist_title']));
              $catechist_paragraph->set('field_file', $catechist_data['catechist_attach_file']);
              $catechist_paragraph->set('field_description', $this->t($catechist_data['catechist_description']));
              $catechist_paragraph->set('langcode', $current_language);
              $catechist_paragraph->save();
            }
            else {
              // Create new catechist paragraph.
              $catechist_paragraph = $this->entityTypeManager->getStorage('paragraph')->create([
                'type' => 'catechist_information',
                'field_title' => $this->t($catechist_data['catechist_title']),
                'field_file' => $catechist_data['catechist_attach_file'],
                'field_description' => $this->t($catechist_data['catechist_description']),
                'langcode' => $current_language,
              ]);
              $catechist_paragraph->setParentEntity($heart_course_entity, 'catechist_information');
              $catechist_paragraph->save();
            }
            // Store the paragraph IDs.
            $catechist_paragraph_ids[] = $catechist_paragraph->id();
          }
        }
        $initial_catechist_ids = $form_state->get('initial_catechist_ids');
        // If catechist bundle is removed for a id,delete its paragraph.
        if ($initial_catechist_ids) {
          $paragraph_storage = $this->entityTypeManager->getStorage('paragraph');
          foreach ($initial_catechist_ids as $initial_id) {
            if (!in_array($initial_id, $catechist_paragraph_ids)) {
              $paragraph = $paragraph_storage->load($initial_id);
              if ($paragraph) {
                $paragraph->delete();
              }
            }
          }
        }

        // Update or create the parish_information paragraph.
        if (!empty($values['parish_information']['parish_id'])) {
          // Load and update existing parish paragraph.
          $parish_paragraph = $this->entityTypeManager->getStorage('paragraph')->load($values['parish_information']['parish_id']);

          if ($parish_paragraph->hasTranslation($current_language)) {
            $parish_paragraph = $parish_paragraph->getTranslation($current_language);
          }
          else {
            $parish_paragraph = $parish_paragraph->addTranslation($current_language);
          }

          $parish_paragraph->set('field_parish_title', $this->t($values['parish_information']['parish_title']));
          $parish_paragraph->set('field_parish_file', $values['parish_information']['parish_attach_file']);
          $parish_paragraph->set('field_parish_description', $this->t($values['parish_information']['parish_description']));
          $parish_paragraph->set('langcode', $current_language);
          $parish_paragraph->save();
        }
        else {
          // Create new parish paragraph.
          $parish_paragraph = $this->entityTypeManager->getStorage('paragraph')->create([
            'type' => 'parish_information',
            'field_parish_title' => $this->t($values['parish_information']['parish_title']),
            'field_parish_file' => $values['parish_information']['parish_attach_file'],
            'field_parish_description' => $this->t($values['parish_information']['parish_description']),
            'langcode' => $current_language,
          ]);
          $parish_paragraph->setParentEntity($heart_course_entity, 'parish_information');
          $parish_paragraph->save();
        }

        // Attach updated paragraphs to the heart_course entity.
        $heart_course_entity->set('catechist_information', $catechist_paragraph_ids);
        $heart_course_entity->set('parish_information', $parish_paragraph->id());
        $heart_course_entity->set('langcode', $current_language);
        $heart_course_entity->save();
        $this->message->addMessage($this->t("Heart Course entity updated Successfully."));
      }
    }
  }

  /**
   * Callback for both ajax-enabled buttons.
   *
   * Selects and returns the fieldsets.
   */
  public function addmoreCallback(array &$form, FormStateInterface $form_state) {
    // Get the catechist bundle fields.
    return $form['catechist'];
  }

  /**
   * Submit handler for the "add-one-more" button.
   */
  public function addOne(array &$form, FormStateInterface $form_state) {
    // Increase the count of catechist bundle.
    $catechist_count = $form_state->get('catechist_count');
    $add_button = $catechist_count + 1;
    $form_state->set('catechist_count', $add_button);
    // Rebuild the form and update the catechist bundle.
    $form_state->setRebuild();
  }

  /**
   * Submit handler for the "remove one" button.
   */
  public function removeCallback(array &$form, FormStateInterface $form_state) {
    // Decrease the count of catechist bundle.
    $catechist_count = $form_state->get('catechist_count');
    if ($catechist_count > 1) {
      $remove_button = $catechist_count - 1;
      $form_state->set('catechist_count', $remove_button);
    }
    // Rebuild the form and update the catechist bundle.
    $form_state->setRebuild();
  }

}
