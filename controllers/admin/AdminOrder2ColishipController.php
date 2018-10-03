<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

class AdminOrder2ColishipController extends ModuleAdminController
{
    public function __construct()
    {
        $this->display = 'view';
        $this->module = 'ev_order2coliship';
        parent::__construct();
        $this->meta_title = $this->l('Orders to ColiShip');

        if (!$this->module->active) {
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminHome'));
        }
    }

    public function initToolBarTitle()
    {
        $this->toolbar_title[] = $this->l('Administration');
        $this->toolbar_title[] = $this->l('Orders to ColiShip');
    }

    public function initContent()
    {
        parent::initContent();

        // List of orders with statuses = EV_COLISHIP_STATUS_EXPORT
        $order = new Order;
        $orders = $order->getOrderIdsByStatus((int)Configuration::get('EV_COLISHIP_STATUS_EXPORT'));

        if (count($orders) > 0) {
            $buffer = '';

            foreach ($orders as $id_order) {
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
        } else {
            // no order to export so go to renderView
        }
    }

    public function renderView()
    {
        $href = $this->context->link->getAdminLink('AdminModules', true) . '&configure=' . Tools::safeOutput($this->module->name);
        $tpl = $this->context->smarty->createTemplate(_PS_MODULE_DIR_.'/ev_order2coliship/views/templates/admin/order2coliship.tpl');
        $tpl->assign('href', (string)$href);
        return $tpl->fetch();
    }
}
