<?php

declare(strict_types=1);

namespace Drupal\heart_custom_forms\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Path\CurrentPathStack;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Heart Custom Forms form.
 */
final class EmailTemplateForm extends FormBase {

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
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, CurrentPathStack $current_path, AccountInterface $current_user, RouteMatchInterface $route_match, MessengerInterface $message) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentPath = $current_path;
    $this->currentUser = $current_user;
    $this->routematch = $route_match;
    $this->message = $message;
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
    $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'heart_custom_form_email_template_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $template_id = $this->routematch->getParameter('template_id');
    $status = 'true';
    // Loading entity events with id.
    if ($template_id) {
      $email_template_entity = $this->entityTypeManager->getStorage('heart_email_template')->load($template_id);
      if ($email_template_entity) {
        $form['template_id'] = [
          '#type' => 'hidden',
          '#default_value' => $template_id ?? '',
        ];

        if ($email_template_entity->hasField('trigger_action') && !empty($email_template_entity->get('trigger_action')->getValue())) {
          $defaultaction = [];
          foreach ($email_template_entity->get('trigger_action')->getValue() as $action) {
            $defaultaction[$action['target_id']] = $action['target_id'];
          }
        }
      }
      else {
        // If there is no id.
        $form['form_markup'] = [
          '#type' => 'markup',
          '#markup' => '<h3 class="text-dark">Template Not Found</h3>',
        ];
        return $form;
      }
    }
    // Check resource is publish or not.
    if (isset($email_template_entity) && $email_template_entity->status->value == FALSE) {
      $status = 'false';
    }
    $form['template_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Template Name'),
      '#required' => TRUE,
      '#default_value' => isset($email_template_entity) && $email_template_entity->hasField('template_name') ? $email_template_entity->template_name->value : '',
    ];
    $form['email_subject'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subject Line'),
      '#required' => TRUE,
      '#default_value' => isset($email_template_entity) && $email_template_entity->hasField('email_subject') ? $email_template_entity->email_subject->value : '',
    ];

    $form['email_message'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Email Message'),
      '#format' => isset($email_template_entity) && $email_template_entity->hasField('email_message') ? $email_template_entity->email_message->format : 'full_html',
      '#required' => TRUE,
      '#default_value' => isset($email_template_entity) && $email_template_entity->hasField('email_message') ? $email_template_entity->email_message->value : '',
    ];

    $trigger_action_taxonomy = 'trigger_action';
    $terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree($trigger_action_taxonomy);

    // Populate options array for checkbox field.
    $triggeroptions = [];
    foreach ($terms as $term) {
      // Check if the term is a top-level term.
      if ($term->depth == 0) {
        $triggeroptions[$term->tid] = $term->name;
      }
    }

    $form['trigger_action'] = [
      '#type' => 'select',
      '#title' => $this->t('Trigger Action'),
      '#required' => TRUE,
      '#options' => $triggeroptions,
      '#default_value' => !empty($defaultaction) ? $defaultaction : '',
    ];
    $form['from_name'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('From Name'),
      '#default_value' => isset($email_template_entity) && $email_template_entity->hasField('from_name') ? $email_template_entity->from_name->value : '',
    ];

    $form['from_email'] = [
      '#type' => 'textfield',
      '#required' => TRUE,
      '#title' => $this->t('From Email'),
      '#default_value' => isset($email_template_entity) && $email_template_entity->hasField('from_email') ? $email_template_entity->from_email->value : '',
    ];

    $form['active'] = [
      '#type' => 'select',
      '#title' => $this->t('Active?'),
      '#required' => TRUE,
      '#options' => [
        'true' => $this->t('true'),
        'false' => $this->t('false'),
      ],
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
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();
    // Set the active or not active.
    $status = TRUE;
    if ($values['active'] == 'false') {
      $status = FALSE;
    }
	// Update email template entity.
    if ($values['template_id']) {
      $email_template_entity = $this->entityTypeManager->getStorage('heart_email_template')->load($values['template_id']);
      $email_template_entity->set('template_name', $values['template_name']);
      $email_template_entity->set('email_subject', $values['email_subject']);
      $email_template_entity->set('email_message', $values['email_message']);
      $email_template_entity->set('from_name', $values['from_name']);
      $email_template_entity->set('from_email', $values['from_email']);
      $email_template_entity->set('status', $status);
      $email_template_entity->set('trigger_action', $values['trigger_action']);
      $email_template_entity->save();
    }
    else {
	 // Create email template entity.
      $email_template_entity = $this->entityTypeManager->getStorage('heart_email_template')->create([
        'template_name' => $values['template_name'],
        'email_subject' => $values['email_subject'],
        'email_message' => $values['email_message'],
        'from_name' => $values['from_name'],
        'from_email' => $values['from_email'],
        'status' => $status,
        'trigger_action' => $values['trigger_action'],
      ]);
      $email_template_entity->save();
    }
  }

}
