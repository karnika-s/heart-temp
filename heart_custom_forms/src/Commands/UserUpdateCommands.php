<?php

namespace Drupal\heart_custom_forms\Commands;

use Drush\Commands\DrushCommands;

/**
 *
 */
class UserUpdateCommands extends DrushCommands {

  /**
   * Update all user data.
   *
   * @command user:update-all
   * @aliases user-update-all
   * @description Updates all user data.
   */
  public function updateAllUserData() {
    // Load all user profile IDs.
    $user_profile_storage = \Drupal::entityTypeManager()->getStorage('user_profile_data');
    $user_profile_ids = $user_profile_storage->getQuery()
      ->accessCheck(FALSE)
      ->execute();

    foreach ($user_profile_ids as $user_profile_id) {
      // Load the user profile entity.
      $user_profile_storage = \Drupal::entityTypeManager()->getStorage('user_profile_data')->load($user_profile_id);
      if (!empty($user_profile_storage && $user_profile_storage->user_data->target_id != NULL)) {
        // Load the user entity with user_data id.
        $user_storage = \Drupal::entityTypeManager()->getStorage('user')->load($user_profile_storage->user_data->target_id);
        // Set user profile entity id in user profile reference field.
        if (!empty($user_storage)) {
          $user_storage->field_user_profile_reference = ['target_id' => $user_profile_storage->id()];
          $user_storage->save();
          // Log the update.
          \Drupal::logger('custom_script')->notice('Updated user @uid data.', ['@uid' => $user_storage->id()]);
        }
      }
    }
  }

}
