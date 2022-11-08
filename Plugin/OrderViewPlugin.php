<?php

namespace Ethernal\PartialInvoice\Plugin;

use Magento\Framework\View\LayoutInterface;
use Magento\Sales\Block\Adminhtml\Order\View;

class OrderViewPlugin
{
    /**
     * @var \Magento\Framework\AuthorizationInterface
     */
    private $authorization;

    public function __construct(
        \Magento\Framework\AuthorizationInterface $authorization
    ) {
        $this->authorization = $authorization;
    }

    /**
     * @param View $subject
     * @param LayoutInterface $layout
     * @return array
     */
    public function beforeSetLayout(View $subject, LayoutInterface $layout): array
    {
        $order = $subject->getOrder();

        if ($this->authorization->isAllowed('Ethernal_PartialInvoice::partial_invoice') && $order->canInvoice()) {
            $subject->addButton(
                'order_partial_invoice',
                [
                    'label'   => __('Partial Invoice'),
                    'class'   => 'partial_invoice',
                    'id'      => 'order-view-partial-invoice',
                    'onclick' => 'setLocation(\'' . $subject->getUrl('sales/order_invoice/partial', ['order_id' => $order->getId()]) . '\')'
                ]
            );
        }

        return [$layout];
    }
}
