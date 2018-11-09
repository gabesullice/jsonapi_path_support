<?php

namespace Drupal\jsonapi_path_support\Routing;

use Drupal\Core\Routing\FilterInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouteCollection;

/**
 * Ensures that JSON:API path support's routes are evaluated before any others.
 *
 * Without this, the default routes will serve HTML before this module has an
 * opportunity to provide a JSON:API response.
 */
final class RouteFilter implements FilterInterface {

  /**
   * {@inheritdoc}
   */
  public function filter(RouteCollection $collection, Request $request) {
    if ($request->headers->get('Content-Type', FALSE) !== 'application/vnd.api+json') {
      return $collection;
    }
    // Reorder routes so that JSON:API path support's routes come first.
    $routes = new RouteCollection();
    $unowned_routes = new RouteCollection();
    foreach ($collection->all() as $name => $route) {
      if ($route->getDefault(RouteObjectInterface::CONTROLLER_NAME) === Routes::CONTROLLER_NAME) {
        $routes->add($name, $route);
      }
      else {
        $unowned_routes->add($name, $route);
      }
    }
    $routes->addCollection($unowned_routes);
    return $routes;
  }

}
