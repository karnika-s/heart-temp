<?php

namespace Drupal\heart_custom_forms\Controller;

use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\TempStore\PrivateTempStoreFactory;
use Drupal\heart_custom_forms\HeartCustomService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Custom twig functions.
 */
class GetDataInView extends AbstractExtension {

  /**
   * Entity type Manager service.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;
  /**
   * Request Stack service.
   *
   * @var Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The messenger service.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Custom Helper service.
   *
   * @var \Drupal\heart_custom_forms\HeartCustomService
   */
  protected $helper;

  /**
   * Drupal database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * The tempstore service.
   *
   * @var \Drupal\Core\TempStore\PrivateTempStoreFactory
   */
  protected $tempStoreFactory;

  /**
   * The Account inteface object.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected $currentUser;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new GetTeacherClassList instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $requestStack
   *   The request stack.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   * @param \Drupal\heart_custom_forms\HeartCustomService $helper
   *   The custom user forms helper service.
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection service.
   * @param \Drupal\Core\TempStore\PrivateTempStoreFactory $temp_store
   *   The private tempstore factory.
   * @param \Drupal\Core\Session\AccountInterface $current_user
   *   The Account interface service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RequestStack $requestStack, MessengerInterface $messenger, HeartCustomService $helper, Connection $connection, PrivateTempStoreFactory $temp_store, AccountInterface $current_user, LanguageManagerInterface $language_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->requestStack = $requestStack;
    $this->messenger = $messenger;
    $this->helper = $helper;
    $this->database = $connection;
    $this->tempStoreFactory = $temp_store;
    $this->currentUser = $current_user;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      // Load the service required to construct this class.
      $container->get('entity_type.manager'),
      $container->get('request_stack'),
      $container->get('messenger'),
      $container->get('heart_custom_forms.heart_custom_service'),
      $container->get('database'),
      $container->get('tempstore.private'),
      $container->get('current_user'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFunctions() {
    return [
      new TwigFunction('getClassDiocese', [$this, 'getClassDioceseByCourse']),
      new TwigFunction('getClassParish', [$this, 'getClassParishByCourse']),
      new TwigFunction('getClassCity', [$this, 'getClassCityByCourse']),
      new TwigFunction('getavailableLicense', [$this, 'getAvailableLicense']),
      new TwigFunction('getcurrentuserrole', [$this, 'getCurrentUserRole']),
      new TwigFunction('getclassparishName', [$this, 'getClassParishName']),
      new TwigFunction('getclassdioceseName', [$this, 'getClassDioceseName']),
      new TwigFunction('getviewblocktitletext', [$this, 'viewBlockTitleText']),
      new TwigFunction('getclassteacherName', [$this, 'getClassTeacherName'])
    ];
  }

  /**
   * Returns diocese name.
   */
  public function getClassDioceseByCourse($courseId) {
    $query = $this->database->select('heart_license_field_data', 'hlf');
    $query->leftjoin('heart_diocese_data_field_data', 'hdd', 'hlf.diocese_field=hdd.id');
    $query->condition('course_field', $courseId);
    $query->fields('hdd', ['label']);
    $results = $query->execute()->fetchAll();
    $result = reset($results);
    return $result->label ?? '';
  }

  /**
   * Returns user role.
   */
  public function getCurrentUserRole() {
    $currentUser = $this->currentUser;
    $roles = $currentUser->getRoles();
    if (in_array('facilitator', $roles)) {
      $role = 'facilitator';
    }
    if (in_array('parish_admin', $roles)) {
      $role = 'parish_admin';
    }
    if (in_array('diocesan_admin', $roles)) {
      $role = 'diocesan_admin';
    }
    if (in_array('sales_staff', $roles)) {
      $role = 'sales_staff';
    }
    if (in_array('consultant', $roles)) {
      $role = 'consultant';
    }
    if (in_array('administrator', $roles)) {
      $role = 'administrator';
    }
    return $role ?? '';
  }

  /**
   * Returns user is logged in.
   */
  public function viewBlockTitleText() {
    $currentUser = $this->currentUser;
    if($this->currentUser->isAuthenticated()){
      $logged_in = t('Suggested');
    }else{
      $logged_in = t('Featured');
    }
    return $logged_in;
  }

  /**
   * Returns parish name.
   */
  public function getClassParishByCourse($courseId) {
    $query = $this->database->select('heart_license_field_data', 'hlf');
    $query->leftjoin('heart_parish_data_field_data', 'hdd', 'hlf.parish_field=hdd.id');
    $query->condition('course_field', $courseId);
    $query->fields('hdd', ['label']);
    $results = $query->execute()->fetchAll();
    $result = reset($results);
    return $result->label ?? 'N/A';
  }

  /**
   * Returns class parish name.
   */
  public function getClassParishName($class_id) {
    // Get the current language code.
    $current_language = $this->languageManager->getCurrentLanguage()->getId();

    $query = $this->database->select('heart_class_field_data', 'hcf');
    $query->leftjoin('heart_parish_data_field_data', 'hdd', 'hcf.parish_field=hdd.id');
    $query->condition('hcf.id', $class_id);
    $query->condition('hdd.langcode', $current_language);
    $query->condition('hcf.langcode', $current_language);
    $query->fields('hdd', ['label']);
    $results = $query->execute()->fetchAll();
    $result = reset($results);
    return $result->label ?? 'N/A';
  }

  /**
   * Returns class diocese name.
   */
  public function getClassDioceseName($class_id) {
    // Get the current language code.
    $current_language = $this->languageManager->getCurrentLanguage()->getId();

    $query = $this->database->select('heart_class_field_data', 'hcf');
    $query->leftjoin('heart_diocese_data_field_data', 'hddf', 'hcf.diocese_field=hddf.id');
    $query->condition('hcf.id', $class_id);
    $query->condition('hddf.langcode', $current_language);
    $query->condition('hcf.langcode', $current_language);
    $query->fields('hddf', ['label']);
    $results = $query->execute()->fetchAll();
    $result = reset($results);
    return $result->label ?? '/';
  }

  /**
   * Returns city name.
   */
  public function getClassCityByCourse($courseId) {
    $query = $this->database->select('heart_license_field_data', 'hlf');
    $query->leftjoin('heart_diocese_data_field_data', 'hdd', 'hlf.diocese_field=hdd.id');
    $query->condition('course_field', $courseId);
    $query->fields('hdd', ['diocese_address__locality']);
    $results = $query->execute()->fetchAll();
    $result = reset($results);
    return $result->diocese_address__locality ?? 'N/A';
  }

  /**
   * Returns available license quantity.
   */
  public function getAvailableLicense($classId) {
    $class = $this->entityTypeManager->getStorage('heart_class')->load($classId->__toString());
    $license_assign = $class->licenses_available->value;
    $license_used = $class->licenses_used->value;
    $available = intval($license_assign) - intval($license_used);
    return $available . ' Available' ?? '';
  }

  /**
   * Returns available license quantity.
   */
  public function getClassTeacherName($classId) {
    $class = $this->entityTypeManager->getStorage('heart_class')->load($classId->__toString());
    $user_id = $class->invite_facilitator->target_id;
    if($user_id != null){
      $user = $this->entityTypeManager->getStorage('user_profile_data')->load($user_id);
      $teacherName = $user->first_name->value.' '.$user->last_name->value;
    }
    return $teacherName ?? 'N/A';
  }

}
