<?php

namespace Tests\Feature;

use App\Models\DebitCard;
use App\Models\DebitCardTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class DebitCardTransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected DebitCard $debitCard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->debitCard = DebitCard::factory()->create([
            'user_id' => $this->user->id
        ]);
        Passport::actingAs($this->user);
    }

    public function testCustomerCanSeeAListOfDebitCardTransactions()
    {
        // get /debit-card-transactions
        DebitCardTransaction::factory()->count(3)->create([
            'debit_card_id' => $this->debitCard->id,
        ]);

        $response = $this->getJson('/api/debit-card-transactions?debit_card_id=' . $this->debitCard->id);

        $response->assertStatus(200);
        $response->assertJsonCount(3);
    }

    public function testCustomerCannotSeeAListOfDebitCardTransactionsOfOtherCustomerDebitCard()
    {
        // get /debit-card-transactions
        $otherUser = User::factory()->create();
        $otherDebitCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        DebitCardTransaction::factory()->create([
            'debit_card_id' => $otherDebitCard->id,
        ]);

        DebitCardTransaction::factory()->create();

        $response = $this->getJson('/api/debit-card-transactions?debit_card_id=' . $otherDebitCard->id);

        $response->assertStatus(403);
    }

    public function testCustomerCanCreateADebitCardTransaction()
    {
        // post /debit-card-transactions
        $payload = [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 100000,
            'currency_code' => DebitCardTransaction::CURRENCY_IDR,
        ];

        $response = $this->postJson('/api/debit-card-transactions', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('debit_card_transactions', [
            'debit_card_id' => $this->debitCard->id,
            'amount' => 100000,
            'currency_code' => DebitCardTransaction::CURRENCY_IDR,
        ]);
    }

    public function testCustomerCannotCreateADebitCardTransactionToOtherCustomerDebitCard()
    {
        // post /debit-card-transactions
        $otherUser = User::factory()->create();
        $otherDebitCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $payload = [
            'debit_card_id' => $otherDebitCard->id,
            'amount' => 50000,
            'currency_code' => DebitCardTransaction::CURRENCY_SGD,
        ];

        $response = $this->postJson('/api/debit-card-transactions', $payload);

        $response->assertStatus(403);
        $this->assertDatabaseMissing('debit_card_transactions', [
            'debit_card_id' => $otherDebitCard->id,
            'amount' => 50000,
        ]);
    }

    public function testCustomerCanSeeADebitCardTransaction()
    {
        // get /debit-card-transactions/{debitCardTransaction}
        $transaction = DebitCardTransaction::factory()->create([
            'debit_card_id' => $this->debitCard->id,
        ]);

        $response = $this->getJson('/api/debit-card-transactions/' . $transaction->id);

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'amount' => (string) $transaction->amount,
            'currency_code' => $transaction->currency_code,
        ]);
    }

    public function testCustomerCannotSeeADebitCardTransactionAttachedToOtherCustomerDebitCard()
    {
        // get /debit-card-transactions/{debitCardTransaction}
        $otherUser = User::factory()->create();
        $otherDebitCard = DebitCard::factory()->create([
            'user_id' => $otherUser->id,
        ]);

        $transaction = DebitCardTransaction::factory()->create([
            'debit_card_id' => $otherDebitCard->id,
        ]);

        $response = $this->getJson('/api/debit-card-transactions/' . $transaction->id);

        $response->assertStatus(403);
    }

    // Extra bonus for extra tests :)
    public function testCustomerCannotCreateTransactionWithInvalidData()
    {
        $payload = [
            'debit_card_id' => $this->debitCard->id,
            'amount' => -1000,
            'currency_code' => 'XXX',
        ];

        $response = $this->postJson('/api/debit-card-transactions', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['currency_code']);
    }

    public function testCustomerCannotDeleteOtherCustomerTransaction()
    {
        $otherUser = User::factory()->create();
        $otherDebitCard = DebitCard::factory()->create(['user_id' => $otherUser->id]);
        $transaction = DebitCardTransaction::factory()->create([
            'debit_card_id' => $otherDebitCard->id,
        ]);

        $response = $this->deleteJson('/api/debit-card-transactions/' . $transaction->id);

        $response->assertStatus(405);
    }
}
