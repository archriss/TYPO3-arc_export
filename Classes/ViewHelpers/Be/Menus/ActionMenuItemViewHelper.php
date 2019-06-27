<?php

namespace Archriss\ArcExport\ViewHelpers\Be\Menus;

/**
 * Created by PhpStorm.
 * User: Tof
 * Date: 2019-06-13
 * Time: 17:04
 */

class ActionMenuItemViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Be\Menus\ActionMenuItemViewHelper
{

    /**
     * Renders an ActionMenu option tag
     *
     * @param string $label label of the option tag
     * @param string $controller controller to be associated with this ActionMenuItem
     * @param string $action the action to be associated with this ActionMenuItem
     * @param string $currentNamespace The current namespace used to detect selected action
     * @param array $arguments additional controller arguments to be passed to the action when this ActionMenuItem is selected
     * @return string the rendered option tag
     * @see \TYPO3\CMS\Fluid\ViewHelpers\Be\Menus\ActionMenuViewHelper
     */
    public function render($label, $controller, $action, $currentNamespace, array $arguments = [])
    {
        $uriBuilder = $this->controllerContext->getUriBuilder();
        $uri = $uriBuilder->reset()->uriFor($action, $arguments, $controller);
        $this->tag->addAttribute('value', $uri);
        $currentRequest = $this->controllerContext->getRequest();
        $currentController = $currentRequest->getControllerName();
        $currentAction = $currentRequest->getControllerActionName();
        if ($action === $currentAction && $controller === $currentController && $currentNamespace == $arguments['namespace']) {
            $this->tag->addAttribute('selected', 'selected');
        }
        $this->tag->setContent($label);
        return $this->tag->render();
    }

}