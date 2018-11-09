<?php

namespace Drupal\jsonapi_path_support\Routing;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Provides routes which serve a de-referenced path alias.
 *
 * @internal
 */
final class Routes implements ContainerInjectionInterface {

  /**
   * The JSON:API path support controller name.
   *
   * @var string
   */
  const CONTROLLER_NAME = 'jsonapi_path_support.request_forwarder:forward';

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Routes constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('entity_type.manager'));
  }

  /**
   * Gets all intermediating JSON:API path support routes.
   *
   * This provides routes that will intercept requests for canonical entity
   * routes and forward them to the JSON:API equivalent.
   */
  public function routes() {
    // Get routes which match the canonical paths for every entity type.
    $routes = array_reduce($this->entityTypeManager->getDefinitions(), function (RouteCollection $routes, EntityTypeInterface $entity_type) {
      // Only entity types with canonical routes will work.
      if (!empty($entity_type->getLinkTemplate('canonical'))) {
        $routes->add('jsonapi_path_support.' . $entity_type->id(), static::getRoute($entity_type));
      }
      return $routes;
    }, new RouteCollection());
    // Add the controller.
    $routes->addDefaults([RouteObjectInterface::CONTROLLER_NAME => self::CONTROLLER_NAME]);
    // Add the accepted methods. 'POST' is not supported because it is only for
    // JSON:API relationship and collection routes.
    $routes->setMethods(['HEAD', 'GET', 'PATCH', 'DELETE']);
    // Add the necessary route requirements. We must require the
    // `?_format=api_json` query string even though it is not specification
    // compliant to not break Drupal's cache. However, we still enforce the
    // required `Content-Type: application/vnd.api+json` header.
    $routes->addRequirements([
      '_format' => 'api_json',
      '_access' => 'TRUE',
      'condition' => 'request.headers.get(\'Content-Type\') matches \'/application\/vnd\.api\+json/i\'',
    ]);
    return $routes;
  }

  /**
   * Generate the base route definition that will forward requests to JSON:API.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type for which a route should be defined.
   *
   * @return \Symfony\Component\Routing\Route
   *   The intermediate route definition.
   */
  protected static function getRoute(EntityTypeInterface $entity_type) {
    $entity_type_id = $entity_type->id();
    $route = new Route(str_replace("{{$entity_type_id}}", '{entity}', $entity_type->getLinkTemplate('canonical')));
    $route->setOption('parameters', [
      'entity' => [
        'type' => "entity:$entity_type_id",
      ],
    ]);
    return $route;
  }

}
