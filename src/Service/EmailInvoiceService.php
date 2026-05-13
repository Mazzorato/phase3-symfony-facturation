<?php
namespace App\Service;

use App\Entity\Invoice;
use Symfony\Component\Mailer\MailerInterface;



class EmailInvoiceService{

    function __construct(
        private GotenbergPdfInterface $gotenberg,
        private MailerInterface $mailer,


     ){/**/}

    /**
     * @return int $nbMailSent
     */
    public function sendAll(array $invoices) : int
    {
    foreach ($invoices as $invoice){
                $client = $invoice->getClient();

                if ($client === null|| $client->getEmail()) {
                    continue;
                }
                $pdfPath = $projectDir . '/var/relance-' . $invoice->getNumber() . '.pdf';
                
                $this->gotenberg->html()
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

                $this->mailer->send($email);

                if (file_exists($pdfPath)) {
                    unlink($pdfPath);
                }
                $count++;
            }
        throw new \Exception('Not implemented');
    }

}