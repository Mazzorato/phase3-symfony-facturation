<?php
namespace App\Twig\Components;

use App\Repository\InvoiceRepository;
use App\Service\EmailInvoiceService;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveAction;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('reminder')]
class Reminder
{
    use DefaultActionTrait;

    public ?int $sentCount = null;

    #[LiveAction]
    public function sendAll(
        EmailInvoiceService $emailService,
        InvoiceRepository $invoiceRepository,
        Security $security,
    ): void {
        $user = $security->getUser();

        if (!$user) {
            return;
        }

        $invoices = $invoiceRepository->findByUserAndStatus($user, 'en_attente');

        $this->sentCount = $emailService->sendAll($invoices);
    }
}