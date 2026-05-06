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
    public function new(Request $request, EntityManagerInterface $entityManager, ProductRepository $productRepository): Response
    {
        $invoice = new Invoice();
        $form = $this->createForm(InvoiceType::class, $invoice);
        $form->handleRequest($request);

        if($request->request->has('add_line')){
            $productId = $request->request->get('product_id');
            $quantity = $request->request->get('quantity', 1);
            $unitPrice = $request->request->get('unit_price');

            $product = $productRepository->find($productId);

            if ($product) { 
                $product->setQuantity($quantity);
                $product->setPrice($unitPrice);
                $invoice->addProduct($product);

                $entityManager->persist($invoice);
                $entityManager->persist($product);
                $entityManager->flush();

                return $this->redirectToRoute('app_invoice_new');
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $status = $request->request->get('status');
            $invoice->setStatus($status);

            $entityManager->persist($invoice);
            $entityManager->flush();

            return $this->redirectToRoute('app_invoice', [], Response::HTTP_SEE_OTHER);
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
    public function edit(Request $request, Invoice $invoice, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(InvoiceType::class, $invoice);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_invoice', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('invoice/edit.html.twig', [
            'invoice' => $invoice,
            'form' => $form,
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
}
