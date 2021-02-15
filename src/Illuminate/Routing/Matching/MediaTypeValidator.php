<?php

namespace Illuminate\Routing\Matching;

use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\AcceptHeader;
use Symfony\Component\HttpFoundation\AcceptHeaderItem;

class MediaTypeValidator implements ValidatorInterface
{
    /**
     * Validate a given rule against a route and request.
     *
     * @param \Illuminate\Routing\Route $route
     * @param \Illuminate\Http\Request  $request
     *
     * @return bool
     */
    public function matches(Route $route, Request $request)
    {
        // just return true if we don't have any constraint
        if (!$route->hasMediaTypeConstraint()) {
            return true;
        }

        $acceptHeader = AcceptHeader::fromString($request->header('Accept'));

        foreach ($acceptHeader->all() as $headerItem) {
            if ($this->validate($route, $headerItem)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Validate an accept header item against content negotiation rules
     *
     * @param \Illuminate\Routing\Route                          $route
     * @param \Symfony\Component\HttpFoundation\AcceptHeaderItem $headerItem
     *
     * @return bool
     */
    protected function validate(Route $route, AcceptHeaderItem $headerItem)
    {
        // we are only interested in application-specific media types
        if (!Str::startsWith($headerItem->getValue(), 'application')) {
            return false;
        }

        return $this->validateVersion($route, $headerItem) && $this->validateHeaderParts($route, $headerItem);
    }

    /**
     * Validate version
     *
     * @param \Illuminate\Routing\Route                          $route
     * @param \Symfony\Component\HttpFoundation\AcceptHeaderItem $headerItem
     *
     * @return bool
     */
    protected function validateVersion(Route $route, AcceptHeaderItem $headerItem)
    {
        if (!$version = $route->getAction('version')) {
            return true;
        }

        if (!is_array($version)) {
            $version = [$version];
        }

        return in_array($headerItem->getAttribute('version'), $version);
    }

    /**
     * Validate header parts tree and subTypeSuffix
     *
     * @param \Illuminate\Routing\Route                          $route
     * @param \Symfony\Component\HttpFoundation\AcceptHeaderItem $headerItem
     *
     * @return bool
     */
    protected function validateHeaderParts(Route $route, AcceptHeaderItem $headerItem)
    {
        if (!preg_match('#^application\/(?<tree>.+)\+(?<subtypeSuffix>.+)$#S', $headerItem->getValue(), $headerParts)) {
            return false;
        }

        foreach (['tree', 'subtypeSuffix'] as $headerPart) {
            // ignore if we don't have this constraint
            if (!$actionValue = $route->getAction($headerPart)) {
                continue;
            }

            if ($actionValue != $headerParts[$headerPart]) {
                return false;
            }
        }

        return true;
    }
}
