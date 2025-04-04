<?php
declare(strict_types=1);

namespace Kiener\MolliePayments\Controller\Api\Support;

use Kiener\MolliePayments\Facade\MollieSupportFacade;
use Psr\Log\LoggerInterface;
use Shopware\Core\Content\MailTemplate\Exception\MailTransportFailedException;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Validation\Exception\ConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class SupportControllerBase extends AbstractController
{
    /**
     * @var MollieSupportFacade
     */
    protected $supportFacade;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    public function __construct(MollieSupportFacade $supportFacade, LoggerInterface $logger)
    {
        $this->supportFacade = $supportFacade;
        $this->logger = $logger;
    }

    public function requestSupport(Request $request, Context $context): JsonResponse
    {
        $data = $request->request;

        $name = (string) $data->get('name');
        $email = (string) $data->get('email');
        $recipientLocale = (string) $data->get('recipientLocale');
        $subject = (string) $data->get('subject');
        $message = (string) $data->get('message');

        return $this->sendSupportRequest(
            $name,
            $email,
            $recipientLocale,
            $request->getHost(),
            $subject,
            $message,
            $context
        );
    }

    public function requestSupportLegacy(Request $request, Context $context): JsonResponse
    {
        $data = $request->request;

        $name = (string) $data->get('name');
        $email = (string) $data->get('email');
        $recipientLocale = (string) $data->get('recipientLocale');
        $subject = (string) $data->get('subject');
        $message = (string) $data->get('message');

        return $this->sendSupportRequest(
            $name,
            $email,
            $recipientLocale,
            $request->getHost(),
            $subject,
            $message,
            $context
        );
    }

    private function sendSupportRequest(string $name, string $email, ?string $recipientLocale, string $host, string $subject, string $message, Context $context): JsonResponse
    {
        try {
            $this->logger->info('Sending Support Request to Mollie: ' . $subject);

            $this->supportFacade->sendSupportRequest(
                $name,
                $email,
                $recipientLocale,
                $host,
                $subject,
                $message,
                $context
            );

            return $this->json(
                [
                    'success' => true,
                ]
            );
        } catch (ConstraintViolationException|MailTransportFailedException $e) {
            $this->logger->error(
                $e->getMessage(),
                [
                    'error' => $message,
                    'exceptionParams' => $e->getParameters(),
                ]
            );

            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error(
                $e->getMessage(),
                [
                    'error' => $message,
                ]
            );

            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
