<?php

namespace Drupal\heart_certifications\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Certificate Operation Controller.
 */
class CertificateOperationController extends ControllerBase {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The file URL generator service.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected $fileUrlGenerator;

  /**
   * The file system service.
   *
   * @var \Drupal\Core\File\FileSystemInterface
   */
  protected $fileSystem;

  /**
   * Constructs a CertificateOperationController object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack service.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $file_url_generator
   *   The file URL generator service.
   * @param \Drupal\Core\File\FileSystemInterface $file_system
   *   The file system service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, RequestStack $request_stack, FileUrlGeneratorInterface $file_url_generator, FileSystemInterface $file_system) {
    $this->entityTypeManager = $entity_type_manager;
    $this->requestStack = $request_stack;
    $this->fileUrlGenerator = $file_url_generator;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('request_stack'),
      $container->get('file_url_generator'),
      $container->get('file_system')
    );
  }

  /**
   * Handles PDF download.
   */
  public function downloadPdf() {
    // Get the certificate id from the URL.
    $request = $this->requestStack->getCurrentRequest();
    $certificateId = $request->query->get('certificate_id');

    // Load the custom entity.
    $entity = $this->entityTypeManager->getStorage('heart_certificate')->load($certificateId);

    // Check if field is not empty.
    if ($entity && !$entity->get('upload_file')->isEmpty()) {
      $file = $entity->get('upload_file')->entity;
      $file_url = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());

      // Create a StreamedResponse to download the file directly.
      $response = new StreamedResponse(function () use ($file_url) {
        readfile($file_url);
      });

      $response->headers->set('Content-Type', 'application/pdf');
      $response->headers->set('Content-Disposition', ResponseHeaderBag::DISPOSITION_ATTACHMENT, $file->getFilename());

      return $response;
    }

    // If no file found, throw a 404 error.
    throw new NotFoundHttpException();
  }

}
