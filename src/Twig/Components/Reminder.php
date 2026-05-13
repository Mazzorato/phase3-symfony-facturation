<?php 

namespace App\Twig\Components;

use App\Repository\InvoiceRepository;
use App\Service\EmailInvoiceService;
use Symfony\Component\Filesystem\Filesystem;
use Sensiolabs\GotenbergBundle\GotenbergPdfInterface;
use Sensiolabs\GotenbergBundle\Processor\FileProcessor;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
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
        GotenbergPdfInterface $gotenberg,
        MailerInterface $mailer,
        Security $security,
        #[Autowire('%kernel.project_dir%')] string $projectDir
    ): void 
    {
        
        /** @var \App\Entity\User $user */
        $user = ($security->getUser());

        if (!$user) {
            return;
        }
        $invoices = $invoiceRepository->findBy(['status' => 'en_attente']);
        $count = 0;
        $emailService->sendAll($invoice);
        
        foreach ($invoices as $invoice){
            $client = $invoice->getClient();

            if ($client === null|| $client->getEmail()) {
                continue;
            }
            $pdfPath = $projectDir . '/var/relance-' . $invoice->getNumber() . '.pdf';
            
            $gotenberg->html()
                ->content('invoice/pdf.html.twig' , ['invoice' => $invoice])
                ->processor(new FileProcessor (new Filesystem(), $pdfPath))
                ->generate()
                ->process();

            $email = (new Email())
                ->from($user->getUserIdentifier())
                ->to($client->getEmail())
                ->subject('Relance automatique : Facture N° ' . $invoice->getNumber())
                ->text("Bonjour " . $invoice->getClient()->getName() . ",\n\nCeci est une relance automatique concernant la facture N° " . $invoice->getNumber() . " pour un montant de " . $invoice->getTotalTtc() . " €.\n\nVous trouverez la facture en pièce jointe ainsi que notre IBAN pour le règlement : " . $user->getIban() . "\n\nCordialement,\n" . $user->getCompanyName())
                ->attachFromPath($pdfPath, 'Facture-' . $invoice->getNumber() . '.pdf', 'application/pdf');

            $mailer->send($email);

            if (file_exists($pdfPath)) {
                unlink($pdfPath);
            }
            $count++;
        }

        $this->sentCount = $count;
    }
}