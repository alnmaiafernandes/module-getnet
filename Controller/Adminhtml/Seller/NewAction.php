<?php

/**
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to https://www.fcamara.com.br/ for more information.
 *
 * @category  FCamara
 * @package   FCamara_
 * @copyright Copyright (c) 2020 FCamara Formação e Consultoria
 * @Agency    FCamara Formação e Consultoria, Inc. (http://www.fcamara.com.br)
 * @author    Danilo Cavalcanti de Moura <danilo.moura@fcamara.com.br>
 */

namespace FCamara\Getnet\Controller\Adminhtml\Seller;

class NewAction extends \Magento\Backend\App\Action
{
    /**
     * @var \FCamara\Getnet\Model\SellerFactory
     */
    protected $seller;

    /**
     * NewAction constructor.
     * @param \Magento\Backend\App\Action\Context $context
     * @param \FCamara\Getnet\Model\SellerFactory $seller
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \FCamara\Getnet\Model\SellerFactory $seller
    ) {
        $this->seller = $seller;
        parent::__construct($context);
    }

    /**
     * @return \Magento\Framework\App\ResponseInterface|\Magento\Framework\Controller\Result\Redirect|\Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        $this->_view->loadLayout();
        $this->_view->renderLayout();

        $data = $this->getRequest()->getParam('seller');

        if (is_array($data)) {
            $seller = $this->seller->create();
            $seller->setData($data)->save();
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('*/*/index');
        }
    }
}
