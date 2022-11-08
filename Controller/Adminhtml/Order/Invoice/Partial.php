<?php

namespace Ethernal\PartialInvoice\Controller\Adminhtml\Order\Invoice;

use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\Page;

class Partial extends \Magento\Sales\Controller\Adminhtml\Order\View
{
    const ADMIN_RESOURCE = 'Ethernal_PartialInvoice::partial_invoice';

    public function execute()
    {
        $order          = $this->_initOrder();
        $resultRedirect = $this->resultRedirectFactory->create();
        if ($order) {
            try {
                $resultPage = $this->_initAction();
                $resultPage->getConfig()->getTitle()->prepend(__('Orders'));
            } catch (\Exception $e) {
                $this->logger->critical($e);
                $this->messageManager->addErrorMessage(__('Exception occurred during order load'));
                $resultRedirect->setPath('sales/order/index');
                return $resultRedirect;
            }
            $resultPage->getConfig()->getTitle()->prepend(sprintf("Partial Invoice For #%s", $order->getIncrementId()));
            return $resultPage;
        }
        $resultRedirect->setPath('sales/*/');
        return $resultRedirect;
    }
}
