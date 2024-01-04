<?php

namespace MVCME\View;

use Exception;
use RuntimeException;

/**
 * Class View
 */
class BaseView
{
    /**
     * Saved Data.
     * @var array
     */
    protected $data = [];

    /**
     * Data for the variables that are available in the Views.
     * @var array|null
     */
    protected $tempData;

    /**
     * The base directory to look in for our Views.
     * @var string
     */
    protected $viewPath;

    /**
     * Data for rendering including Caching and Debug Toolbar data.
     * @var array
     */
    protected $renderVars = [];

    /**
     * Whether data should be saved between renders.
     * @var bool
     */
    protected $saveData;

    /**
     * The name of the layout being used, if any.
     * Set by the `extend` method used within views.
     * @var string|null
     */
    protected $layout;

    /**
     * Holds the sections and their data.
     * @var array
     */
    protected $sections = [];

    /**
     * The name of the current section being rendered, if any.
     * @var array<string>
     */
    protected $sectionStack = [];

    public function __construct(?string $viewPath = null)
    {
        $this->viewPath = ROOTPATH . 'app/Views/' . rtrim($viewPath, '\\/ ') . DIRECTORY_SEPARATOR;
    }

    /**
     * Builds the output based upon a file name and any data that has already been set
     * @param string $view File name of the view source
     * @param array|null $options Reserved for 3rd-party uses since
     *                             it might be needed to pass additional info
     *                             to other template engines.
     * @param bool|null $saveData If true, saves data for subsequent calls,
     *                             if false, cleans the data after displaying,
     *                             if null, uses the config setting.
     * @return string
     */
    public function render(string $view, bool $saveData = false)
    {
        $saveData ??= $this->saveData;

        $fileExt = pathinfo($view, PATHINFO_EXTENSION);

        $this->renderVars['view'] = empty($fileExt) ? $view . '.php' : $view;

        $this->renderVars['file'] = $this->viewPath . $this->renderVars['view'];

        if (!is_file($this->renderVars['file'])) {
            throw new Exception("Cannot find file {$this->renderVars['file']}");
        }

        // Save current vars
        $renderVars = $this->renderVars;

        $output = (function (): string {
            extract($this->tempData);
            ob_start();
            include $this->renderVars['file'];

            return ob_get_clean() ?: '';
        })();

        // Get back current vars
        $this->renderVars = $renderVars;

        // When using layouts, the data has already been stored
        if ($this->layout !== null && $this->sectionStack === []) {
            $layoutView = $this->layout;
            $this->layout = null;

            $renderVars = $this->renderVars;
            $output = $this->render($layoutView, $saveData);

            $this->renderVars = $renderVars;
        }

        $this->tempData = null;

        return $output;
    }

    /**
     * Sets several pieces of view data at once
     * @return $this
     */
    public function setData(array $data = [])
    {
        $this->tempData ??= $this->data;
        $this->tempData = array_merge($this->tempData, $data);

        return $this;
    }

    /**
     * Specifies that the current view should extend an existing layout.
     * @return void
     */
    public function extend(string $layout)
    {
        $this->layout = $layout;
    }

    /**
     * Starts holds content for a section within the layout.
     * @param string $name Section name
     * @return void
     */
    public function section(string $name)
    {
        // Saved to prevent BC
        $this->sectionStack[] = $name;

        ob_start();
    }

    /**
     * Captures the last section
     * @return void
     */
    public function endSection()
    {
        $contents = ob_get_clean();

        if ($this->sectionStack === []) {
            throw new RuntimeException('View themes, no current section.');
        }

        $section = array_pop($this->sectionStack);

        // Ensure an array exists so we can store multiple entries for this.
        if (!array_key_exists($section, $this->sections)) {
            $this->sections[$section] = [];
        }

        $this->sections[$section][] = $contents;
    }

    /**
     * Renders a section's contents.
     * @param bool $saveData If true, saves data for subsequent calls,
     *                       if false, cleans the data after displaying.
     * @return void
     */
    public function renderSection(string $sectionName, bool $saveData = false)
    {
        if (!isset($this->sections[$sectionName])) {
            echo '';

            return;
        }

        foreach ($this->sections[$sectionName] as $key => $contents) {
            echo $contents;
            if ($saveData === false) {
                unset($this->sections[$sectionName][$key]);
            }
        }
    }

    /**
     * Used within layout views to include additional views.
     * @param bool $saveData
     * @return string
     */
    public function include(string $view, $saveData = true)
    {
        return $this->render($view, $saveData);
    }

    protected function prepareTemplateData(bool $saveData): void
    {
        $this->tempData ??= $this->data;

        if ($saveData) {
            $this->data = $this->tempData;
        }
    }
}
