<?php

namespace Ethernal\PartialInvoice\Controller\Adminhtml\Order\Invoice;

use Magento\Backend\App\Action\Context;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\DB\Transaction;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Registry;
use Magento\Sales\Api\InvoiceRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Service\InvoiceService;

class Submit extends \Magento\Sales\Controller\Adminhtml\Invoice\AbstractInvoice\View implements HttpPostActionInterface
{
    /**
     * @var \Magento\Sales\Api\OrderRepositoryInterface
     */
    private $orderRepository;
    /**
     * @var \Magento\Sales\Model\Service\InvoiceService
     */
    private $invoiceService;
    /**
     * @var \Magento\Framework\DB\Transaction
     */
    private $transaction;

    public function __construct(
        Context                    $context,
        Registry                   $registry,
        ForwardFactory             $resultForwardFactory,
        OrderRepositoryInterface   $orderRepository,
        InvoiceService             $invoiceService,
        Transaction                $transaction,
        InvoiceRepositoryInterface $invoiceRepository = null
    ) {
        parent::__construct($context, $registry, $resultForwardFactory, $invoiceRepository);
        $this->invoiceRepository = $invoiceRepository;
        $this->orderRepository   = $orderRepository;
        $this->invoiceService    = $invoiceService;
        $this->transaction       = $transaction;
    }

    public function execute()
    {
        $orderId  = $this->getRequest()->getParam('order_id');
        $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        try {
            $order  = $this->orderRepository->get($orderId);
            $amount = $this->getRequest()->getParam('amount');
            $max    = $order->getBaseGrandTotal() - $order->getBaseTotalInvoiced();
            if ($amount != $max) {
                if ($amount <= 0 || $amount > $max) {
                    $this->messageManager->addErrorMessage(__('Amount to partial invoice is invalid, please try again.'));
                    return $redirect->setPath('sales/order_invoice/partial', ['order_id' => $orderId]);
                }
            }
            $invoice = $this->invoiceService->prepareInvoice($order);
            $invoice->setState(Order\Invoice::STATE_PAID);
            $invoice->save();

            $transactionSave =
                $this->transaction
                    ->addObject($invoice)
                    ->addObject($invoice->getOrder());
            $transactionSave->save();

            $order->addCommentToStatusHistory(
                __('Notified customer about invoice creation #%1.', $invoice->getId())
            )->setIsCustomerNotified(true)->save();

            $order->setTotalInvoiced($order->getTotalInvoiced() + $amount);
            $order->setBaseTotalInvoiced($order->getBaseTotalInvoiced() + $amount);

            $order->setTotalPaid($order->getTotalPaid() + $amount);
            $order->setBaseTotalPaid($order->getBaseTotalPaid() + $amount);

            $order->setSubtotalInvoiced($order->getSubtotalInvoiced() + $amount);
            $order->setBaseSubtotalInvoiced($order->getBaseSubtotalInvoiced() + $amount);


            $this->completeOrder($order);
            $this->orderRepository->save($order);

            $this->messageManager->addSuccessMessage(__('You have successfully created partial invoice for order.'));
            return $redirect->setPath('sales/order/view', ['order_id' => $orderId]);
        } catch (NoSuchEntityException $e) {
            $this->messageManager->addErrorMessage(__('The order that was requested doesn\'t exist. Verify the entity and try again.'));
            return $redirect->setPath('sales/order');
        }
    }

    /**
     * @param \Magento\Sales\Model\Order $order
     * @return void
     */
    public function completeOrder($order)
    {
        if ($order->getBaseGrandTotal() == $order->getBaseTotalInvoiced() && !$order->canShip()) {
            $order->setState(Order::STATE_COMPLETE)
                ->setStatus($order->getConfig()->getStateDefaultStatus(Order::STATE_COMPLETE));
        }
    }
}
