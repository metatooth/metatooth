<?php
declare(strict_types=1);

namespace Metatooth\SquareCheckout\Controller\Webhook;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\RawFactory;
use Psr\Log\LoggerInterface;

class Notify implements HttpPostActionInterface, CsrfAwareActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly RawFactory $rawFactory,
        private readonly LoggerInterface $logger,
    ) {}

    public function createCsrfValidationException(RequestInterface $request): ?InvalidRequestException
    {
        return null;
    }

    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    public function execute()
    {
        $this->logger->info(
            'Metatooth_SquareCheckout: webhook received',
            ['body' => $this->request->getContent()]
        );

        // TODO: verify Square-Signature header and process payment.completed events

        $result = $this->rawFactory->create();
        $result->setHttpResponseCode(200);
        $result->setContents('OK');
        return $result;
    }
}
