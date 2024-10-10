<?php

declare(strict_types=1);

namespace Drupal\heart_custom_forms\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a add event form.
 *
 * @Block(
 *   id = "heart_custom_forms_event",
 *   admin_label = @Translation("Add event"),
 *   category = @Translation("Custom"),
 * )
 */
final class EventFormBlock extends BlockBase implements
  ContainerFactoryPluginInterface {
  /**
   * Protected Validation service.
   *
   * @var formBuilder
   */
  protected $formbuilder;

  /**
   * Dependency injection.
   */
  public function __construct(array $configuration,
    $plugin_id,
    $plugin_definition,
    FormBuilderInterface $formbuilder) {

    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->formbuilder = $formbuilder;

  }

  /**
   * Create container.
   */
  public static function create(ContainerInterface $container,
    array $configuration,
    $plugin_id,
    $plugin_definition) {

    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('form_builder')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {

    // Render the form.
    $form = $this->formbuilder->getForm('\Drupal\heart_custom_forms\Form\EventForm');

    $build = [
      'content' => ['add_event_form' => $form],
    ];

    return $build;
  }

}
