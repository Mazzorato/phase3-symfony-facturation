<?php 

namespace App\Twig\Components;

use App\Entity\Invoice;
use Symfony\UX\LiveComponent\Attribute\AsLiveComponent;
use Symfony\UX\LiveComponent\Attribute\LiveProp;
use Symfony\UX\LiveComponent\DefaultActionTrait;

#[AsLiveComponent('invoice_lines')]
class InvoiceLines
{
    use DefaultActionTrait;

    #[LiveProp]
    public Invoice $invoice;

    #[LiveProp(writable: true)]
    public array $quantity = [];

    public function mount(Invoice $invoice): void
    {
        $this->invoice = $invoice;
        foreach ($invoice->getProducts() as $product) {
            $this->quantity[$product->getId()] = $product->getQuantity();
        }
    }

    public function getGrandTotal(): float 
    {
        $total = 0;
        foreach ($this->invoice->getProducts() as $product){
            $qty = $this->quantity[$product->getId()] ?? 0;
            $total += $product->getPrice() * $qty;
        }
        return $total;
    }
}