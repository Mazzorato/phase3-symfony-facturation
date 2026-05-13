<?php
namespace App\Service;

use App\Entity\User;
use App\Entity\Invoice;
use Sensiolabs\GotenbergBundle\GotenbergPdfInterface;
use Sensiolabs\GotenbergBundle\Processor\FileProcessor;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class EmailInvoiceService {

    public function __construct(
        private GotenbergPdfInterface $gotenberg,
        private MailerInterface $mailer,
        private Security $security,
        #[Autowire('%kernel.project_dir%')] private string $projectDir
    ) {}

    /**
     * @return int $nbMailSent
     */
    public function sendAll(array $invoices) : int
    {
        $nbMailSent = 0;
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new \LogicException('L\'utilisateur doit être connecté');
        }
        
        foreach ($invoices as $invoice){
            $client = $invoice->getClient();

            if ($client === null || !$client->getEmail()) {
                continue;
            }
            
            $directory = $this->projectDir . '/var';
            
            $generatePdfPath = $this->gotenberg->html()
                ->content('invoice/pdf.html.twig' , ['invoice' => $invoice])
                ->processor(new FileProcessor(new Filesystem(), $directory))
                ->generate()
                ->process();

            $email = (new Email())
                ->from($user->getUserIdentifier())
                ->to($client->getEmail())
                ->subject('Relance automatique : Facture N° ' . $invoice->getNumber())
                ->text("Bonjour " . $client->getName() . ",\n\nCeci est une relance automatique concernant la facture N° " . $invoice->getNumber() . " pour un montant de " . $invoice->getTotalTtc() . " €.\n\nVous trouverez la facture en pièce jointe ainsi que notre IBAN pour le règlement : " . $user->getIban() . "\n\nCordialement,\n" . $user->getCompanyName())
                ->attachFromPath($generatePdfPath, 'Facture-' . $invoice->getNumber() . '.pdf', 'application/pdf');

            $this->mailer->send($email);

            if (file_exists($generatePdfPath)) {
                unlink($generatePdfPath);
            }
            
            $nbMailSent++;
        }
        return $nbMailSent;
    }
}