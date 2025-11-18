<?php

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\TransactionService;
use App\Interfaces\Repositories\TransactionRepositoryInterface;
use App\Interfaces\Repositories\UserRepositoryInterface;
use App\Interfaces\Repositories\CompteRepositoryInterface;
use App\Interfaces\Notifications\BrevoServiceInterface;
use App\Models\User;
use App\Models\Compte;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;

class TransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected TransactionService $transactionService;
    protected $transactionRepoMock;
    protected $userRepoMock;
    protected $compteRepoMock;
    protected $brevoMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->transactionRepoMock = Mockery::mock(TransactionRepositoryInterface::class);
        $this->userRepoMock = Mockery::mock(UserRepositoryInterface::class);
        $this->compteRepoMock = Mockery::mock(CompteRepositoryInterface::class);
        $this->brevoMock = Mockery::mock(BrevoServiceInterface::class);

        $this->transactionService = new TransactionService(
            $this->transactionRepoMock,
            $this->userRepoMock,
            $this->compteRepoMock,
            $this->brevoMock
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_create_deposit_transaction()
    {
        // Arrange
        $userId = 1;
        $data = [
            'telephone_recepteur_id' => '771234567',
            'montant' => 50000,
            'note' => 'Test deposit'
        ];

        $user = new User(['id' => $userId, 'nom' => 'Test', 'prenom' => 'User']);
        $receiver = new User(['id' => 2, 'nom' => 'Receiver', 'prenom' => 'User']);
        $compte = new Compte(['id' => 1, 'solde' => 0]);

        $this->userRepoMock
            ->shouldReceive('findById')
            ->with($userId)
            ->andReturn($user);

        $this->userRepoMock
            ->shouldReceive('findByTelephone')
            ->with($data['telephone_recepteur_id'])
            ->andReturn($receiver);

        $this->userRepoMock
            ->shouldReceive('getUserAccounts')
            ->with($receiver->id)
            ->andReturn(collect([$compte]));

        $this->transactionRepoMock
            ->shouldReceive('create')
            ->once()
            ->andReturn(new \App\Models\Transaction($data));

        // Act
        $result = $this->transactionService->createDeposit($data, $userId);

        // Assert
        $this->assertInstanceOf(\App\Models\Transaction::class, $result);
    }

    /** @test */
    public function it_throws_exception_when_receiver_not_found()
    {
        // Arrange
        $userId = 1;
        $data = [
            'telephone_recepteur_id' => '771234567',
            'montant' => 50000
        ];

        $user = new User(['id' => $userId]);

        $this->userRepoMock
            ->shouldReceive('findById')
            ->with($userId)
            ->andReturn($user);

        $this->userRepoMock
            ->shouldReceive('findByTelephone')
            ->with($data['telephone_recepteur_id'])
            ->andReturn(null);

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Utilisateur destinataire non trouvé');

        $this->transactionService->createDeposit($data, $userId);
    }

    /** @test */
    public function it_validates_transaction_permissions()
    {
        // Arrange
        $userId = 1;
        $user = new User([
            'id' => $userId,
            'type' => 'client',
            'statut' => 'actif'
        ]);

        $this->userRepoMock
            ->shouldReceive('findById')
            ->with($userId)
            ->andReturn($user);

        // Act
        $canTransfer = $this->transactionService->validateTransactionPermissions($userId, 'transfert');

        // Assert
        $this->assertTrue($canTransfer);
    }

    /** @test */
    public function it_checks_sufficient_balance()
    {
        // Arrange
        $userId = 1;
        $amount = 50000;
        $user = new User(['id' => $userId, 'tax_percentage' => 2.5]);
        $compte = new Compte(['id' => 1, 'solde' => 100000]);

        $this->userRepoMock
            ->shouldReceive('findById')
            ->with($userId)
            ->andReturn($user);

        $this->userRepoMock
            ->shouldReceive('getUserAccounts')
            ->with($userId)
            ->andReturn(collect([$compte]));

        // Act
        $hasBalance = $this->transactionService->hasSufficientBalance($userId, $amount, 'transfert');

        // Assert
        $this->assertTrue($hasBalance);
    }
}
