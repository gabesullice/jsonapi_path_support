<?php

namespace Drupal\jsonapi_path_support\EventSubscriber;

use Drupal\jsonapi\EventSubscriber\JsonApiRequestValidator;
use Drupal\jsonapi_path_support\Routing\Routes;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;

/**
 * Decorates the JSON API param validator and permits the _format query string.
 *
 * @internal
 */
final class JsonApiRequestValidatorDecorator extends JsonApiRequestValidator {

  /**
   * {@inheritdoc}
   */
  public function onRequest(GetResponseEvent $event) {
    $request = $event->getRequest();
    if ($request->get(RouteObjectInterface::CONTROLLER_NAME) === Routes::CONTROLLER_NAME && $request->query->has('_format')) {
      $request->query->remove('_format');
    }
    parent::onRequest($event);
  }

}
