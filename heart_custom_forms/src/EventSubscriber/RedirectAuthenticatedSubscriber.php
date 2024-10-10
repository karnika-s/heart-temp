<?php

namespace Drupal\heart_custom_forms\EventSubscriber;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Drupal\Core\Url;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Event subscriber subscribing to KernelEvents::REQUEST.
 *
 * @package Drupal\heart_custom_forms\EventSubscriber
 */
class RedirectAuthenticatedSubscriber implements EventSubscriberInterface {

  /**
   * The Account inteface object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Constructs a new RedirectAuthenticatedSubscriber instance.
   *
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The Account interface service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(AccountInterface $current_user, EntityTypeManagerInterface $entity_type_manager) {
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
          $container->get('current_user'),
          $container->get('entity_type.manager')
      );
  }

  /**
   * {@inheritdoc}
   *
   * @return array
   *   The event names to listen for, and the methods that should be executed.
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['checkAuthenticatedUser', 30];
    return $events;
  }

  /**
   * React to event that occurs when a new request is received.
   *
   * @param \Symfony\Component\HttpKernel\Event\RequestEvent $event
   *   New request event.
   */
  public function checkAuthenticatedUser(RequestEvent $event) {
    $request = $event->getRequest();
    $roles = $this->currentUser->getRoles();
    // Check if the user is not anonymous and requested path matches the pattern.
    if ($this->currentUser->isAuthenticated() && $request->getPathInfo() == '/') {
      // Check current user role and assign dashboard menu url as per user role.
      $url = '';
      // Check if the user has the 'learner' role.
      if (in_array('learner', $roles) && !in_array('facilitator', $roles) && !in_array('parish_admin', $roles)
          && !in_array('diocesan_admin', $roles) && !in_array('content_editor', $roles) && !in_array('sales_staff', $roles)
          && !in_array('consultant', $roles)) {
        // Redirection url for learner user.
        $url = '/learner-dashboard';
      }
      // Check if the user has the 'facilitator' role.
      if (in_array('facilitator', $roles) && !in_array('parish_admin', $roles)
          && !in_array('diocesan_admin', $roles) && !in_array('content_editor', $roles) && !in_array('sales_staff', $roles)
          && !in_array('consultant', $roles)) {
        // Redirection url for facilitator user.
        $url = '/facilitator-dashboard';
      }
      // Check if the user has the 'parish_admin' role.
      if (in_array('parish_admin', $roles)
          && !in_array('diocesan_admin', $roles) && !in_array('content_editor', $roles) && !in_array('sales_staff', $roles)
          && !in_array('consultant', $roles)) {
        // Redirection url for parish_admin user.
        $url = '/parish-leader-dashboard';
      }
      // Check if the user has the 'diocesan_admin' role.
      if (in_array('diocesan_admin', $roles) && !in_array('content_editor', $roles) && !in_array('sales_staff', $roles)
          && !in_array('consultant', $roles)) {
        // Redirection url for diocesan_admin user.
        $url = '/diocesan-dashboard';
      }
      // Check if the user has the 'content_editor' role.
      if (in_array('content_editor', $roles) && !in_array('sales_staff', $roles) && !in_array('consultant', $roles)) {
        // Redirection url for content_editor user.
        $url = '/content-editor-dashboard';
      }
      // Check if the user has the 'sales_staff' role.
      if (in_array('sales_staff', $roles) && !in_array('consultant', $roles)) {
        // Redirection url for sales_staff user.
        $url = '/sales-staff-dashboard';
      }
      // Check if the user has the 'consultant' role.
      if (in_array('consultant', $roles)) {
        // Redirection url for consultant user.
        $url = '/consultant-dashboard';
      }
      
      if ($url) {
        // Redirect the user to the appropriate dashboard page.
        $redirect_url = Url::fromUserInput($url);
        $response = new RedirectResponse($redirect_url->toString(), 302);

        // Disabling cache.
        $response->setMaxAge(0);
        $response->setSharedMaxAge(0);
        $response->headers->addCacheControlDirective('no-cache', TRUE);

        $event->setResponse($response);
      }
    }

  }

}
