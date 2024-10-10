<?php

declare(strict_types=1);

namespace Drupal\heart_custom_forms\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Mail\MailManagerInterface;
use Drupal\Core\PageCache\ResponsePolicy\KillSwitch;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Routing\UrlGeneratorInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\heart_custom_forms\HeartCustomService;
use Drupal\heart_user_data\UserRefData;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Drupal\commerce_price\Price;
use Drupal\Component\Datetime\TimeInterface;

/**
 * Returns responses for Heart zoom routes.
 */
final class InviteAcceptRejectController extends ControllerBase {
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
   * The user reference data creation.
   *
   * @var \Drupal\heart_user_data\UserRefData
   */
  protected $userRefData;

  /**
   * The mail manager.
   *
   * @var \Drupal\Core\Mail\MailManagerInterface
   */
  protected $mailManager;

  /**
   * Custom Helper service.
   *
   * @var \Drupal\heart_custom_forms\HeartCustomService
   */
  protected $helper;

  /**
   * The form builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The date and time.
   *
   * @var Drupal\Core\Datetime\DrupalDateTime
   */
  protected $dateTime;

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
   * @param \Drupal\heart_user_data\UserRefData $userRefData
   *   The user reference data creation.
   * @param \Drupal\Core\Mail\MailManagerInterface $mail_manager
   *   The mail manager.
   * @param Drupal\heart_custom_forms\HeartCustomService $helper
   *   The custom helper service.
   * @param \Drupal\Core\Form\FormBuilderInterface $formBuilder
   *   The form builder service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   * @param Drupal\Core\Datetime\DrupalDateTime $date_time
   *   The date and time.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager,
  RouteMatchInterface $route_match,
  UrlGeneratorInterface $url,
  AccountInterface $current_user,
  KillSwitch $page_cache_kill_switch,
  UserRefData $userRefData,
  MailManagerInterface $mail_manager,
  HeartCustomService $helper,
  FormBuilderInterface $formBuilder,
  RendererInterface $renderer,
  RequestStack $request_stack,
  LanguageManagerInterface $language_manager,
  TimeInterface $date_time) {
    $this->entityTypeManager = $entity_type_manager;
    $this->routematch = $route_match;
    $this->url = $url;
    $this->currentUser = $current_user;
    $this->pageCacheKillSwitch = $page_cache_kill_switch;
    $this->userRefData = $userRefData;
    $this->mailManager = $mail_manager;
    $this->helper = $helper;
    $this->formBuilder = $formBuilder;
    $this->renderer = $renderer;
    $this->requestStack = $request_stack;
    $this->languageManager = $language_manager;
    $this->dateTime = $date_time;
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
      $container->get('page_cache_kill_switch'),
      $container->get('heart_user_data.user_ref_data'),
      $container->get('plugin.manager.mail'),
      $container->get('heart_custom_forms.heart_custom_service'),
      $container->get('form_builder'),
      $container->get('renderer'),
      $container->get('request_stack'),
      $container->get('language_manager'),
      $container->get('datetime.time'),
    );
  }

  /**
   * Accept Invitation controller.
   */
  public function accept($user_id, $class_id) {
    // Get the current request object from the request stack.
    $current_request = $this->requestStack->getCurrentRequest();

    // Retrieve all query parameters as an associative array.
    $type = $current_request->query->get('type');
    $currentUser = $this->currentUser;
    if ($currentUser->isAnonymous()) {
      // // If user is not logged in, redirect to the login page.
      $destination = Url::fromRoute('<current>', ['type' => $type])->toString();
      return $this->redirect('user.login', [], ['query' => ['destination' => $destination]]);
    }
    // Check if the logged-in user matches the user_id provided in the link.
    if ($currentUser->id() != $user_id) {
      // Return access denied response if IDs do not match.
      throw new AccessDeniedHttpException();
    }
    // Get the current request object from the request stack.
    $current_request = $this->requestStack->getCurrentRequest();
    $type = $current_request->query->get('type');
    if (!empty($class_id) && !empty($user_id)) {
      // Load user profile data with user id.
      $custom_entity = $this->entityTypeManager->getStorage('user_profile_data');
      $query = $custom_entity->getQuery()
        ->condition('user_data', $user_id)
        ->accessCheck(FALSE);
      $entity_ids = $query->execute();

      if (!empty($entity_ids)) {
        $userp_id = reset($entity_ids);
        // Check if any class invitation is present for this user.
        // If present update entity.
        $class_invitation_entity = $this->entityTypeManager->getStorage('class_invitation');
        $query = $class_invitation_entity->getQuery()
          ->condition('class_reference', $class_id)
          ->condition('invited_user', $userp_id)
          ->condition('invitation_type', $type)
          ->accessCheck(FALSE);
        $entity_ids = $query->execute();
        if (!empty($entity_ids)) {
          $class_invitation = reset($entity_ids);
          $class_invitation = $this->entityTypeManager->getStorage('class_invitation')->load($class_invitation);
          if ($class_invitation->get('invitation_status')->value != 'accepted') {
            $class_invitation->set('invitation_status', 'accepted');
            $class_invitation->save();
          }
          else {
            return [
              '#markup' => $this->t('You are already accepted invitation for this class.'),
            ];
          }

          $user = $this->entityTypeManager->getStorage('user')->load($user_id);
          if ($user) {
            $uid = $user->id();
            $rids = $user->getRoles();
            if ($type == 'teacher') {
              // Assign facilatator role to the user if not already assigned.
              if (!in_array('facilitator', $rids)) {
                $user->addRole('facilitator');
                $user->save();
              }
            }
            elseif ($type == 'student') {
              // Assign learner role to the user if not already assigned.
              if (!in_array('learner', $rids)) {
                $user->addRole('learner');
                $user->save();
              }
            }
            // Add class info to user refrence data table.
            $this->userRefData->userRefDataAdd($uid, 'heart_class', $class_id, 'heart_class');
          }
          if (!empty($userp_id) && !empty($class_id)) {
            // Load class entity and update data.
            $class = $this->entityTypeManager->getStorage('heart_class')->load($class_id);
            if ($type == "teacher") {
              // Get already facilatator.
              $facilatators = $class->get('invite_facilitator')->getValue();
              $licenses_used = intval($class->licenses_used->value) + 1;
              if (!empty($facilatator)) {
                $facilatator_id = [];
                foreach ($facilatators as $facilatator) {
                  $facilatator_id[] = $facilatator['target_id'];
                }
                if (!in_array($userp_id, $facilatator_id)) {
                  $class->invite_facilitator[] = ['target_id' => $userp_id];
                  // $class->set('licenses_used', $licenses_used);
                  $class->save();
                }
              }
              else {
                $class->invite_facilitator[] = ['target_id' => $userp_id];
                // $class->set('licenses_used', $licenses_used);
                $class->save();
              }
            }
            elseif ($type == 'student') {
              // Get already learners.
              $learners = $class->get('class_learner')->getValue();
              $licenses_used = intval($class->licenses_used->value) + 1;
              if (!empty($learners)) {
                $learners_id = [];
                foreach ($learners as $learner) {
                  $learners_id[] = $learner['target_id'];
                }
                if (!in_array($userp_id, $learners_id)) {
                  $class->class_learner[] = ['target_id' => $userp_id];
                  $class->set('licenses_used', $licenses_used);
                  $class->save();
                }
              }
              else {
                $class->class_learner[] = ['target_id' => $userp_id];
                $class->set('licenses_used', $licenses_used);
                $class->save();
              }
            }
            $course_product_id = $class->course_field->target_id;
            $uid = $user->id();
            $operation = 'add';
    

            if (!empty($course_product_id)) {
              // Load product.
              $product = $this->entityTypeManager->getStorage('commerce_product')->getQuery();
              $product->condition('type', 'course');
              $product->condition('field_course_product', $course_product_id);
              $product->accessCheck(FALSE);
              $entity_ids = $product->execute();
              if(!empty($entity_ids)){
                $entity_id = reset($entity_ids);
                $product = $this->entityTypeManager->getStorage('commerce_product')->load($entity_id);
                $product_entity_id = $product->id();
                $product_entity_bundle = $product->bundle();
                // Check if product already exist in user ref data table.
                $productExist = $this->userRefData->userRefDataGet($uid, 'commerce_product', $product_entity_bundle, $product_entity_id);
              }
            }

            if ($operation == 'add' && !empty($uid) && !empty($product) && empty($productExist)) {
              $variations = $product->getVariations();
              $variation_id = $variations[0]->id();

              // Load the product variation.
              $variation = $this->entityTypeManager->getStorage('commerce_product_variation')->load($variation_id);

              // Create an order item and save it.
              $order_item = $this->entityTypeManager
                ->getStorage('commerce_order_item')
                ->create([
                  'type' => 'default',
                  'purchased_entity' => $variation,
                  'quantity' => 1,
                  'field_complementary' => 1,
                  'field_product_type' => $product_entity_bundle,
                  'field_product' => $product,
                  'unit_price' => new Price('0', 'USD'),
                  'overridden_unit_price' => TRUE,
                ]);

              // Set the product title explicitly.
              $order_item->setTitle($product->getTitle());
              $order_item->save();

              // Create order and attach the previous order item generated.
              $order = $this->entityTypeManager
                ->getStorage('commerce_order')
                ->create([
                  'type' => 'default',
                  'mail' => $this->currentUser->getEmail(),
                  'uid' => $this->currentUser->id(),
                  'store_id' => 1,
                  'order_items' => [$order_item],
                  'placed' => $this->dateTime->getCurrentTime(),
                  'payment_gateway' => '',
                  'checkout_step' => 'complete',
                  'state' => 'completed',
                ]);

              // Add order number and save (based on order id).
              $order->set('order_number', $order->id());
              $order->save();

              // Add product to user ref data table.
              $this->userRefData->userRefDataAdd($uid, 'commerce_product', $product_entity_id, $product_entity_bundle);
            }

          }
          return [
            '#markup' => $this->t('Thank you for accepting the invitation for @class_name.', ['@class_name' => $class->label()]),
          ];
        }
        else {
          return [
            '#markup' => $this->t('Invitation for class has been canceled by admin.'),
          ];
        }
      }
    }
  }

  /**
   * Reject Invitation controller.
   */
  public function reject($user_id, $class_id) {
    if (!empty($class_id) && !empty($user_id)) {
      $class = $this->entityTypeManager->getStorage('heart_class')->load($class_id);
      // Load user profile data with user id.
      $custom_entity = $this->entityTypeManager->getStorage('user_profile_data');
      $query = $custom_entity->getQuery()
        ->condition('user_data', $user_id)
        ->accessCheck(FALSE);
      $entity_ids = $query->execute();

      if (!empty($entity_ids)) {
        $userp_id = reset($entity_ids);
        // Check if any class invitation is present for this user.
        // If present update entity.
        $class_invitation_entity = $this->entityTypeManager->getStorage('class_invitation');
        $query = $class_invitation_entity->getQuery()
          ->condition('class_reference', $class_id)
          ->condition('invited_user', $userp_id)
          ->accessCheck(FALSE);
        $entity_ids = $query->execute();
        if (!empty($entity_ids)) {
          $class_invitation = reset($entity_ids);
          $class_invitation = $this->entityTypeManager->getStorage('class_invitation')->load($class_invitation);
          $class_invitation->set('invitation_status', 'rejected');
          $class_invitation->save();
        }
      }
      return [
        '#markup' => $this->t('Thank you for your response regarding the invitation for @class_name.', ['@class_name' => $class->label()]),
      ];
    }
  }

  /**
   * Resend Invitation controller.
   */
  public function resend($user_id, $class_id) {
    if (!empty($class_id) && !empty($user_id)) {
      // Load user with user id.
      $user = $this->entityTypeManager->getStorage('user')->load($user_id);
      $class = $this->entityTypeManager->getStorage('heart_class')->load($class_id);
      $class_name = $class->label();
      $custom_entity = $this->entityTypeManager->getStorage('user_profile_data');
      $query = $custom_entity->getQuery()
        ->condition('user_data', $user->uid->value)
        ->accessCheck(FALSE);
      $entity_ids = $query->execute();
      $userp_id = '';
      if (!empty($entity_ids)) {
        $userp_id = reset($entity_ids);
      }
      $class_invitation_entity = $this->entityTypeManager->getStorage('class_invitation');
      $invitationquery = $class_invitation_entity->getQuery()
        ->condition('class_reference', $class_id)
        ->condition('invited_user', $userp_id)
        ->accessCheck(FALSE);
      $invite_ids = $invitationquery->execute();
      if (!empty($invite_ids)) {
        $invite_id = reset($invite_ids);
        $class_invitation = $this->entityTypeManager->getStorage('class_invitation')->load($invite_id);
        $type = $class_invitation->invitation_type->value;
        $class_invitation->set('invitation_status', 'pending');
        $class_invitation->save();

        // Generate accept and reject links.
        // $accept_link = Url::fromRoute('heart_custom_forms.accept_invite',
        // ['user_id' => $user->uid->value, 'class_id' => $class_id],
        // ['absolute' => TRUE]
        // );.
        $accept_link = Url::fromRoute('heart_custom_forms.accept_invite',
                        ['user_id' => $user->uid->value, 'class_id' => $class_id],
                        [
                          'absolute' => TRUE,
                          'query' => ['type' => $type],
                        ],
                      );
        // $reject_link = Url::fromRoute('heart_custom_forms.reject_invite',
        //         ['user_id' => $user->uid->value, 'class_id' => $class_id],
        //         ['absolute' => TRUE]
        //         );
        if ($type == 'teacher') {
          // Email the user.
          $subject = 'Invitation for Class Facilitator';
          $body = [
            'class_name' => $class_name,
            'class_id' => $class->id(),
            'accept_link' => $accept_link,
          // 'reject_link' => $reject_link,
          ];

          $langcode = 'en';
          $module = 'heart_diocese';
          $key = 'heart_class_invite_facilitator';
          $message = $body;

          $params['message'] = $message;
          $params['subject'] = $subject;
          $send = TRUE;

          $sent = $this->mailManager->mail($module, $key, $user->mail->value, $langcode, $params, NULL, $send);
        }

        // Check if email was sent successfully.
        if ($sent['result'] !== TRUE) {
          return new JsonResponse([
            'status' => 'failed',
            'message' => 'Failed to send email invitation.',
          ]);
        }
        // Get the view block.
        $args = [$class_id];
        $rendered_view = $this->helper->getViewBlock('class_learners_view', $args, 'block_1');

        // Render the view block.
        $rendered_view_block = $this->renderer->renderRoot($rendered_view);

        // Ensure any necessary JavaScript settings are included.
        $settings_view = [];
        if (isset($rendered_view['#attached']['drupalSettings'])) {
          $settings_view = $rendered_view['#attached']['drupalSettings'];
        }

        // Get class facilitator view block.
        $facilitator_view = $this->helper->getViewBlock('class_learners_view', $args, 'class_facilitator_block');

        // Render the view block.
        $facilitator_view_block = $this->renderer->renderRoot($facilitator_view);
        $facilitator_settings_view = [];
        if (isset($facilitator_view['#attached']['drupalSettings'])) {
          $facilitator_settings_view = $facilitator_view['#attached']['drupalSettings'];
        }
        // Return the form HTML, view and settings.
        return new JsonResponse([
          'view_block_html' => $rendered_view_block,
          'settings_view' => $settings_view,
          'facilitator_view_block' => $facilitator_view_block,
          'facilitator_settings_view' => $facilitator_settings_view,
          'status' => 'success',
        ]);
      }
      // Return new JsonResponse("Invitation sent successfully.");.
    }
    else {
      return new JsonResponse([
        'status' => 'failed',
      ]);
    }
  }

  /**
   * Cancel Invitation controller.
   */
  public function cancel($user_id, $class_invitation_id) {
    if (!empty($class_invitation_id) && !empty($user_id)) {
      // Load user with user id.
      $user = $this->entityTypeManager->getStorage('user')->load($user_id);
      $class_invitation = $this->entityTypeManager->getStorage('class_invitation')->load($class_invitation_id);
      if (!empty($user) && !empty($class_invitation)) {
        $class_id = $class_invitation->get('class_reference')->target_id;
        $class_invitation->delete();
        $this->userRefData->userRefDataDelete($user_id, 'heart_class', $class_id, 'heart_class');
        // Get the view block.
        $args = [$class_id];
        $rendered_view = $this->helper->getViewBlock('class_learners_view', $args, 'block_1');

        // Render the view block.
        $rendered_view_block = $this->renderer->renderRoot($rendered_view);

        // Ensure any necessary JavaScript settings are included.
        $settings_view = [];
        if (isset($rendered_view['#attached']['drupalSettings'])) {
          $settings_view = $rendered_view['#attached']['drupalSettings'];
        }

        // Get class facilitator view block.
        $facilitator_view = $this->helper->getViewBlock('class_learners_view', $args, 'class_facilitator_block');

        // Render the view block.
        $facilitator_view_block = $this->renderer->renderRoot($facilitator_view);
        $facilitator_settings_view = [];
        if (isset($facilitator_view['#attached']['drupalSettings'])) {
          $facilitator_settings_view = $facilitator_view['#attached']['drupalSettings'];
        }
        
        return new JsonResponse([
          'view_block_html' => $rendered_view_block,
          'settings_view' => $settings_view,
          'facilitator_view_block' => $facilitator_view_block,
          'facilitator_settings_view' => $facilitator_settings_view,
          'status' => 'success',
          'message' => 'Invitation canceled successfully.',
        ]);
      }
      else {
        return new JsonResponse([
          'status' => 'failed',
          'message' => 'Failed to cancel invitation.',
        ]);
      }
    }
    else {
      return new JsonResponse([
        'status' => 'failed',
        'message' => 'Failed to cancel invitation.',
      ]);
    }
  }

  /**
   * Ajax call to get manage class form and learner view.
   */
  public function classDetailForm($id) {
    $current_request = $this->requestStack->getCurrentRequest();

    // Retrieve all query parameters as an associative array.
    $page = $current_request->query->get('page');
   // dump($type);exit;
    // Set the current language for the rendering process.
    // $current_language = $this->languageManager->getCurrentLanguage()->getId();   // \Drupal::service('content_translation.manager')->setLanguage($current_language);
    $cuurent_user_roles = $this->currentUser->getRoles();
    $role = FALSE;
    // Check current user role for sending flag to view class facilator view.
    if (in_array('diocesan_admin', $cuurent_user_roles) || in_array('administrator', $cuurent_user_roles) ||
     in_array('parish_admin', $cuurent_user_roles)) {
      $role = TRUE;
    }
    $form = $this->formBuilder->getForm('Drupal\heart_custom_forms\Form\ManageClassDetailsForm');
    $rendered_form = $this->renderer->renderRoot($form);
    if($page != null && $page == 'manage'){
      $form = $this->formBuilder->getForm('Drupal\heart_custom_forms\Form\ManageClassDetailsFormManage');
      $rendered_form = $this->renderer->renderRoot($form);
    }

    $formLicense = $this->formBuilder->getForm('Drupal\heart_custom_forms\Form\ClassAddMoreLicensesForm');
    $licenserendered_form = $this->renderer->renderRoot($formLicense);

    // Get the view block.
    $args = [$id];
    $rendered_view = $this->helper->getViewBlock('class_learners_view', $args, 'block_1');

    // Render the view block.
    $rendered_view_block = $this->renderer->renderRoot($rendered_view);
    
    if (in_array('diocesan_admin', $cuurent_user_roles) || in_array('parish_admin', $cuurent_user_roles)) {
      // Get class facilitator view block.
      $facilitator_view = $this->helper->getViewBlock('class_learners_view', $args, 'class_facilitator_block_for_parish');


      // Render the view block.
      $facilitator_view_block = $this->renderer->renderRoot($facilitator_view);
    }else{

      // Get class facilitator view block.
      $facilitator_view = $this->helper->getViewBlock('class_learners_view', $args, 'class_facilitator_block');

      // Render the view block.
      $facilitator_view_block = $this->renderer->renderRoot($facilitator_view);
    }

    // Ensure any necessary JavaScript settings are included.
    $settings = [];
    if (isset($form['#attached']['drupalSettings'])) {
      $settings = $form['#attached']['drupalSettings'];
    }
    $licensesettings = [];
    if (isset($formLicense['#attached']['drupalSettings'])) {
      $licensesettings = $formLicense['#attached']['drupalSettings'];
    }
    $settings_view = [];
    if (isset($rendered_view['#attached']['drupalSettings'])) {
      $settings_view = $rendered_view['#attached']['drupalSettings'];
    }
    $facilitator_settings_view = [];
    if (isset($facilitator_view['#attached']['drupalSettings'])) {
      $facilitator_settings_view = $facilitator_view['#attached']['drupalSettings'];
    }
    // Return the form HTML, view and settings.
    return new JsonResponse([
      'form_html' => $rendered_form,
      'licenserendered_form' => $licenserendered_form,
      'view_block_html' => $rendered_view_block,
      'settings' => $settings,
      'licensesettings' => $licensesettings,
      'settings_view' => $settings_view,
      'facilitator_view_block' => $facilitator_view_block,
      'facilitator_settings_view' => $facilitator_settings_view,
      'role' => $role,
    ]);
  }

  /**
   * Ajax call to get and manage user profile form.
   */
  public function userProfileEditForm($id) {
    // Set the current language for the rendering process.
    // $current_language = $this->languageManager->getCurrentLanguage()->getId();   // \Drupal::service('content_translation.manager')->setLanguage($current_language);
    $cuurent_user_roles = $this->currentUser->getRoles();
    $role = FALSE;
    // Check current user role for sending flag to view class facilator view.
    if (in_array('diocesan_admin', $cuurent_user_roles) || in_array('administrator', $cuurent_user_roles) ||
     in_array('parish_admin', $cuurent_user_roles)) {
      $role = TRUE;
    }
    $form = $this->formBuilder->getForm('Drupal\heart_custom_forms\Form\UserRegistrationForm');
    // $form['#id'] = $id;  // Add the user ID to form state.
    $rendered_form = $this->renderer->renderRoot($form);

    // Ensure any necessary JavaScript settings are included.
    $settings = [];
    if (isset($form['#attached']['drupalSettings'])) {
      $settings = $form['#attached']['drupalSettings'];
    }
    // Return the form HTML, view and settings.
    return new JsonResponse([
      'form_html' => $rendered_form,
      'settings' => $settings,
      'role' => $role,
      'user_id' => $id,
    ]);
  }

  /**
   * Ajax call to get and manage user classes.
   */
  public function userClassDataForm($id) {
    $cuurent_user_roles = $this->currentUser->getRoles();
    $role = FALSE;
    // Check current user role for sending flag to view class facilator view.
    if (in_array('diocesan_admin', $cuurent_user_roles) || in_array('administrator', $cuurent_user_roles) ||
     in_array('parish_admin', $cuurent_user_roles)) {
      $role = TRUE;
    }
    $form = $this->formBuilder->getForm('Drupal\heart_custom_forms\Form\UserClassesForm');
    // $form['#id'] = $id;  // Add the user ID to form state.
    $rendered_form = $this->renderer->renderRoot($form);

    // Ensure any necessary JavaScript settings are included.
    $settings = [];
    if (isset($form['#attached']['drupalSettings'])) {
      $settings = $form['#attached']['drupalSettings'];
    }
    // Return the form HTML, view and settings.
    return new JsonResponse([
      'form_html' => $rendered_form,
      'settings' => $settings,
      'role' => $role,
      'user_id' => $id,
    ]);
  }

  /**
   * Ajax call to get and manage user classes.
   */
  public function promotFront() {
    $querys = $this->entityTypeManager->getStorage('commerce_product')->loadMultiple();
    foreach($querys as $query){
      $query->set('field_promote_to_front_page', false);
      $query->save();
    }
  }

}
