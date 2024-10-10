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
 * Returns responses for Heart parish routes.
 */
final class HeartParishController extends ControllerBase {

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
   * Constructs a new HeartParishController object.
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
    $parish_id = $this->request->getCurrentRequest()->request->get('parish_id');
    $admins_list = [];

    if (!empty($parish_id)) {

      // Get parish admin data.
      $getAdmins = $this->userRefData->userRefDataGet(NULL, 'heart_parish_data', 'heart_parish_data', $parish_id);

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
    $parish_id = $this->request->getCurrentRequest()->request->get('parish_id');
    $uid = $this->request->getCurrentRequest()->request->get('uid');

    if (!empty($parish_id)) {

      // Remove parish admin data.
      $associatedData = $this->getEntityTypesCreatedByCurrentUser($uid);

      if (!empty($associatedData)) {

        // Get parish admin data.
        $getAdmins = $this->userRefData->userRefDataGet(NULL, 'heart_parish_data', 'heart_parish_data', $parish_id);

        if (!empty($getAdmins)) {
          foreach ($getAdmins as $key => $admin) {
            $curr_uid = $admin['uid'];
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
        // Remove parish admin refrence for given parish.
        $custom_entity_user_profile = $this->entityTypeManager->getStorage('user_profile_data');
        $custom_entity_user_profile = $custom_entity_user_profile->loadByProperties([
          'user_data' => $uid,
        ]);

        $custom_entity_user_profile = reset($custom_entity_user_profile);
        $user_profile_id = $custom_entity_user_profile->id();

        if (!empty($custom_entity_user_profile)) {
          $removed_parish = [];
          $current_roles = $custom_entity_user_profile->get('sub_role')->getValue();
          foreach ($current_roles as $key => $value) {
            if ($value['target_id'] != '9') {
              $removed_parish = $value;
            }
          }

          $custom_entity_user_profile->set('sub_role', $removed_parish);
          $custom_entity_user_profile->save();
        }

        $custom_entity_parish = $this->entityTypeManager->getStorage('heart_parish_data');
        $custom_entity_parish = $custom_entity_parish->loadByProperties([
          'id' => $parish_id,
        ]);

        $custom_entity_parish = reset($custom_entity_parish);

        if (!empty($custom_entity_parish)) {
          $current_admins = $custom_entity_parish->get('parish_admins')->getValue();
          $updated_admins = array_filter($current_admins, function ($item) use ($user_profile_id) {
            return $item['target_id'] != $user_profile_id;
          });

          $custom_entity_parish->set('parish_admins', $updated_admins);
          $custom_entity_parish->save();
        }

        $this->userRefData->userRefDataDelete($uid, 'heart_parish_data', $parish_id, 'heart_parish_data');

        return new JsonResponse("Deleted");
      }

    }
    else {
      return new JsonResponse("Failure");
    }
  }

  /**
   * Assign parish content to another admin.
   */
  public function assignParishContent() {

    // Get the input values from the request.
    $parish_id = $this->request->getCurrentRequest()->request->get('parish_id');
    $touid = $this->request->getCurrentRequest()->request->get('to_uid');
    $fromuid = $this->request->getCurrentRequest()->request->get('from_uid');

    if (!empty($parish_id)) {

      // Get parish admin data.
      $entitiesHasData = $this->getEntityTypesCreatedByCurrentUser($fromuid);
      if (!empty($entitiesHasData)) {
        // Set Parish ID for the user profile entity.
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
