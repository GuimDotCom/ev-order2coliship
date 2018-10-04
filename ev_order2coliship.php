<?php
/* Security */
if (!defined('_PS_VERSION_')) {
  	exit;
}

class EV_Order2Coliship extends Module
{
    private $_html = '';
    private $_postErrors = array();

    function __construct()
    {
        $this->name = 'ev_order2coliship';
        $this->version = '1.0.1';
        $this->tab = 'administration';
        $this->page = basename(__FILE__, '.php');
        $this->author = 'Everlats.com';
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);

        parent::__construct();

        $this->displayName = $this->l('Orders to ColiShip');
        $this->description = $this->l('Allows you to download order information to import in ColiShip (Colissimo)');
    }

    function install()
    {
        if (!parent::install()) {
            return false;
        }

        if (!$this->registerHook('displayAdminOrderTabShip') || !$this->registerHook('displayAdminOrderContentShip')  or !Configuration::updateValue('EV_COLISHIP_STATUS_EXPORT', '3') or !$this->installTab('AdminOrder2Coliship', 'Export ColiShip', 0, 'AdminParentOrders')) {
            return false;
        }
        return true;
    }

    function uninstall()
    {
        if (!parent::uninstall() or !Configuration::deleteByName('EV_COLISHIP_STATUS_EXPORT')) {
            return false;
        }
        return true;
    }

    // $this->installTab('AdminOrder2Coliship', 'Export ColiShip', 0, 'AdminParentOrders')
    public function installTab($className, $tabName, $tabParentId, $tabParentName = false)
    {
        $tab = new Tab();
        $tab->active = 1;
        $tab->class_name = $className;
        $tab->name = array();

        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = $tabName;
        }

        if ($tabParentName) {
            $tab->id_parent = (int) Tab::getIdFromClassName($tabParentName);
        } else {
            $tab->id_parent = $tabParentId;
        }

        $tab->module = $this->name;

        return $tab->add();
    }

    public function getContent()
    {
        $this->_html = '<h2>'.$this->displayName.'</h2>';
        if (isset($_POST['submitEvo2c'])) {
            if (!$_POST['ev_coliship_status_export']) {
                $this->_postErrors[] = $this->l('Order status code to Export is required.');
            }
            if (!sizeof($this->_postErrors)) {
                Configuration::updateValue('EV_COLISHIP_STATUS_EXPORT', $_POST['ev_coliship_status_export']);
                $this->displayConf();
            } else {
                $this->displayErrors();
            }
        }

        $this->displayO2CDescription();
        $this->displayFormSettings();
        return $this->_html;
    }

    public function displayConf()
    {
        $this->_html .= '
        		<div class="conf confirm">
        			<img src="../img/admin/ok.gif" alt="'.$this->l('Confirmation').'" />
        			'.$this->l('Settings updated').'
        		</div>';
    }

    public function displayErrors()
    {
        $nbErrors = sizeof($this->_postErrors);
        $this->_html .= '
        		<div class="alert error">
        			<h3>'.($nbErrors > 1 ? $this->l('There are') : $this->l('There is')).' '.$nbErrors.' '.($nbErrors > 1 ? $this->l('errors') : $this->l('error')).'</h3>
        			<ol>';
                foreach ($this->_postErrors as $error) {
                    $this->_html .= '<li>'.$error.'</li>';
                }
                $this->_html .= '
        			</ol>
        		</div>';
    }


    public function displayO2CDescription()
    {
        $this->_html .= '
        		<table border="0"><tr>
        		<td style="vertical-align: top;">
        		<b>'.$this->l('This module allows you to download package information into a TXT file for ColiShip.').'</b><br />
        		'.$this->l('ColiShip is a software provided by La Poste to create shipping labels. (website: https://www.colissimo.fr/entreprise/coliship/)').'<br />
        		<br />'.$this->l('Please set the order status of orders that will be exported (example: processing status).').'<br /></td></tr></table><br />';
    }

    public function displayFormSettings()
    {
        $status = Configuration::get('EV_COLISHIP_STATUS_EXPORT');

        $content = $this->getListStatuses((int)Configuration::get('PS_LANG_DEFAULT'));

        $this->_html .= '
        		<form action="'.$_SERVER['REQUEST_URI'].'" method="post">
        		<fieldset>
        			<legend><img src="../img/admin/contact.gif" />'.$this->l('Settings').'</legend>
              <br/>
        			<label>'.$this->l('Order Status to export').'</label>
        			<div class="margin-form">
        			    <select name="ev_coliship_status_export">';
        			foreach($content as $c) {
          			  $this->_html .= '<option value="'.$c['id_order_state'].'"';
          			  if($c['id_order_state'] == $status) {
            			    $this->_html .= ' selected';
          			  }
          			  $this->_html .= '>'.$c['name'].'</option>';
        			}

        			$this->_html .= '</select></div><center><input type="submit" name="submitEvo2c" value="'.$this->l('Update settings').'" class="button" /></center>
        		</fieldset>
        		</form>';
    }

    protected function getListStatuses($id_lang)
    {
        $sql = '
            SELECT os.`id_order_state`, osl.`name`
            FROM `'._DB_PREFIX_.'order_state` os
            LEFT JOIN `'._DB_PREFIX_.'order_state_lang` osl ON (os.`id_order_state` = osl.`id_order_state`)
            WHERE `id_lang` = '.(int)$id_lang.' ORDER BY osl.`name`';

        return  Db::getInstance()->executeS($sql);
    }
}
