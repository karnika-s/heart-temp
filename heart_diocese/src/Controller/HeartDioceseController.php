<?php

declare(strict_types=1);

namespace Drupal\heart_diocese\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\heart_user_data\UserRefData;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Returns responses for Heart diocese routes.
 */
final class HeartDioceseController extends ControllerBase {

  /**
   * The route match.
   *
   * @var Symfony\Component\HttpFoundation\RequestStack
   */
  protected $request;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The user reference data creation.
   *
   * @var \Drupal\heart_user_data\UserRefData
   */
  protected $userRefData;

  /**
   * Constructs a new HeartDioceseController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\heart_user_data\UserRefData $userRefData
   *   The user reference data creation.
   * @param Symfony\Component\HttpFoundation\RequestStack $request
   *   The route match.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, UserRefData $userRefData, RequestStack $request) {
    $this->entityTypeManager = $entityTypeManager;
    $this->userRefData = $userRefData;
    $this->request = $request;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('heart_user_data.user_ref_data'),
      $container->get('request_stack'),
    );
  }

  /**
   * Fetch admin info.
   */
  public function fetchAdminInfo() {

    // Get the input values from the request.
    $diocese_id = $this->request->getCurrentRequest()->request->get('diocese_id');
    $admins_list = [];

    if (!empty($diocese_id)) {

      // Get diocese admin data.
      $getAdmins = $this->userRefData->userRefDataGet(NULL, 'heart_diocese_data', 'heart_diocese_data', $diocese_id);
      if (!empty($getAdmins)) {
        foreach ($getAdmins as $key => $admin) {
          $uid = $admin['user_id'];
          $name = '';
          if (!empty($uid)) {

            $custom_entity = $this->entityTypeManager->getStorage('user_profile_data');
            $custom_entity = $custom_entity->loadByProperties(['user_data' => $uid]);
            if (!empty($custom_entity)) {
              $custom_entity = reset($custom_entity);
              $first_name = $custom_entity->first_name->getString();
              $last_name = $custom_entity->last_name->getString();

              $name = ucfirst($first_name) . ' ' . ucfirst($last_name);

              if (!empty($name)) {
                $user = $this->entityTypeManager->getStorage('user')->load($uid);
                $name = $user->name->getString();
              }
            }
            else {
              // Get the user.
              $user = $this->entityTypeManager->getStorage('user')->load($uid);
              $name = $user->name->getString();
            }
          }
          $admins_list[$key] = [
            'uid' => $uid,
            'name' => $name,
          ];
        }
        return new JsonResponse($admins_list);
      }
      else {
        return new JsonResponse("NA");
      }
    }
  }

