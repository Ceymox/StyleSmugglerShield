<?php
/**
 * Copyright © Ceymox. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Ceymox\StyleSmugglerShield\Plugin;

use Ceymox\StyleSmugglerShield\Model\Config;
use Ceymox\StyleSmugglerShield\Model\DirectiveScanner;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\GraphQl\Controller\GraphQl;
use Psr\Log\LoggerInterface;

/**
 * Rejects GraphQL requests carrying Magento template directive syntax.
 */
class GraphQlDirectiveGuardPlugin
{
    /**
     * @param Config $config
     * @param DirectiveScanner $scanner
     * @param JsonFactory $jsonFactory
     * @param HttpResponse $httpResponse
     * @param LoggerInterface $logger
     */
    public function __construct(
        private readonly Config $config,
        private readonly DirectiveScanner $scanner,
        private readonly JsonFactory $jsonFactory,
        private readonly HttpResponse $httpResponse,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Reject the request before it reaches a resolver if it carries directive syntax.
     *
     * @param GraphQl $subject
     * @param callable $proceed
     * @param RequestInterface $request
     * @return ResponseInterface
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundDispatch(GraphQl $subject, callable $proceed, RequestInterface $request): ResponseInterface
    {
        if (!$this->config->isGraphQlGuardEnabled()) {
            return $proceed($request);
        }

        $rawBody = (string)$request->getContent();
        $matches = $this->scanner->scanString($rawBody);

        if (empty($matches) && $rawBody !== '') {
            $decoded = json_decode($rawBody, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $matches = $this->scanner->scanStructure($decoded);
            }
        }

        if (!empty($matches)) {
            $this->logger->warning(
                'StyleSmugglerShield: blocked GraphQL request containing template directive syntax',
                [
                    'remote_ip' => $request->getServer('REMOTE_ADDR'),
                    'matched' => reset($matches),
                ]
            );

            return $this->buildRejectionResponse();
        }

        return $proceed($request);
    }

    /**
     * Build a GraphQL-style 400 error response.
     *
     * @return ResponseInterface
     */
    private function buildRejectionResponse(): ResponseInterface
    {
        $jsonResult = $this->jsonFactory->create();
        $jsonResult->setHttpResponseCode(400);
        $jsonResult->setData([
            'errors' => [
                [
                    'message' => 'The request was rejected: input values must not contain template directive syntax.',
                    'extensions' => ['category' => 'graphql-input'],
                ],
            ],
        ]);
        $jsonResult->renderResult($this->httpResponse);

        return $this->httpResponse;
    }
}
