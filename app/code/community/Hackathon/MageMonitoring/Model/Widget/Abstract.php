<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Hackathon
 * @package     Hackathon_MageMonitoring
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class Hackathon_MageMonitoring_Model_Widget_Abstract
{
    // define config keys
    const CONFIG_START_COLLAPSED = 'collapsed';
    const CONFIG_DISPLAY_PRIO = 'display_prio';

    // global default values
    protected $_DEF_START_COLLAPSED = 0;
    protected $_DEF_DISPLAY_PRIO = 10;

    // base node for all config keys
    const CONFIG_PRE_KEY = 'widgets/';

    // callback marker
    const CALLBACK = 'cb:';

    protected $_output = array();
    protected $_buttons = array();
    protected $_config = array();

    /**
     * Returns unique widget id. You really don't want to override is. ;)
     *
     * @return string
     */
    public function getId()
    {
        return get_called_class();
    }

    /**
     * (non-PHPdoc)
     * @see Hackathon_MageMonitoring_Model_Widget::isActive()
     */
    public function isActive()
    {
        return true;
    }

    /**
     * Adds a row to output array.
     *
     * @param string $css_id
     * @param string $label
     * @param string $value
     * @param string $chart
     * @return $this
     */
    public function addRow($css_id, $label, $value = null, $chart = null)
    {
        $this->_output[] = array(
            'css_id' => $css_id,
            'label' => $label,
            'value' => $value,
            'chart' => $chart
        );

        return $this;
    }

    /**
     * Adds a button to button array.
     *
     * @param string $button_id
     * @param string $label
     * @param string $controller_action
     * @param string $url_params
     * @param string $confirm_message
     * @param string $css_class
     * @return $this
     */
    public function addButton(
        $button_id,
        $label,
        $controller_action,
        $url_params = null,
        $confirm_message = null,
        $css_class = 'f-right'
    ) {
        $b = Mage::app()->getLayout()->createBlock('adminhtml/widget_button');
        $b->setId($button_id);
        $b->setLabel($label);
        $b->setOnClick($this->getOnClick($controller_action, $url_params, $confirm_message));
        $b->setClass($css_class);
        $b->setType('button');

        $this->_buttons[] = $b;

        return $this;
    }

    /**
     * Get onClick data for button display.
     *
     * @param string $controller_action
     * @param string $url_params
     * @param string $confirm_message
     *
     * @return string
     */
    protected function getOnClick($controller_action, $url_params = null, $confirm_message = null)
    {
        $onClick = '';
        // check if this is an ajax call with callback
        if (!strncmp($controller_action, self::CALLBACK, strlen(self::CALLBACK))) {
            $callback = substr($controller_action, strlen(self::CALLBACK));
            $widgetId = $this->getId();
            $widgetName = $this->getName();
            $callbackUrl = Mage::helper('magemonitoring')->getWidgetUrl('*/widgetAjax/execCallback', $this->getId());
            $refreshUrl = 'null';
            // check if refresh flag is set
            if (isset($url_params['refreshAfter']) && $url_params['refreshAfter']) {
                $refreshUrl = '\'' . Mage::helper('magemonitoring')->getWidgetUrl(
                        '*/widgetAjax/refreshWidget',
                        $this->getId()
                    ) . '\'';
            }
            // add callback js
            $onClick .= "execWidgetCallback('$widgetId', '$widgetName', '$callback', '$callbackUrl', $refreshUrl);";
            // add confirm dialog?
            if ($confirm_message) {
                $onClick = "var r=confirm('$confirm_message'); if (r==true) {" . $onClick . "}";
            }

            return $onClick;
        }
        $url = Mage::getSingleton('adminhtml/url')->getUrl($controller_action, $url_params);
        if ($confirm_message) {
            $onClick = "confirmSetLocation('$confirm_message','$url')";
        } else {
            $onClick = "setLocation('$url')";
        }

        return $onClick;
    }

    /**
     * Returns output array.
     *
     * @return array|false
     */
    public function getButtons()
    {
        if (empty($this->_buttons)) {
            return false;
        }

        return $this->_buttons;
    }

    /**
     * Returns an array that can feed Hackathon_MageMonitoring_Block_Chart.
     *
     * @param string $canvasId
     * @param array $chartData
     * @param string $chartType
     * @param int $width
     * @param int $height
     *
     * @return array
     */
    public function createChartArray($canvasId, $chartData, $chartType = 'Pie', $width = 76, $height = 76)
    {
        return array(
            'chart_id' => $canvasId,
            'chart_type' => $chartType,
            'canvas_width' => $width,
            'canvas_height' => $height,
            'chart_data' => $chartData
        );
    }

    /**
     * (non-PHPdoc)
     * @see Hackathon_MageMonitoring_Model_Widget::displayCollapsed()
     */
    public function displayCollapsed()
    {
        return $this->getConfig(self::CONFIG_START_COLLAPSED);
    }

    /**
     * (non-PHPdoc)
     * @see Hackathon_MageMonitoring_Model_Widget::displayCollapsed()
     */
    public function getDisplayPrio()
    {
        return $this->getConfig(self::CONFIG_DISPLAY_PRIO);
    }


    /**
     * (non-PHPdoc)
     * @see Hackathon_MageMonitoring_Model_Widget::initConfig()
     */
    public function initConfig()
    {
        $this->addConfig(
            self::CONFIG_START_COLLAPSED,
            'Do not render widget on pageload?',
            $this->_DEF_START_COLLAPSED,
            'checkbox',
            false
        );

        $this->addConfig(
            self::CONFIG_DISPLAY_PRIO,
            'Display priority (0=top):',
            $this->_DEF_DISPLAY_PRIO,
            'text',
            false
        );

        return $this->_config;
    }

    /**
     * (non-PHPdoc)
     * @see Hackathon_MageMonitoring_Model_Widget::getConfig()
     */
    public function getConfig($config_key = null, $valueOnly = true)
    {
        if (empty($this->_config)) {
            $this->_config = $this->initConfig();
        }
        if ($config_key && array_key_exists($config_key, $this->_config)) {
            if ($valueOnly) {
                return $this->_config[$config_key]['value'];
            } else {
                return $this->_config[$config_key];
            }
        } else {
            if ($config_key) {
                return false;
            }
        }

        return $this->_config;
    }

    /**
     * (non-PHPdoc)
     * @see Hackathon_MageMonitoring_Model_Widget::addConfig()
     */
    public function addConfig(
        $config_key,
        $label,
        $defaultValue,
        $inputType = "text",
        $required = false,
        $tooltip = null
    ) {
        $this->_config[$config_key] = array(
            'label' => $label,
            'value' => $defaultValue,
            'type' => $inputType,
            'required' => $required,
            'tooltip' => $tooltip
        );

        return $this;
    }

    /**
     * (non-PHPdoc)
     * @see Hackathon_MageMonitoring_Model_Widget::loadConfig()
     */
    public function loadConfig()
    {

        foreach ($this->getConfig() as $key => $conf) {
            if ($value = Mage::getStoreConfig(
                self::CONFIG_PRE_KEY . strtolower(str_replace('_', '/', $this->getId() . '_' . $key))
            )
            ) {
                $this->_config[$key]['value'] = $value;
            }
        }

        return $this->_config;
    }

    /**
     * (non-PHPdoc)
     * @see Hackathon_MageMonitoring_Model_Widget::saveConfig()
     */
    public function saveConfig($post)
    {
        foreach ($this->getConfig() as $key => $conf) {
            $c = Mage::getModel('core/config');
            $value = '';
            if (array_key_exists($key, $post)) {
                $value = $post[$key];
            }
            $c->saveConfig(
                self::CONFIG_PRE_KEY . strtolower(str_replace('_', '/', $this->getId() . '_' . $key)),
                $value,
                'default',
                0
            );
        }

        return $this;
    }

    /**
     * (non-PHPdoc)
     * @see Hackathon_MageMonitoring_Model_Widget::deleteConfig()
     */
    public function deleteConfig()
    {
        foreach ($this->getConfig() as $key => $conf) {
            $c = Mage::getModel('core/config');
            $c->deleteConfig(
                self::CONFIG_PRE_KEY . strtolower(str_replace('_', '/', $this->getId() . '_' . $key)),
                'default',
                0
            );
        }

        return $this;
    }

}
