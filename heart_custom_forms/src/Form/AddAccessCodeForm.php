<?php

declare(strict_types=1);

namespace Drupal\heart_custom_forms\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\heart_custom_forms\HeartCustomService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Heart Custom Forms form.
 */
final class AddAccessCodeForm extends FormBase {
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
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * The UserRefData Service.
   *
   * @var Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The date and time.
   *
   * @var Drupal\Core\Datetime\DrupalDateTime
   */
  protected $dateTime;

  /**
   * Custom Helper service.
   *
   * @var \Drupal\heart_custom_forms\HeartCustomService
   */
  protected $helper;

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
   * @param Drupal\Core\Database\Connection $database
   *   The Database connection Service.
   * @param Drupal\Core\Datetime\DrupalDateTime $date_time
   *   The date and time.
   * @param Drupal\heart_custom_forms\HeartCustomService $helper
   *   The custom helper service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    CurrentPathStack $current_path,
    AccountInterface $current_user,
    RouteMatchInterface $route_match,
    MessengerInterface $messenger,
    Connection $database,
    TimeInterface $date_time,
    HeartCustomService $helper
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentPath = $current_path;
    $this->currentUser = $current_user;
    $this->routeMatch = $route_match;
    $this->database = $database;
    $this->messenger = $messenger;
    $this->dateTime = $date_time;
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
          $container->get('database'),
          $container->get('datetime.time'),
          $container->get('heart_custom_forms.heart_custom_service')
      );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'heart_custom_forms_add_access_code';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $course_option = $this->helper->getCourseProduct();
    $form['#prefix'] = '<div class="wrapper-600">';
    $form['#suffix'] = '</div>';
    $form['select_course'] = [
      '#type' => 'select',
      '#chosen' => TRUE,
      '#title' => $this->t('Select Course'),
      '#options' => $course_option,
      '#required' => TRUE,
    ];
    $form['access_code_upload'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Select csv'),
      '#upload_location' => 'public://uploads/excel',
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
      ],
      '#required' => TRUE,
      // '#default_value' => $this->config('heart_diocese.settings')->get('class_learners_list_upload'),
    ];
    // $form['activation_code'] = [
    //   '#type' => 'number',
    //   '#title' => $this->t('Access Code(Max 50)'),
    //   '#required' => TRUE,
    // ];
    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('submit'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {

    // Get the activation code.
    // $activation_code = $form_state->getValue('activation_code');.
    // If ($activation_code > 50) {
    //   $form_state->setErrorByName(
    //     'activation_code',
    //     $this->t('You can not create more that 50.'),
    //   );
    // }.
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {

    // Get the activation code.
    $values = $form_state->getValues();
    $access_doc = $form_state->getValue('access_code_upload');
    // Validate uploaded file.
    $file = $form_state->getValue('access_code_upload');
    if (!empty($file)) {

      // Load the file entity.
      /** @var \Drupal\file\Entity\FileInterface $file_entity */
      $file_entity = $this->entityTypeManager->getStorage('file')->load($file[0]);
      $file_path = $file_entity->getFileUri();

      $row = 0;
      if (($handle = fopen($file_path, "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
          if ($row != 0) {
            $access_codes[] = $data[0];
          }
          $row++;
        }
        fclose($handle);
      }
      if (!empty($access_codes)) {
        // Loop through emails and set diocese id and send invite.
        foreach ($access_codes as $access_code) {
          $access_code_entity = $this->entityTypeManager->getStorage('heart_access_code')->create([
            'label' => $access_code,
            'course_field' => $values['select_course'],
            'status' => TRUE,
          ]);
          $access_code_entity->save();
        }
        $this->messenger->addMessage($this->t('Successfully created access codes'));
      }
      else {
        $this->messenger->addError($this->t('There is some problem to create access code'));
      }
    }
    // $qty = intval($values['activation_code']);
    // for ($i = 0; $i < $qty; $i++) {
    //   // Generate random string form access code.
    //   $access_code_key = $this->helper->heartAccesscodeGeneratePhp();
    //   // Create access code entity.
    // $access_code_entity = $this->entityTypeManager->getStorage('heart_access_code')->create([
    //   'label' => $access_code_key,
    //   'course_field' => $values['select_course'],
    //   'status' => TRUE,
    // ]);
    // $access_code_entity->save();
    // }
  }

}
