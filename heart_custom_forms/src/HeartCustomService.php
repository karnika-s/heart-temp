<?php

declare(strict_types=1);

namespace Drupal\heart_custom_forms;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an interface defining a user ref data entity type.
 */
final class HeartCustomService {
  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;
  /**
   * Database connection.
   *
   * @var Drupal\Core\Database\Connection
   */
  protected $database;
  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs the Helper.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\Connection $database
   *   Database connection.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, Connection $database, LanguageManagerInterface $language_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->database = $database;
    $this->languageManager = $language_manager;
  }

  /**
   * Dependency injection.
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('database'),
      $container->get('language_manager')
    );
  }

  /**
   * Get Diocese name.
   */
  public function getDioceseName(): array {
    // Get the current language.
    $current_language = $this->languageManager->getCurrentLanguage()->getId();
    // Query to get the Diocese from heart_diocese_data_field_data table.
    $query = $this->database->select('heart_diocese_data_field_data', 'n')
      ->condition('n.langcode', $current_language)
      ->fields('n', ['id', 'label']);
    $results = $query->execute()->fetchAll();
    $diocese = ['' => t('- Select Diocese -')];
    foreach ($results as $result) {
      $diocese[$result->id] = $result->label;
    }
    // Return the diocese with id and name.
    return $diocese;
  }

  /**
   * Get Parish names.
   */
  public function getParishByDiocese($dioceseId): array {

    // Get the current language.
    $current_language = $this->languageManager->getCurrentLanguage()->getId();

    $parish = ['' => t('- Select Parish -')];
    if ($dioceseId != NULL) {
      // Query to get the Parish with Diocese from
      // heart_parish_data_field_data table.
      $query = $this->database->select('heart_parish_data_field_data', 'n')
        ->condition('n.langcode', $current_language)
        ->fields('n', ['id', 'label'])
        ->condition('n.diocese_field', $dioceseId, '=');
      $results = $query->execute()->fetchAll();
      foreach ($results as $result) {
        $parish[$result->id] = $result->label;
      }
    }
    // Return the Parish with id and name.
    return $parish;
  }

  /**
   * Get Parish names.
   */
  public function getCourseProduct(): array {
    // Get the current language.
    $current_language = $this->languageManager->getCurrentLanguage()->getId();
    $course = ['' => t('- Select Class -')];
    // Query to get the Course Product from course_product_field_data table.
    $query = $this->database->select('course_product_field_data', 'n')
      ->fields('n', ['id', 'product_title', 'heart_course_reference'])
      ->condition('n.langcode', $current_language);
    $results = $query->execute()->fetchAll();

    foreach ($results as $result) {
      if ($result->heart_course_reference != '') {
        // Query to get the Course Name from course_product_field_data table.
        $coursequery = $this->database->select('heart_course_field_data', 'cn')
          ->fields('cn', ['id', 'label'])
          ->condition('cn.langcode', $current_language)
          ->condition('cn.id', $result->heart_course_reference, '=');
        $courseresults = $coursequery->execute()->fetchAll();
        $courseresults = reset($courseresults);
        if (!empty($courseresults)) {
          $course[$result->id] = $courseresults->label . ' : ' . $result->product_title;
        }
      }
      else {
        $course[$result->id] = $result->product_title;
      }
    }

    // Return the Course Product with id and name.
    return $course;
  }

  /**
   * Get Course License.
   */
  public function getCourseProductLicenseQuantity($courseId, $dioceseId, $parishId): array {
    // Get the current language.
    $current_language = $this->languageManager->getCurrentLanguage()->getId();
    // Query to get the Licence quantiy of course
    // from heart_license_field_data table.
    $results = [];
    $query = $this->database->select('heart_license_field_data', 'n');
    $query->fields('n', ['license_quantity_available']);
    $query->condition('n.course_field', $courseId, '=');
    $query->condition('n.diocese_field', $dioceseId, '=');
    $query->condition('n.langcode', $current_language);
    if ($parishId != '') {
      $query->condition('n.parish_field', $parishId, '=');
    }
    $results = $query->execute()->fetchAll();
    // Return the Course License Quantity.
    return $results;
  }

  /**
   * Helper function to get view.
   *
   * @var string $view_id
   *   The view id to fetch.
   *
   * @var array
   *   The arguments to supply to view.
   */
  public function getViewBlock($view_id, $args, $display_id) {
    $content = '';
    $view = Views::getView($view_id);
    if (is_object($view)) {
      $view->setArguments($args);
      $view->setDisplay($display_id);
      $view->preExecute();
      $view->execute();
      $content = $view->buildRenderable($display_id, $args);
    }

    return $content;
  }

  /**
   * Get Diocese Admin Diocese.
   */
  public function getDioceseAdminDiocese($currentuserId): array {
    // Get the current language.
    $current_language = $this->languageManager->getCurrentLanguage()->getId();

    // Query to get the diocese admin and parish admin Diocese and Parish.
    $query = $this->database->select('heart_diocese_data_field_data', 'n');
    $query->join('heart_diocese_data__diocese_admins', 'hdda', 'n.id = hdda.entity_id');
    $query->condition('hdda.diocese_admins_target_id', $currentuserId, 'IN');
    $query->condition('n.langcode', $current_language);
    $query->condition('hdda.langcode', $current_language);
    $query->fields('n', ['id', 'label']);
    // ->condition('hdda.diocese_admins_target_id', $currentuserId, '=');
    $results = $query->execute()->fetchAll();
    $diocese = ['' => '- Select Diocese -'];
    foreach ($results as $result) {
      $diocese[$result->id] = $result->label;
    }
    // Return the diocese with id and name.
    return $diocese;
  }

  /**
   * Get Diocese Admin Diocese.
   */
  public function getParishAdminDiocese($currentuserId): array {

    // Get the current language.
    $current_language = $this->languageManager->getCurrentLanguage()->getId();
    // Query to get the diocese admin and parish admin Diocese and Parish.
    $query = $this->database->select('heart_parish_data_field_data', 'n');
    $query->join('heart_diocese_data_field_data', 'hddfd', 'n.diocese_field = hddfd.id');
    $query->join('heart_parish_data__parish_admins', 'hdpa', 'n.id = hdpa.entity_id');
    $query->condition('hdpa.parish_admins_target_id', $currentuserId, 'IN');
    $query->condition('n.langcode', $current_language);
    $query->condition('hddfd.langcode', $current_language);
    $query->condition('hdpa.langcode', $current_language);
    $query->fields('hddfd', ['id', 'label']);
    $results = $query->execute()->fetchAll();
    $diocese = ['' => '- Select Diocese -'];
    foreach ($results as $result) {
      $diocese[$result->id] = $result->label;
    }
    // Return the diocese with id and name.
    return $diocese;
  }

  /**
   * Get Diocese Admin Diocese.
   */
  public function getParishAdminParish($currentuserId, $diocese): array {
    // Get the current language.
    $current_language = $this->languageManager->getCurrentLanguage()->getId();
    // Query to get the parish admin Parish.
    $query = $this->database->select('heart_parish_data_field_data', 'n');
    $query->join('heart_parish_data__parish_admins', 'hdpa', 'n.id = hdpa.entity_id');
    $query->condition('n.diocese_field', $diocese);
    $query->condition('hdpa.parish_admins_target_id', $currentuserId, 'IN');
    $query->condition('n.langcode', $current_language);
    $query->condition('hdpa.langcode', $current_language);
    $query->fields('n', ['id', 'label']);
    // ->condition('hdda.diocese_admins_target_id', $currentuserId, '=');
    $results = $query->execute()->fetchAll();
    $parish = ['' => '- Select Diocese -'];
    foreach ($results as $result) {
      $parish[$result->id] = $result->label;
    }
    // Return the diocese with id and name.
    return $parish;
  }

  /**
   * Get diocese Course products.
   */
  public function getCourseProductByDiocese($diocese): array {

    // Get the current language.
    $current_language = $this->languageManager->getCurrentLanguage()->getId();
    $course = ['' => '- Select Class -'];
    // Query to get the Course Product from course_product_field_data table.
    $query = $this->database->select('course_product_field_data', 'n');
    $query->join('heart_license_field_data', 'hlfd', 'n.id=hlfd.course_field');
    $query->condition('hlfd.diocese_field', $diocese);
    $query->condition('hlfd.purchased_for', 'diocese', '=');
    $query->condition('n.langcode', $current_language);
    $query->condition('hlfd.langcode', $current_language);
    $query->fields('n', ['id', 'product_title', 'heart_course_reference']);
    $results = $query->execute()->fetchAll();

    foreach ($results as $result) {
      if ($result->heart_course_reference != '') {
        // Query to get the Course Name from course_product_field_data table.
        $coursequery = $this->database->select('heart_course_field_data', 'cn')
          ->fields('cn', ['id', 'label'])
          ->condition('cn.id', $result->heart_course_reference, '=');
        $courseresults = $coursequery->execute()->fetchAll();
        $courseresults = reset($courseresults);
        if (!empty($courseresults)) {
          $course[$result->id] = $courseresults->label . ' : ' . $result->product_title;
        }
      }
      else {
        $course[$result->id] = $result->product_title;
      }
    }

    // Return the Course Product with id and name.
    return $course;
  }

  /**
   * Get parish Course products.
   */
  public function getCourseProductByParish($diocese, $parishId): array {
    // Get the current language.
    $current_language = $this->languageManager->getCurrentLanguage()->getId();
    $course = ['' => '- Select Class -'];
    // Query to get the Course Product from course_product_field_data table.
    $query = $this->database->select('course_product_field_data', 'n');
    $query->join('heart_license_field_data', 'hlfd', 'n.id=hlfd.course_field');
    $query->condition('hlfd.diocese_field', $diocese);
    $query->condition('hlfd.parish_field', $parishId);
    $query->condition('hlfd.purchased_for', 'parish', '=');
    $query->condition('hlfd.langcode', $current_language);
    $query->condition('n.langcode', $current_language);

    $query->fields('n', ['id', 'product_title', 'heart_course_reference']);
    $results = $query->execute()->fetchAll();

    foreach ($results as $result) {
      if ($result->heart_course_reference != '') {
        // Query to get the Course Name from course_product_field_data table.
        $coursequery = $this->database->select('heart_course_field_data', 'cn')
          ->fields('cn', ['id', 'label'])
          ->condition('cn.id', $result->heart_course_reference, '=');
        $courseresults = $coursequery->execute()->fetchAll();
        $courseresults = reset($courseresults);
        if (!empty($courseresults)) {
          $course[$result->id] = $courseresults->label . ' : ' . $result->product_title;
        }
      }
      else {
        $course[$result->id] = $result->product_title;
      }
    }

    // Return the Course Product with id and name.
    return $course;
  }

  /**
   * Get Cuurent User Diocese.
   */
  public function getCurrentUserDiocese($currentuserId): array {
    // Query to get the diocese admin and parish admin Diocese and Parish.
    $query = $this->database->select('user_profile_data_field_data', 'n');
    $query->join('heart_diocese_data_field_data', 'hddf', 'n.user_diocese_field = hdda.id');
    $query->condition('n.user_data', $currentuserId);
    $query->fields('hddf', ['id', 'label']);
    // ->condition('hdda.diocese_admins_target_id', $currentuserId, '=');
    $results = $query->execute()->fetchAll();
    $diocese = ['' => '- Select Diocese -'];
    foreach ($results as $result) {
      $diocese[$result->id] = $result->label;
    }
    // Return the diocese with id and name.
    return $diocese;
  }

  /**
   * Used to create incremental hash value for access code.
   */
  public function incrementalHash($len) {
    $charset = "BCDFGHKMNPRSTVXZ2346789";
    $charset = str_shuffle($charset);
    $charset = str_shuffle($charset);
    $base = strlen($charset);
    $result = '';

    $now_array = explode(' ', microtime());
    $now = $now_array[1];
    while ($now >= $base) {
      $i = $now % $base;
      $result = $charset[$i] . $result;
      // Explicitly cast to integer.
      $now = (int) ($now / $base);
    }
    return substr($result, -5);
  }

  /**
   * Used to create accesscode.
   */
  public function heartAccesscodeGeneratePhp() {

    $code_part_1 = self::incrementalHash(5);
    $code_part_2 = self::incrementalHash(5);
    $code_part_3 = self::incrementalHash(5);
    $code_part_4 = self::incrementalHash(5);

    $genarated_accesscode = $code_part_1 . '-' . $code_part_2 . '-' . $code_part_3 . '-' . $code_part_4;
    // The field names refer to RFC 4122 section 4.1.2.
    return $genarated_accesscode;
  }

  /**
   * Return webinar data with date and title.
   */
  public function getWebinarsDatas() {
    $webinar_options = ['' => '- select -'];
    $current_language = $this->languageManager->getCurrentLanguage()->getId();

    // First query to get the webinar product IDs to exclude.
    $query = $this->database->select('heart_zoom_webinars_field_data', 'n');
    $query->join('event_field_data', 'efd', 'n.id=efd.heart_webinar_reference');
    $query->join('commerce_product__field_event_reference', 'cped', 'efd.id=cped.field_event_reference_target_id');
    $query->condition('n.langcode', $current_language);
    $query->fields('efd', ['heart_webinar_reference']);
    $results = $query->execute()->fetchAll();

    // Prepare an array of IDs to exclude in the second query.
    $exclude_ids = [];
    if (!empty($results)) {
      foreach ($results as $result) {
        $exclude_ids[] = $result->heart_webinar_reference;
      }
    }

    // Second query to get webinars, excluding the IDs from the first query.
    $webinarquery = $this->database->select('heart_zoom_webinars_field_data', 'n');
    $webinarquery->fields('n', ['id', 'label', 'start_only_date']);

    // Exclude IDs if any are found from the first query.
    if (!empty($exclude_ids)) {
      $webinarquery->condition('n.id', $exclude_ids, 'NOT IN');
    }

    // Optionally filter by language again if needed.
    $webinarquery->condition('n.langcode', $current_language);

    // Fetch the results.
    $webinarresults = $webinarquery->execute()->fetchAll();
    // Populate the webinar options with the results from the second query.
    foreach ($webinarresults as $webinarresult) {
      $webinar_options[$webinarresult->id] = $webinarresult->label . ' - ' . $webinarresult->start_only_date;
    }
    return $webinar_options;
  }

  /**
   * Return alredy used webinar data with date and title.
   */
  public function getAllWebinarsDatas() {
    $webinar_options = ['' => '- select -'];
    $current_language = $this->languageManager->getCurrentLanguage()->getId();

    // Second query to get webinars, excluding the IDs from the first query.
    $webinarquery = $this->database->select('heart_zoom_webinars_field_data', 'n');
    $webinarquery->fields('n', ['id', 'label', 'start_only_date']);

    // Optionally filter by language again if needed.
    $webinarquery->condition('n.langcode', $current_language);

    // Fetch the results.
    $webinarresults = $webinarquery->execute()->fetchAll();
    // Populate the webinar options with the results from the second query.
    foreach ($webinarresults as $webinarresult) {
      $webinar_options[$webinarresult->id] = $webinarresult->label . ' - ' . $webinarresult->start_only_date;
    }
    return $webinar_options;
  }

  /**
   * Get Course Classes.
   */
  public function getCourseClassesById($courseId): array {
    // Get the current language.
    $current_language = $this->languageManager->getCurrentLanguage()->getId();
    // Query to get the Licence quantiy of course
    // from heart_license_field_data table.
    $results = [];
    $query = $this->database->select('heart_class_field_data', 'n');
    $query->fields('n', ['label', 'id']);
    $query->condition('n.course_field', $courseId, '=');
    // Optionally filter by language again if needed.
    $query->condition('n.langcode', $current_language);
    $results = $query->execute()->fetchAll();
    // Return the Course License Quantity.
    return $results;
  }

  /**
   * Get classes by course id.
   */
  public function getClassByCourseId($course_id) {
    // Get the current language.
    $current_language = $this->languageManager->getCurrentLanguage()->getId();
    // Query to get the Course Product from course_product_field_data table.
    $query = $this->database->select('heart_class_field_data', 'n');
    $query = $query->fields('n', ['id', 'label']);
    $query = $query->condition('n.langcode', $current_language);
    $query = $query->condition('n.course_field', $course_id);
    $results = $query->execute()->fetchAll();
    $classes = [];
    foreach ($results as $result) {
      $classes[$result->id] = $result->label;
    }
    // Return the diocese with id and name.
    return $classes;
  }

}
