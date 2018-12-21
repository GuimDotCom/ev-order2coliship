<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminOrder2ColishipController extends ModuleAdminController
{
    /** @var int $step */
    private $step;

    /** @var string $header */
    private $header;

    /** @var int $selection */
    private $selection = 0;

    public function __construct()
    {
        $this->bootstrap  = true;
        $this->module = 'ev_order2coliship';
        parent::__construct();
        $this->meta_title = $this->l('Orders to ColiShip');

        if (!$this->module->active) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminHome'));
        }
        $this->initSteps();

    }

    public function initSteps()
    {
        $this->step = 1;
        if (Tools::isSubmit('submitBulkselectorders')) {
            $this->step = 2;
        }
        if (Tools::isSubmit('submitBulkselectAllorders')) {
            $this->step = 3;
        }
        if (!(int) $this->step || (int) $this->step > 3) {
            $this->step = 1;
        }
    }

    public function initToolBarTitle()
    {
        $this->toolbar_title[] = $this->l('Administration');
        $this->toolbar_title[] = $this->l('Orders to ColiShip');
    }

    public function initProcess()
    {
        switch ($this->step) {
            case 1:
            default:
                $this->initStep1();
                break;
            case 2:
                $this->initStep2();
                break;
            case 3:
                $this->initStep2(1);
                break;
        }
        parent::initProcess();
    }

    public function initContent()
    {
        parent::initContent();
    }

    /**
     * @param int  $idLang
     * @param null $orderBy
     * @param null $orderWay
     * @param int  $start
     * @param null $lim
     * @param bool $idLangShop
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function getList($idLang, $orderBy = null, $orderWay = null, $start = 0, $lim = null, $idLangShop = false)
    {
        parent::getList($idLang, $orderBy, $orderWay, $start, $lim, $idLangShop);
        if ($this->_listTotal > 0) {
            $this->bulk_actions = array(
                'select' => array(
                    'text' => $this->module->l('Export checked orders', 'AdminOrder2ColishipController'),
                    'icon' => 'icon-arrow-circle-o-right',
                ),
            );
        }
    }

    /**
     * @return array
     * @throws PrestaShopDatabaseException
     */
    public function getAllOrdersToProcess()
    {
        $selectedStates = (array)json_decode(Configuration::get('EV_COLISHIP_STATUS_EXPORT'));
        $dbQuery = new DbQuery();
        $dbQuery->select('o.`id_order`')
                ->from('orders', 'o');
        if ($selectedStates && is_array($selectedStates) && count($selectedStates) > 0) {
            $dbQuery->where('o.`current_state` IN ('.implode(',', array_map('intval', $selectedStates)).')');
        }
        $dbQuery->orderBy('o.date_add DESC');
        $results = Db::getInstance(_PS_USE_SQL_SLAVE_)
                     ->executeS($dbQuery);

        return array_column($results, 'id_order');
    }

    /**
     * @throws PrestaShopDatabaseException
     */
    public function initStep1()
    {
        $idLang = $this->context->language->id;
        $statusesList = array();
        $statuses = OrderState::getOrderStates((int) $idLang);
        foreach ($statuses as $status) {
            $statusesList[$status['id_order_state']] = $status['name'];
        }
        $countriesList = array();
        $countries = Country::getCountries((int) $idLang);
        foreach ($countries as $country) {
            $countriesList[$country['id_country']] = $country['name'];
        }
        $selectedStates = (array)json_decode(Configuration::get('EV_COLISHIP_STATUS_EXPORT'));

        $select = array(
            'o.`reference`',
            'CONCAT(LEFT(c.`firstname`, 1), ". ", c.`lastname`) AS `customer`',
            'CONCAT(LEFT(ad.`firstname`, 1), ". ", ad.`lastname`) AS `customer_delivery`',
            'osl.`name` AS `osname`',
            'os.`color`',
            'o.`date_add`',
            'ca.`name` AS `commercial_name`',
            'cl.`name` AS `country`',
            '"--"',
        );
        //@formatter:off
        $join = array(
            'LEFT JOIN `'._DB_PREFIX_.'orders` o ON o.`id_order` = a.`id_order`',
            'LEFT JOIN `'._DB_PREFIX_.'address` ad ON ad.`id_address` = o.`id_address_delivery`',
            'LEFT JOIN `'._DB_PREFIX_.'country` co ON co.`id_country` = ad.`id_country`',
            'LEFT JOIN `'._DB_PREFIX_.'country_lang` cl ON (cl.`id_country` = ad.`id_country` AND cl.`id_lang` = '.$idLang.')',
            'LEFT JOIN `'._DB_PREFIX_.'customer` c ON c.`id_customer` = o.`id_customer`',
            'LEFT JOIN `'._DB_PREFIX_.'carrier` ca ON ca.`id_carrier` = o.`id_carrier`',
            'LEFT JOIN `'._DB_PREFIX_.'order_state` os ON os.`id_order_state` = o.`current_state`',
            'LEFT JOIN `'._DB_PREFIX_.'order_state_lang` osl ON (osl.`id_order_state` = os.`id_order_state` AND osl.`id_lang` = '.(int) $idLang.')',

        );
        //@formatter:on
        $this->identifier = 'id_order';
        $this->table = 'orders';
        $this->list_id = 'orders';
        $this->_select = implode(',', $select);
        $this->_join = implode(' ', $join);
        if (!empty($selectedStates)) {
            $this->_where = 'AND o.current_state IN('.implode(',', array_map('intval', $selectedStates)).') ';
        } else {
            $this->_where = '';
        }
        $this->_where .= Shop::addSqlRestriction(false, 'o');
        $this->list_no_link = true;
        $this->_orderBy = 'o.date_add';
        $this->_orderWay = 'DESC';
        $this->fields_list = array(
            'reference'       => array(
                'title'          => $this->module->l('Reference', 'AdminOrder2ColishipController'),
                'remove_onclick' => true,
                'class'          => 'pointer col-reference-plus',
            ),
            'customer'        => array(
                'title'          => $this->module->l('Customer', 'AdminOrder2ColishipController'),
                'havingFilter'   => true,
                'remove_onclick' => true,
            ),
            'customer_delivery'        => array(
                'title'          => $this->module->l('Recipient', 'AdminOrder2ColishipController'),
                'havingFilter'   => true,
                'remove_onclick' => true,
            ),
            'osname'          => array(
                'title'          => $this->module->l('Order state', 'AdminOrder2ColishipController'),
                'remove_onclick' => true,
                'type'           => 'select',
                'color'          => 'color',
                'list'           => $statusesList,
                'filter_key'     => 'os!id_order_state',
                'filter_type'    => 'int',
                'order_key'      => 'osname',
            ),
            'date_add'        => array(
                'title'          => $this->module->l('Date', 'AdminOrder2ColishipController'),
                'remove_onclick' => true,
                'type'           => 'datetime',
                'filter_key'     => 'o!date_add',
            ),
            'commercial_name' => array(
                'title'          => $this->module->l('Colissimo Service', 'AdminOrder2ColishipController'),
                'remove_onclick' => true,
                'type'           => 'select',
                'list'           => array(),
                'filter_key'     => 'cs!commercial_name',
                'filter_type'    => 'string',
                'order_key'      => 'commercial_name',
            ),
            'country'         => array(
                'title'          => $this->module->l('Delivery country', 'AdminOrder2ColishipController'),
                'remove_onclick' => true,
                'type'           => 'select',
                'list'           => $countriesList,
                'filter_key'     => 'co!id_country',
                'filter_type'    => 'int',
                'order_key'      => 'country',
            ),
        );
    }

    /**
     * @param int $selection
     * @throws Exception
     * @throws PrestaShopDatabaseException
     * @throws SmartyException
     */
    public function initStep2($all = false)
    {
        if($all == 1) {
            $ids = $this->getAllOrdersToProcess();
        }
        else {
            $ids = Tools::getValue('ordersBox');
        }

        if (!$ids || empty($ids)) {
            $this->errors[] = $this->module->l('Please select at least one order.', 'AdminOrder2ColishipController');
            $this->step = 1;
            $this->initProcess();

            return;
        }

        $data = array();
        $buffer = '';
        foreach ($ids as $id_order) {
            // List of orders with statuses = EV_COLISHIP_STATUS_EXPORT

            $order = new Order(intval($id_order));
            $customer = new Customer($order->id_customer);
            $ad = new Address(intval($order->id_address_delivery));
            $carrier = new Carrier($order->id_carrier);
            $message = ''; // $order->getFirstMessage();
            $products = $order->getProducts();

            $weight = 0;
            foreach ($products as $k => $product) {
                $weight += $product["product_weight"]*$product["product_quantity"];
            }
            // weight have to be in gramme
            $weight = $weight*1000;

            $company = $ad->company != '' && $ad->company != '0' ? $ad->company:'';

            $result = Db::getInstance()->getRow('SELECT `iso_code` FROM `'._DB_PREFIX_.'country` WHERE `id_country` = '.intval($ad->id_country));
            $iso_code = $result["iso_code"];

            $reference = 'CLI' . $order->id_customer . $order->id_address_delivery;

            // Référence;Raison sociale;Service;Prénom;Nom;Etage couloir escalier;Entrée bâtiment;N° et voie;Lieu dit;Code postal;Commune;Code ISO du pays;Téléphone fixe;Email;Code porte1 ;Code porte2;Interphone;Instructions de livraison;Nom commercial chargeur;Poids

            $buffer .= utf8_decode('"'.$reference.'";"'.trim($company).'";"";"'.trim($ad->firstname).'";"'.trim($ad->lastname).'";"";"";"'.trim($ad->address1).'";"'.trim($ad->address2).'";"'.$ad->postcode.'";"'.$ad->city.'";"'.$iso_code.'";"'.trim($ad->phone).'";"'.$customer->email.'";"";"";"";"'.$message.'";"";"'.$weight.'"')."\n";
        }

        $name = utf8_decode("order_export_".date('YmdHis').".txt");
        if (isset($_SERVER['HTTP_USER_AGENT']) && strpos($_SERVER['HTTP_USER_AGENT'], 'MSIE')) {
            header('Content-Type: application/force-download');
        } else {
            header('Content-Type: application/octet-stream');
        }
        if (headers_sent()) {
            echo 'Some data has already been output to browser, can\'t send PDF file';
        }
        header('Content-Length: '.strlen($buffer));
        header('Content-disposition: attachment; filename="'.$name.'"');
        echo $buffer;
        die();
    }
}
