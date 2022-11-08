<?php

namespace Ethernal\PartialInvoice\Block\Adminhtml\Order\Invoice\Partial;

class Form extends \Magento\Sales\Block\Adminhtml\Order\AbstractOrder
{

    public function getSaveUrl()
    {
        return $this->getUrl('*/*/submit');
    }

    public function getMaxInvoiceAmount()
    {
        $order   = $this->getOrder();
        $max     = $order->getGrandTotal() - $order->getTotalInvoiced();
        $baseMax = $order->getBaseGrandTotal() - $order->getBaseTotalInvoiced();
        return $this->_adminHelper->displayPrices($order, $baseMax, $max);
    }
}
