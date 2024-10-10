<?php

declare(strict_types=1);

namespace Drupal\heart_custom_forms\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Returns responses for Heart zoom routes.
 */
final class PublishProductsController extends ControllerBase {
  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

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
   * The mail manager.
   *
   * @var Drupal\Core\Url
   */
  protected $url;

  /**
   * The page cache kill switch service.
   *
   * @var Drupal\Core\PageCache\ResponsePolicy\KillSwitch
   */
  private $pageCacheKillSwitch;

  /**
   * Constructs an UserCustomFormsHelper object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   * @param Drupal\Core\UrlGeneratorInterface $url
   *   The url helper.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The current user.
   * @param Drupal\Core\PageCache\ResponsePolicy\KillSwitch $page_cache_kill_switch
   *   The Page cache service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RouteMatchInterface $route_match, UrlGeneratorInterface $url, AccountInterface $current_user, KillSwitch $page_cache_kill_switch) {
    $this->entityTypeManager = $entity_type_manager;
    $this->routematch = $route_match;
    $this->url = $url;
    $this->currentUser = $current_user;
    $this->pageCacheKillSwitch = $page_cache_kill_switch;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_route_match'),
      $container->get('url_generator'),
      $container->get('current_user'),
      $container->get('page_cache_kill_switch')
    );
  }

  /**
   * Popup data custom.
   */
  public function publish() {
    // Clear cache before loading view.
    $this->pageCacheKillSwitch->trigger();
    // Get the current user object.
    $currentUser = $this->currentUser;
    $uid = $currentUser->id();

    // Get route parameter.
    $prod_id = $this->routematch->getParameter('id');
    // Load the event entity.
    $event_product = $this->entityTypeManager->getStorage('commerce_product')->load($prod_id);
    if ($event_product) {
      $product_entity_bundle = $event_product->bundle();
      $product_id = $event_product->id();
      if ($product_entity_bundle == 'video_resource') {
        $event_entity = $this->entityTypeManager->getStorage('heart_video_resource')->load($event_product->field_referenced_video->target_id);
      }
      elseif ($product_entity_bundle == 'events') {
        $event_entity = $this->entityTypeManager->getStorage('event')->load($event_product->field_event_reference->target_id);
      }
      elseif ($product_entity_bundle == 'course') {
        $event_entity = $this->entityTypeManager->getStorage('heart_course')->load($event_product->field_heart_course->target_id);
      }
      elseif ($product_entity_bundle == 'resource_library') {
        $event_entity = $this->entityTypeManager->getStorage('pdf_resource')->load($event_product->field_pdf_resource->target_id);
      }
      $event_entity->set('status', TRUE);
      $event_entity->save();
      $event_product->set('status', TRUE);
      $event_product->save();
    }
    $url = Url::fromUserInput('/manage-content');
    $response = new RedirectResponse($url->toString());
    return $response;
  }

}
