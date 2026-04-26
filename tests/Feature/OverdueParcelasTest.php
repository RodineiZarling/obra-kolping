<?php

namespace Tests\Feature;

use App\Models\ContasPagar;
use App\Models\ContasPagarParcela;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OverdueParcelasTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test that parcelas with past due dates are marked as overdue.
     *
     * @return void
     */
    public function test_parcelas_with_past_due_dates_are_marked_as_overdue()
    {
        // Create a parcela with a due date in the past
        $overdueParcela = ContasPagarParcela::create([
            'empresa' => 1,
            'contas_pagar_id' => 1,
            'vencimento' => Carbon::now()->subDays(5), // 5 days ago
            'valor' => 100.00,
            'status' => 1, // Em aberto
            'parcela' => 1,
            'total_parcelas' => 1,
        ]);

        // Create a parcela with a due date in the future
        $futureParcela = ContasPagarParcela::create([
            'empresa' => 1,
            'contas_pagar_id' => 1,
            'vencimento' => Carbon::now()->addDays(5), // 5 days from now
            'valor' => 100.00,
            'status' => 1, // Em aberto
            'parcela' => 1,
            'total_parcelas' => 1,
        ]);

        // Create a parcela with a past due date but already paid
        $paidParcela = ContasPagarParcela::create([
            'empresa' => 1,
            'contas_pagar_id' => 1,
            'vencimento' => Carbon::now()->subDays(5), // 5 days ago
            'recebimento' => Carbon::now(), // Paid today
            'valor' => 100.00,
            'status' => 2, // Pago
            'parcela' => 1,
            'total_parcelas' => 1,
        ]);

        // Run the update method
        ContasPagarParcela::updateAllOverdueStatus();

        // Refresh the models from the database
        $overdueParcela->refresh();
        $futureParcela->refresh();
        $paidParcela->refresh();

        // Assert that the overdue parcela has status 4 (Vencido)
        $this->assertEquals(4, $overdueParcela->status);

        // Assert that the future parcela still has status 1 (Em aberto)
        $this->assertEquals(1, $futureParcela->status);

        // Assert that the paid parcela still has status 2 (Pago)
        $this->assertEquals(2, $paidParcela->status);
    }

    /**
     * Test that the isOverdue method correctly identifies overdue parcelas.
     *
     * @return void
     */
    public function test_is_overdue_method()
    {
        // Create a parcela with a due date in the past
        $overdueParcela = new ContasPagarParcela([
            'vencimento' => Carbon::now()->subDays(5), // 5 days ago
            'status' => 1, // Em aberto
        ]);

        // Create a parcela with a due date in the future
        $futureParcela = new ContasPagarParcela([
            'vencimento' => Carbon::now()->addDays(5), // 5 days from now
            'status' => 1, // Em aberto
        ]);

        // Create a parcela with a past due date but already paid
        $paidParcela = new ContasPagarParcela([
            'vencimento' => Carbon::now()->subDays(5), // 5 days ago
            'recebimento' => Carbon::now(), // Paid today
            'status' => 2, // Pago
        ]);

        // Assert that the overdue parcela is identified as overdue
        $this->assertTrue($overdueParcela->isOverdue());

        // Assert that the future parcela is not identified as overdue
        $this->assertFalse($futureParcela->isOverdue());

        // Assert that the paid parcela is not identified as overdue
        $this->assertFalse($paidParcela->isOverdue());
    }
}
