<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use App\Entity\Invoice;
use App\Form\InvoiceType;
use App\Repository\InvoiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/invoice')]
final class InvoiceController extends AbstractController
{
    #[Route(name: 'app_invoice', methods: ['GET'])]
    public function index(InvoiceRepository $invoiceRepository, Request $request): Response
    {
        $status = $request->query->get('status');

        if ($status) {
            $invoices = $invoiceRepository->findBy(['status' => $status]);
        } else {
            $invoices = $invoiceRepository->findAll();
        }

        return $this->render('invoice/index.html.twig', [
            'invoices' => $invoices,
            'currentStatus' => $status,
        ]);
    }

    #[Route('/new', name: 'app_invoice_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, ProductRepository $productRepository, InvoiceRepository $invoiceRepository): Response
    {
        $invoice = new Invoice();
        $form = $this->createForm(InvoiceType::class, $invoice);
        
        if($request->request->has('add_line')){
            $productId = $request->request->get('product_id');
            $quantity = $request->request->get('quantity', 1);
            $unitPrice = $request->request->get('unit_price');   
            $product = $productRepository->find($productId);
            
            if ($product) { 
                $invoice->SetStatus('brouillon');
                $product->setQuantity($quantity);
                $product->setPrice($unitPrice);
                $invoice->addProduct($product);
                
                $entityManager->persist($invoice);
                $entityManager->persist($product);
                $entityManager->flush();
                
                return $this->redirectToRoute('app_invoice_edit', [ 'id' => $invoice->getId()]);
            }
        }
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $status = $request->request->get('status');
            $invoice->setStatus($status);

            $total = 0;
            foreach ($invoice->getProducts() as $product) {
                $total += $product->getPrice() * $product->getQuantity();
            }
            $invoice->setTotalTtc($total);

            $date = new \DateTime();
            $count = $invoiceRepository->countInvoicesThisMonth() + 1;
            $invoice->setNumber('FACT-' . $date->format('Ymd') . '-' . $count);
            $invoice->setCreateAt($date);

            $entityManager->persist($invoice);
            $entityManager->flush();

            return $this->redirectToRoute('app_invoice_show',[ 'id' => $invoice->getId()], Response::HTTP_SEE_OTHER);
        }

        return $this->render('invoice/new.html.twig', [
            'invoice' => $invoice,
            'form' => $form,
            'products' => $productRepository->findAll(),
        ]);
    }

    #[Route('/{id}', name: 'app_invoice_show', methods: ['GET'])]
    public function show(Invoice $invoice): Response
    {
        return $this->render('invoice/show.html.twig', [
            'invoice' => $invoice,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_invoice_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Invoice $invoice, EntityManagerInterface $entityManager, ProductRepository $productRepository, InvoiceRepository $invoiceRepository): Response
    {

    if ($invoice->getStatus() !== 'brouillons' && $invoice->getStatus() !== null) {
        return $this->redirectToRoute('app_invoice_show', ['id' => $invoice->getId()]);
    }

    $form = $this->createForm(InvoiceType::class, $invoice);

    if ($request->request->has('add_line')) {
        $productId = $request->request->get('product_id');
        $quantity = $request->request->get('quantity', 1);
        $unitPrice = $request->request->get('unit_price');
        $product = $productRepository->find($productId);

        if ($product) {
            $product->setQuantity($quantity);
            $product->setPrice($unitPrice);
            $invoice->addProduct($product);
            $entityManager->flush();
        }
        return $this->redirectToRoute('app_invoice_edit', ['id' => $invoice->getId()]);
    }

    if ($request->isMethod('POST') && $request->request->has('status')) {
        $status = $request->request->get('status');
        $invoice->setStatus($status);

        $total = 0;
        foreach ($invoice->getProducts() as $product) {
            $total += $product->getPrice() * $product->getQuantity();
        }
        $invoice->setTotalTtc($total);

        if (!$invoice->getNumber()) {
            $date = new \DateTime();
            $count = $invoiceRepository->countInvoicesThisMonth() + 1;
            $invoice->setNumber('FACT-' . $date->format('Ymd') . '-' . $count);
            $invoice->setCreateAt(new \DateTime());
        }

        $entityManager->flush();
        return $this->redirectToRoute('app_invoice_show', ['id' => $invoice->getId()], Response::HTTP_SEE_OTHER);
    }

    return $this->render('invoice/edit.html.twig', [
        'invoice' => $invoice,
        'form' => $form,
        'products' => $productRepository->findAll(),
    ]);
    }

    #[Route('/{id}', name: 'app_invoice_delete', methods: ['POST'])]
    public function delete(Request $request, Invoice $invoice, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$invoice->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($invoice);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_invoice', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/validate', name:'app_invoice_validate', methods: ['POST'])]
    public function validate(Request $request, Invoice $invoice, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('validate' . $invoice->getId(), $request->getPayload()->getString('_token'))){
            $invoice->setStatus('en_attente');
            $entityManager->flush();
        }
        return $this->redirectToRoute('app_invoice_show', ['id' => $invoice->getId()]);
    }

    #[Route('/{id}/pay', name: 'app_invoice_pay', methods: ['POST'])]
    public function pay(Request $request, Invoice $invoice, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('pay' . $invoice->getId(), $request->getPayload()->getString('_token'))){
            $invoice->setStatus('payées');
            $entityManager->flush();
        }
        return $this->redirectToRoute('app_invoice_show', ['id' => $invoice->getId()]);
    }
}
