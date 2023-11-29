<?php

namespace Mautic\CoreBundle\Event;

use Mautic\CoreBundle\Twig\Helper\AssetsHelper;
use Symfony\Contracts\EventDispatcher\Event;

class CustomAssetsEvent extends Event
{
    protected \Mautic\CoreBundle\Twig\Helper\AssetsHelper $assetsHelper;

    public function __construct(AssetsHelper $assetsHelper)
    {
        $this->assetsHelper = $assetsHelper;
    }

    /**
     * @param string $location
     * @param string $context
     */
    public function addCustomDeclaration($declaration, $location = 'head', $context = AssetsHelper::CONTEXT_APP)
    {
        $this->assetsHelper->setContext($context)
            ->addCustomDeclaration($declaration, $location)
            ->setContext(AssetsHelper::CONTEXT_APP);

        return $this;
    }

    /**
     * @param string $location
     * @param bool   $async
     * @param null   $name
     * @param string $context
     */
    public function addScript($script, $location = 'head', $async = false, $name = null, $context = AssetsHelper::CONTEXT_APP)
    {
        $this->assetsHelper->setContext($context)
            ->addScript($script, $location, $async, $name)
            ->setContext(AssetsHelper::CONTEXT_APP);

        return $this;
    }

    /**
     * @param string $location
     * @param string $context
     */
    public function addScriptDeclaration($script, $location = 'head', $context = AssetsHelper::CONTEXT_APP)
    {
        $this->assetsHelper->setContext($context)
            ->addScriptDeclaration($script, $location)
            ->setContext(AssetsHelper::CONTEXT_APP);

        return $this;
    }

    /**
     * @param string $context
     */
    public function addStylesheet($stylesheet, $context = AssetsHelper::CONTEXT_APP)
    {
        $this->assetsHelper->setContext($context)
            ->addStylesheet($stylesheet)
            ->setContext(AssetsHelper::CONTEXT_APP);

        return $this;
    }

    /**
     * @param string $context
     */
    public function addStyleDeclaration($styles, $context = AssetsHelper::CONTEXT_APP)
    {
        $this->assetsHelper->setContext($context)
            ->addStyleDeclaration($styles)
            ->setContext(AssetsHelper::CONTEXT_APP);

        return $this;
    }
}
