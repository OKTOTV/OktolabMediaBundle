<?php

namespace Oktolab\MediaBundle\Model;

use Bprs\AppLinkBundle\BprsAppLinkUrlInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ApiUrlService implements BprsAppLinkUrlInterface
{
    private $router;
    private $route_names;

    public function __construct($router, $route_names)
    {
        $this->router = $router;
        $this->route_names = $route_names;
    }

    public function getUrls()
    {
        $urls = [];
        foreach ($this->route_names as $route_name) {
            $urls[$route_name] = $this->router->generate($route_name, [], UrlGeneratorInterface::ABSOLUTE_URL);
        }
        return $urls;
    }
}

?>
