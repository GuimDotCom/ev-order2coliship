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
        $this->version = '1.2.0';
        $this->tab = 'administration';
        $this->page = basename(__FILE__, '.php');
        $this->author = 'Everlats.com';
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Orders to ColiShip');
        $this->description = $this->l('Allows you to download order information to import in ColiShip (Colissimo)');
    }

    function install()
    {
        if (!parent::install()) {
            return false;
        }

        if (!$this->registerHook('displayAdminOrderTabShip') || !$this->registerHook('displayAdminOrderContentShip')  or !Configuration::updateValue('EV_COLISHIP_STATUS_EXPORT', '3') || !$this->registerHook('displayAdminOrder2ColishipListAfter') or !$this->installTab('AdminOrder2Coliship', 'Export ColiShip', 0, 'AdminParentOrders')) {
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
                Configuration::updateValue('EV_COLISHIP_STATUS_EXPORT', json_encode($_POST['ev_coliship_status_export']));
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
        		<br />'.$this->l('Please check one or more orders\' statuses that will appears in exported list (example: processing status).').'<br /></td></tr></table><br />';
    }

    public function displayFormSettings()
    {
        $status_export = Configuration::get('EV_COLISHIP_STATUS_EXPORT');
        $status_export = (array)json_decode($status_export);

        $idLang = $this->context->language->id;
        $statusesList = array();
        $statuses = OrderState::getOrderStates((int) $idLang);
        foreach ($statuses as $status) {
            $statusesList[$status['id_order_state']] = $status['name'];
        }

        $this->_html .= '
        		<form action="'.$_SERVER['REQUEST_URI'].'" method="post">
        		<fieldset>
        			<legend><img src="../img/admin/contact.gif" />'.$this->l('Settings').'</legend>
              <br/>
        			<label>'.$this->l('Order Statuses List').'</label>
        			<div class="margin-form">';
        			foreach($statusesList as $status_id => $status_name) {
          			  $this->_html .= '<div class="checkbox">
                                    <label>
                                      <input type="checkbox" name="ev_coliship_status_export[]" value="'.$status_id.'"';
          			  if(in_array($status_id, $status_export)) {
            			    $this->_html .= ' checked="checked"';
          			  }
                  $this->_html .= ' /> '.$status_name.'
                                    </label>
                                  </div>';
        			}

        			$this->_html .= '</div><center><input type="submit" name="submitEvo2c" value="'.$this->l('Update settings').'" class="button" /></center>
        		</fieldset>
        		</form>
        		<br/>
     		    <p><a href="https://www.diffuseurs-dessentielles.com/?utm_source=prestashop-module&utm_medium=banner&utm_campaign=order2coliship" target="_blank"><img src="https://www.diffuseurs-dessentielles.com/media/banner/banner400x263.jpg" alt="Diffuseurs d\'Essentielles"/></a></p>
';
    }

    /**
     * @param array $params
     */
    public function hookdisplayAdminOrder2ColishipListAfter($params)
    {
        return $this->context->smarty->display(
            $this->local_path.'views/templates/hook/admin/displayAdminOrder2ColishipListAfter.tpl'
        );
    }
}
