<?php

namespace Drupal\jsonapi_path_support\Controller;

use Drupal\Core\Url;
use Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Intercepts requests for canonical routes and forwards them to JSON:API.
 *
 * @internal
 */
final class Forwarder {

  /**
   * The HTTP kernel.
   *
   * @var \Symfony\Component\HttpKernel\HttpKernelInterface
   */
  protected $kernel;

  /**
   * The JSON:API resource type repository.
   *
   * @var \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface
   */
  protected $resourceTypeRepository;

  /**
   * Forwarder constructor.
   *
   * @param \Symfony\Component\HttpKernel\HttpKernelInterface $kernel
   *   The HTTP kernel.
   * @param \Drupal\jsonapi\ResourceType\ResourceTypeRepositoryInterface $resource_type_repository
   *   The JSON:API resource type repository.
   */
  public function __construct(HttpKernelInterface $kernel, ResourceTypeRepositoryInterface $resource_type_repository) {
    $this->kernel = $kernel;
    $this->resourceTypeRepository = $resource_type_repository;
  }

  /**
   * Forwards requests to JSON API.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   The JSON:API response.
   *
   * @throws \Exception
   */
  public function forward(Request $request) {
    return $this->kernel->handle($this->getJsonApiRequest($request), HttpKernelInterface::SUB_REQUEST);
  }

  /**
   * Creates a Request object for the appropriate JSON:API route.
   *
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The incoming request.
   *
   * @return \Symfony\Component\HttpFoundation\Request
   *   A corresponding JSON:API request.
   */
  protected function getJsonApiRequest(Request $request) {
    $entity = $request->get('entity');
    $entity_type_id = $entity->getEntityTypeId();
    $bundle = $entity->bundle();
    $route_parameters = ['entity' => $entity->uuid()];
    $resource_type = $this->resourceTypeRepository->get($entity_type_id, $bundle);
    $route_name = sprintf('jsonapi.%s.individual', $resource_type->getTypeName());
    $related_url = Url::fromRoute($route_name, $route_parameters)->toString(TRUE);
    $subrequest = Request::create($related_url->getGeneratedUrl(), 'GET', [], $request->cookies->all(), [], $request->server->all());
    $subrequest->query = $request->query;
    return $subrequest;
  }

}
