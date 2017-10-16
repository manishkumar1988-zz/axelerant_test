<?php

namespace Drupal\axelerant_test\Controller;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Defines the AxelerantTestController controller.
 */
class AxelerantTestController extends ControllerBase {

  protected $entityType;
  protected $queryFactory;
  protected $configFactory;

  /**
   * Constructor to inject dependency.
   *
   * @param Drupal\Core\Entity\EntityTypeManagerInterface $entityType
   * @param Drupal\Core\Entity\Query\QueryFactory $queryFactory
   */
  public function __construct(EntityTypeManagerInterface $entityType, QueryFactory $queryFactory, ConfigFactoryInterface $configFactory) {
    $this->entityType = $entityType;
    $this->queryFactory = $queryFactory;
    $this->configFactory = $configFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager'),
      $container->get('entity.query'),
      $container->get('config.factory')
    );
  }

  /**
   * Returns content for this controller.
   */
  public function content($siteapikey, $nid) {
    $query = $this->queryFactory->get('node');
    $query->condition('status', 1);
    $query->condition('nid', $nid);
    $query->condition('type', 'page');
    $nid = $query->execute();
    if (count($nid) < 1) {
      return new JsonResponse('{
        "error": {
          "code": 403,
          "message": "Access Denied"
        }
      }', 403, ['Content-Type' => 'application/json']);
    }
    $entity_storage = $this->entityType->getStorage('node');
    $entity = $entity_storage->load(array_values($nid)[0]);
    return new JsonResponse($entity->toArray(), 200, ['Content-Type' => 'application/json']);
  }

  /**
   * Checks access for this controller.
   */
  public function access($siteapikey, $nid) {
    $siteapikey_config = \Drupal::config('system.site')->get('siteapikey');
    $node = node_load($nid);
    if (!empty($siteapikey_config) && $siteapikey_config == $siteapikey && $siteapikey_config != 'No API Key yet' && is_numeric($nid) && $node->getType() == 'page') {
      return AccessResult::allowed();
    }
    return AccessResult::forbidden();
  }

}