  /**
   * Remove admin info.
   */
  public function removeAdminInfo() {

    // Get the input values from the request.
    $diocese_id = $this->request->getCurrentRequest()->request->get('diocese_id');
    $uid = $this->request->getCurrentRequest()->request->get('uid');

    if (!empty($diocese_id)) {

      // Remove diocese admin data.
      $associatedData = $this->getEntityTypesCreatedByCurrentUser($uid);

      if (!empty($associatedData)) {

        // Get diocese admin data.
        $getAdmins = $this->userRefData->userRefDataGet(NULL, 'heart_diocese_data', 'heart_diocese_data', $diocese_id);
        if (!empty($getAdmins)) {
          foreach ($getAdmins as $key => $admin) {
            $curr_uid = $admin['user_id'];
            $name = '';
            if (!empty($curr_uid) && $curr_uid != $uid) {

              $custom_entity = $this->entityTypeManager->getStorage('user_profile_data');
              $custom_entity = $custom_entity->loadByProperties(['user_data' => $curr_uid]);
              if (!empty($custom_entity)) {
                $custom_entity = reset($custom_entity);
                $first_name = $custom_entity->first_name->getString();
                $last_name = $custom_entity->last_name->getString();

                $name = ucfirst($first_name) . ' ' . ucfirst($last_name);
              }
              else {
                // Get the user.
                $user = $this->entityTypeManager->getStorage('user')->load($curr_uid);
                $name = $user->name->getString();
              }
            }
            $admins_list[$key] = [
              'uid' => $curr_uid,
              'name' => $name,
            ];
          }
          return new JsonResponse($admins_list);
        }
        else {
          return new JsonResponse("NA");
        }
      }
      else {
        // Remove diocese admin refrence for given diocese.
        $custom_entity_user_profile = $this->entityTypeManager->getStorage('user_profile_data');
        $custom_entity_user_profile = $custom_entity_user_profile->loadByProperties([
          'user_data' => $uid,
        ]);

        $custom_entity_user_profile = reset($custom_entity_user_profile);
        $user_profile_id = $custom_entity_user_profile->id();

        if (!empty($custom_entity_user_profile)) {
          $custom_entity_user_profile->set('user_diocese_field', []);
          $custom_entity_user_profile->save();
        }

        $custom_entity_diocese = $this->entityTypeManager->getStorage('heart_diocese_data');
        $custom_entity_diocese = $custom_entity_diocese->loadByProperties([
          'id' => $diocese_id,
        ]);

        $custom_entity_diocese = reset($custom_entity_diocese);

        if (!empty($custom_entity_diocese)) {
          $current_admins = $custom_entity_diocese->get('diocese_admins')->getValue();
          $updated_admins = array_filter($current_admins, function ($item) use ($user_profile_id) {
            return $item['target_id'] != $user_profile_id;
          });

          $custom_entity_diocese->set('diocese_admins', $updated_admins);
          $custom_entity_diocese->save();
        }

        $this->userRefData->userRefDataDelete($uid, 'heart_diocese_data', $diocese_id, 'heart_diocese_data');

        return new JsonResponse("Deleted");
      }

    }
    else {
      return new JsonResponse("Failure");
    }
  }

  /**
   * Assign diocese content to another admin.
   */
  public function assignDioceseContent() {

    // Get the input values from the request.
    $diocese_id = $this->request->getCurrentRequest()->request->get('diocese_id');
    $touid = $this->request->getCurrentRequest()->request->get('to_uid');
    $fromuid = $this->request->getCurrentRequest()->request->get('from_uid');

    if (!empty($diocese_id)) {

      // Get diocese admin data.
      $entitiesHasData = $this->getEntityTypesCreatedByCurrentUser($fromuid);
      if (!empty($entitiesHasData)) {
        // Set Diocesan ID for the user profile entity.
        foreach ($entitiesHasData as $entity) {
          $storage = $this->entityTypeManager->getStorage($entity);
          $keys = $storage->getEntityType()->getKeys();
          if (isset($keys['uid'])) {
            $loaded_entities = $storage->loadByProperties([
              'uid' => $fromuid,
            ]);
            foreach ($loaded_entities as $loaded_entity) {
              $loaded_entity->set('uid', $touid);
              $loaded_entity->save();
            }
          }
          if (isset($keys['content_translation_uid'])) {
            $loaded_entities = $storage->loadByProperties([
              'content_translation_uid' => $fromuid,
            ]);
            foreach ($loaded_entities as $loaded_entity) {
              $loaded_entity->set('content_translation_uid', $touid);
              $loaded_entity->save();
            }
          }
        }
        return new JsonResponse("Author changed successfully.");
      }
      else {
        return new JsonResponse("Failed");
      }
    }
  }

  /**
   * Get all entity types where user has created data.
   *
   * @return array
   *   An array of entity types where user has created data.
   */
  public function getEntityTypesCreatedByCurrentUser($user_id) {
    $entity_types = [];
    foreach ($this->entityTypeManager->getDefinitions() as $entity_type_id => $entity_type) {
      if ($entity_type->entityClassImplements('\Drupal\Core\Entity\ContentEntityInterface')) {
        // Content entity type.
        $storage = $this->entityTypeManager->getStorage($entity_type_id);
        $keys = $storage->getEntityType()->getKeys();
        if (isset($keys['uid']) or isset($keys['owner'])) {
          $query = $storage->getQuery();
          $count = $query->condition('uid', $user_id)->accessCheck(FALSE)->count()->execute();
          if ($count > 0) {
            $entity_types[] = $entity_type_id;
          }
        }
      }
    }
    return $entity_types;
  }

}
