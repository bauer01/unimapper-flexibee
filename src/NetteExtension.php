<?php

namespace UniMapper\Extension;

use Nette\Diagnostics,
    UniMapper\Exceptions\FlexibeeException,
    UniMapper\Exceptions\PropertyException;

/**
 * Nette Framework extension.
 */
class NetteExtension extends \Nette\Config\CompilerExtension
{

    /** @var array $defaults Default configuration */
    public $defaults = array();

    /**
     * Processes configuration data
     *
     * @return void
     */
    public function loadConfiguration()
    {
        $builder = $this->getContainerBuilder();

        if ($builder->parameters["debugMode"]) {
            $builder->addDefinition($this->prefix("flexibeepanel"))
                ->setClass("UniMapper\Extension\NetteExtension\FlexibeePanel")
                ->addSetup(
                    'Nette\Diagnostics\Debugger::$bar->addPanel(?)',
                    array('@self')
                )
                ->addSetup(
                    'Nette\Diagnostics\Debugger::$blueScreen->addPanel(?)',
                    array('UniMapper\Extension\NetteExtension::renderException')
                );
            $builder->getDefinition("application")
                ->addSetup(
                    '$service->onStartup[] = ?',
                    array(array($this->prefix("@flexibeepanel"), "getTab"))
                );
        }
    }

    /**
     * Register extension
     *
     * @param \Nette\Configurator $configurator
     */
    public static function register(\Nette\Configurator $configurator)
    {
        $configurator->onCompile[] = function ($config, \Nette\DI\Compiler $compiler) {
            $compiler->addExtension("UniMapper", new NetteExtension);
        };
    }

    /**
     * Extend debugger bluescreen
     *
     * @param mixed $exception Exception
     *
     * @return array
     */
    public static function renderException($exception)
    {
        if ($exception instanceof PropertyException
            && $exception->getEntityPath() !== false
        ) {
            $link = Diagnostics\Helpers::editorLink(
                $exception->getEntityPath(),
                $exception->getEntityLine()
            );
            $code = Diagnostics\BlueScreen::highlightFile(
                $exception->getEntityPath(),
                $exception->getEntityLine()
            );
            return array(
                "tab" => "ORM Entity",
                "panel" =>  $link . "\n" . $code
            );
	} elseif ($exception instanceof FlexibeeException) {
            $request = $exception->getRequest();
            return array(
                "tab" => $request->method . " request",
                "panel" => '<h3>URL</h3>'
                    . '<a href="' . $request->uri . '">' . $request->uri . '</a>'
                    . ' <h3>Detail</h3>'
                    . Diagnostics\Helpers::clickableDump($request)
            );
        }
    }

}