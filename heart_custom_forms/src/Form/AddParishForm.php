<?php

declare(strict_types=1);

namespace Drupal\heart_custom_forms\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\heart_custom_forms\HeartCustomService;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Heart Custom Forms form.
 */
final class AddParishForm extends FormBase {

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
   * Custom Helper service.
   *
   * @var \Drupal\heart_custom_forms\HeartCustomService
   */
  protected $helper;
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
   * @param Drupal\heart_custom_forms\HeartCustomService $helper
   *   The custom helper service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    CurrentPathStack $current_path,
    AccountInterface $current_user,
    RouteMatchInterface $route_match,
    MessengerInterface $message,
    HeartCustomService $helper,
    LanguageManagerInterface $language_manager
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentPath = $current_path;
    $this->currentUser = $current_user;
    $this->routematch = $route_match;
    $this->message = $message;
    $this->helper = $helper;
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
      $container->get('heart_custom_forms.heart_custom_service'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'heart_custom_forms_add_parish';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['#prefix'] = '<div id="add-parish-form">';
    $form['#suffix'] = '</div>';
    // Call helper function to get diocese.
    $diocese_options = $this->helper->getDioceseName();
    $form['field_diocese'] = [
      '#type' => 'select',
      '#title' => $this->t('Diocese'),
      '#options' => $diocese_options,
      '#required' => TRUE,
    ];

    $form['parish_name'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('Parish Name'),
    ];

    $form['parish_address'] = [
      '#type' => 'address',
      '#default_value' => [
        'country_code' => 'US',
      ],
      '#field_overrides' => [
        'organization' => 'hidden',
        'givenName' => 'hidden',
        'familyName' => 'hidden',
        'addressLine3' => 'hidden',
      ],
    ];

    $form['actions'] = [
      '#type' => 'actions',
      'submit' => [
        '#type' => 'submit',
        '#value' => $this->t('add parish'),
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Get the current language.
    $current_language = $this->languageManager->getCurrentLanguage()->getId();
    $values = $form_state->getValues();
    if (!empty($values)) {
      $parish_address = [
        'country_code' => $values['parish_address']['country_code'] ?? '',
        'administrative_area' => $values['parish_address']['administrative_area'] ?? '',
        'address_line1' => $values['parish_address']['address_line1'] ?? '',
        'address_line2' => $values['parish_address']['address_line2'] ?? '',
        'postal_code' => $values['parish_address']['postal_code'] ?? '',
        'locality' => $values['parish_address']['locality'] ?? '',
      ];
      // Create heart parish entity.
      $parish_entity = $this->entityTypeManager->getStorage('heart_parish_data')->create([
        'label' => $values['parish_name'],
        'diocese_field' => $values['field_diocese'],
        'parish_address' => $parish_address,
        'langcode' => $current_language,
      ]);
      // Save entity.
      $parish_entity->save();
      $this->message->addMessage('Parish added successfully.');
    }
  }

}
